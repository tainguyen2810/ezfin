<?php

use App\Modules\Compare\Actions\ImportSavingsRates;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsRateImportException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Ghi nội dung CSV ra file tạm và nhập. */
function importCsv(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $content);

    return app(ImportSavingsRates::class)->handle($path);
}

/** Nhập và trả về danh sách lỗi theo số dòng. */
function importErrors(string $content): array
{
    try {
        importCsv($content);
    } catch (SavingsRateImportException $exception) {
        return $exception->errors;
    }

    throw new RuntimeException('Mong đợi lỗi nhưng file được nhập thành công.');
}

beforeEach(function () {
    $this->online = FinancialProduct::factory()->savings()->create(['slug' => 'online-a']);
    $this->counter = FinancialProduct::factory()->savings()->create(['slug' => 'tai-quay-b']);
});

it('imports rates with Vietnamese number and date formats', function () {
    $result = importCsv(<<<'CSV'
        san_pham,ky_han,kenh,lai_suat,so_tien_tu,so_tien_den,linh_lai,hieu_luc_tu
        online-a,12,online,"5,2",,,cuoi-ky,01/11/2026
        online-a,6,online,4.75,,,hang-thang,1/11/2026
        tai-quay-b,12,tai-quay,"5,5",1.000.000.000,,dau-ky,2026-11-01
        CSV);

    expect($result)->toBe(['created' => 3, 'updated' => 0, 'unchanged' => 0]);

    $rate = SavingsRate::query()->where('product_id', $this->counter->id)->sole();
    expect($rate->term_months)->toBe(12)
        ->and($rate->channel)->toBe(SavingsChannel::Counter)
        ->and($rate->interest_payment)->toBe(InterestPayment::Upfront)
        ->and($rate->rate)->toBe('5.5000')
        ->and($rate->min_amount)->toBe('1000000000.00')
        ->and($rate->max_amount)->toBeNull()
        ->and($rate->effective_from->toDateString())->toBe('2026-11-01');

    expect(SavingsRate::query()->where('term_months', 6)->sole())
        ->rate->toBe('4.7500')
        ->interest_payment->toBe(InterestPayment::Monthly)
        ->min_amount->toBe('0.00');
});

it('reads Excel files saved with a BOM, semicolons and Windows line endings', function () {
    $result = importCsv("\xEF\xBB\xBFsan_pham;ky_han;kenh;lai_suat;hieu_luc_tu\r\nonline-a;0;online;0,5;01/11/2026\r\n\r\n");

    expect($result['created'])->toBe(1)
        ->and(SavingsRate::sole()->rate)->toBe('0.5000');
});

it('does not create duplicates when the same file is imported again', function () {
    $csv = "san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nonline-a,12,online,\"5,2\",01/11/2026\nonline-a,6,online,\"4,8\",01/11/2026\n";

    importCsv($csv);
    $again = importCsv(str_replace('"4,8"', '"4,9"', $csv));

    expect($again)->toBe(['created' => 0, 'updated' => 1, 'unchanged' => 1])
        ->and(SavingsRate::count())->toBe(2)
        ->and(SavingsRate::query()->where('term_months', 6)->value('rate'))->toBe('4.9000');
});

it('keeps history when a new effective date is imported', function () {
    importCsv("san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nonline-a,12,online,5.2,01/10/2026\n");
    importCsv("san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nonline-a,12,online,5.4,01/11/2026\n");

    expect(SavingsRate::count())->toBe(2);
});

it('reports every invalid line with its line number and writes nothing', function () {
    SavingsRate::factory()->for($this->online, 'product')->create(['rate' => 5.0]);

    $errors = importErrors(<<<'CSV'
        san_pham,ky_han,kenh,lai_suat,so_tien_tu,so_tien_den,linh_lai,hieu_luc_tu
        online-a,12,online,"5,2",,,cuoi-ky,01/11/2026
        khong-co,12,online,5,,,,01/11/2026

        online-a,abc,online,52,,,,31/02/2026
        online-a,6,qua-app,5,10000000,5000000,cuoi-thang,01/11/2026
        online-a,12,online,"5,3",,,cuoi-ky,01/11/2026
        CSV);

    expect(array_keys($errors))->toBe([3, 5, 6, 7])
        ->and($errors[3])->toBe(['Không có sản phẩm tiết kiệm với mã "khong-co".'])
        ->and($errors[5])->toHaveCount(3)
        ->and($errors[5][0])->toContain('Kỳ hạn')
        ->and($errors[5][1])->toContain('Lãi suất')
        ->and($errors[5][2])->toContain('Ngày hiệu lực')
        ->and($errors[6])->toHaveCount(3)
        ->and($errors[7])->toBe(['Trùng với dòng 2 (cùng sản phẩm, kỳ hạn, kênh, lĩnh lãi, số tiền từ, ngày hiệu lực).']);

    // Dòng 2 hợp lệ nhưng không được ghi vì file có lỗi; dữ liệu cũ giữ nguyên.
    expect(SavingsRate::count())->toBe(1)
        ->and(SavingsRate::sole()->rate)->toBe('5.0000');
});

it('rejects files with wrong columns, encoding or no data', function (string $content, int $line, string $message) {
    $errors = importErrors($content);

    expect($errors)->toHaveKey($line)
        ->and(implode(' ', $errors[$line]))->toContain($message);
})->with([
    'missing column' => ["san_pham,ky_han,lai_suat\nonline-a,12,5\n", 1, 'Thiếu cột: kenh, hieu_luc_tu'],
    'unknown column' => ["san_pham,ky_han,kenh,lai_suat,hieu_luc_tu,ghi_chu\n", 1, 'Cột không hỗ trợ: ghi_chu'],
    'not utf-8' => ["san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\n".mb_convert_encoding('tiết-kiệm', 'UTF-16')."\n", 0, 'CSV UTF-8'],
    'empty file' => ['', 0, 'File trống'],
    'header only' => ["san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\n", 0, 'không có dòng dữ liệu'],
]);

it('does not accept credit cards as savings products', function () {
    FinancialProduct::factory()->create(['slug' => 'the-tin-dung-a']);

    expect(importErrors("san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nthe-tin-dung-a,12,online,5,01/11/2026\n"))
        ->toHaveKey(2);
});

it('provides a template that imports cleanly once its product exists', function () {
    FinancialProduct::factory()->savings()->create(['slug' => 'tiet-kiem-online-ngan-hang-a']);

    expect(importCsv(ImportSavingsRates::template())['created'])->toBe(4);
});
