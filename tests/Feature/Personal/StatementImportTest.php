<?php

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Actions\DeleteAccount;
use App\Modules\Personal\Imports\StatementMapping;
use App\Modules\Personal\Imports\StatementReader;
use App\Modules\Personal\Imports\StatementValues;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

uses(RefreshDatabase::class);

const IMPORT = 'http://app.ezfin.test/giao-dich/nhap-sao-ke';

/** Sao kê kiểu ngân hàng Việt Nam: vài dòng thông tin, tiêu đề, cột ghi nợ/ghi có, dòng tổng cuối. */
const STATEMENT_CSV = <<<'CSV'
SAO KÊ TÀI KHOẢN;;;;
Số tài khoản: 0123456789;;;;
Ngày GD;Số tham chiếu;Ghi nợ;Ghi có;Nội dung chi tiết
01/10/2026;FT001;;25.000.000;"LUONG THANG 9
CONG TY ABC"
03/10/2026;FT002;55.000;;GRAB*THANH TOAN
03/10/2026;FT003;55.000;;GRAB*THANH TOAN
05/10/2026;FT004;6.000.000;;CHUYEN TIEN THUE NHA
32/10/2026;FT005;10.000;;NGAY SAI
;;6.110.000;25.000.000;Tổng cộng
CSV;

beforeEach(function () {
    Storage::fake('local');
    Feature::activate('personal');
    $this->user = User::factory()->create();
    app(CreateDefaultCategories::class)->handle($this->user);
    $this->actingAs($this->user);
    $this->bank = Wallet::factory()->for($this->user)->create(['name' => 'Vietcombank', 'opening_balance' => 0]);
});

function uploadStatement(string $content = STATEMENT_CSV, string $name = 'sao-ke.csv', ?int $walletId = null): string
{
    $response = test()->post(IMPORT, ['wallet_id' => $walletId ?? test()->bank->id, 'file' => UploadedFile::fake()->createWithContent($name, $content)]);
    $response->assertRedirect();

    return $response->headers->get('Location');
}

function confirmImport(string $url, array $extra = []): TestResponse
{
    $page = test()->get($url)->assertOk();
    $mapping = collect($page->viewData('mapping')->query())->map(fn ($value) => $value ?? '')->all();

    return test()->post($url, [...$mapping, 'expense_category_id' => Category::firstWhere('name', 'Khác')->id,
        'income_category_id' => Category::firstWhere('name', 'Thu khác')->id, ...$extra]);
}

it('reads Vietnamese amounts and dates', function (mixed $value, ?int $expected) {
    expect(StatementValues::amount($value))->toBe($expected);
})->with([
    ['1.250.000', 1_250_000], ['1,250,000', 1_250_000], ['1.250.000,00', 1_250_000], ['1,250,000.50', 1_250_001],
    ['-55.000', -55_000], ['(55.000)', -55_000], ['12.000 VND', 12_000], ['−9.000', -9_000], ['1.250', 1_250], ['12.5', 13],
    [250000.0, 250_000], ['', null], ['-', null], ['abc', null],
]);

it('reads dates in day-first order by default', function () {
    expect(StatementValues::date('03/10/2026')->toDateString())->toBe('2026-10-03')
        ->and(StatementValues::date('03/10/2026', 'mdy')->toDateString())->toBe('2026-03-10')
        ->and(StatementValues::date('03-10-26 14:22')->toDateString())->toBe('2026-10-03')
        ->and(StatementValues::date('2026-10-03')->toDateString())->toBe('2026-10-03')
        ->and(StatementValues::date(46298)->toDateString())->toBe('2026-10-03')
        ->and(StatementValues::date('32/10/2026'))->toBeNull()
        ->and(StatementValues::date('Tổng cộng'))->toBeNull();
});

it('finds the header row and the debit, credit, date and description columns', function () {
    $rows = app(StatementReader::class)->csv(STATEMENT_CSV);
    $mapping = StatementMapping::guess($rows);

    expect($mapping->headerRow)->toBe(2)
        ->and([$mapping->date, $mapping->debit, $mapping->credit, $mapping->description, $mapping->amount])->toBe([0, 2, 3, 4, null])
        ->and($rows[3][4])->toBe("LUONG THANG 9\nCONG TY ABC");
});

it('previews, imports into the wallet and skips the same statement next time', function () {
    $url = uploadStatement();

    $this->get($url)->assertOk()->assertSee('Sẽ nhập: 4')->assertSee('Không đọc được ngày')->assertDontSee('Tổng cộng');
    confirmImport($url)->assertRedirect()->assertSessionHas('toast.message', 'Đã nhập 4 giao dịch vào Vietcombank. 1 dòng lỗi không nhập.');

    $rows = Transaction::orderBy('occurred_on')->orderBy('id')->get();
    expect($rows->map(fn ($t) => [$t->occurred_on->toDateString(), $t->type->value, $t->amountInt(), $t->note])->all())->toBe([
        ['2026-10-01', 'income', 25_000_000, 'LUONG THANG 9 CONG TY ABC'],
        ['2026-10-03', 'expense', 55_000, 'GRAB*THANH TOAN'],
        ['2026-10-03', 'expense', 55_000, 'GRAB*THANH TOAN'],
        ['2026-10-05', 'expense', 6_000_000, 'CHUYEN TIEN THUE NHA'],
    ])->and($rows->pluck('wallet_id')->unique()->all())->toBe([$this->bank->id])
        ->and($rows[0]->category->name)->toBe('Thu khác')
        ->and($this->bank->fresh()->balance())->toBe(25_000_000 - 6_110_000)
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    // Nhập lại đúng file đó: không thêm gì.
    $again = uploadStatement();
    $this->get($again)->assertSee('Đã nhập trước đó: 4');
    confirmImport($again)->assertSessionHas('toast.message', 'Đã nhập 0 giao dịch vào Vietcombank. Bỏ qua 4 dòng trùng. 1 dòng lỗi không nhập.');
    expect(Transaction::count())->toBe(4);
});

it('flags rows matching hand-entered transactions and imports them only when asked', function () {
    Transaction::create(['wallet_id' => $this->bank->id, 'category_id' => Category::firstWhere('name', 'Nhà ở')->id, 'type' => 'expense',
        'amount' => 6_000_000, 'occurred_on' => '2026-10-05', 'note' => 'Tiền nhà']);

    $url = uploadStatement();
    $this->get($url)->assertSee('Có thể trùng: 1');
    confirmImport($url);
    expect(Transaction::count())->toBe(4);

    $url = uploadStatement();
    confirmImport($url, ['include_possible_duplicates' => '1']);
    expect(Transaction::count())->toBe(5);
});

it('reads Excel files with real date and number cells and a single signed amount column', function () {
    $path = tempnam(sys_get_temp_dir(), 'ezfin').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['Ngày giao dịch', 'Mô tả', 'Số tiền']));
    $writer->addRow(Row::fromValues([new DateTimeImmutable('2026-10-07'), 'Hoàn tiền thẻ', 120000]));
    $writer->addRow(Row::fromValues([new DateTimeImmutable('2026-10-08'), 'Siêu thị', -350000.0]));
    $writer->close();

    $response = $this->post(IMPORT, ['wallet_id' => $this->bank->id, 'file' => new UploadedFile($path, 'sao-ke.xlsx', null, null, true)]);
    confirmImport($response->headers->get('Location'));
    unlink($path);

    expect(Transaction::orderBy('occurred_on')->get()->map(fn ($t) => [$t->occurred_on->toDateString(), $t->type->value, $t->amountInt()])->all())
        ->toBe([['2026-10-07', 'income', 120_000], ['2026-10-08', 'expense', 350_000]]);
});

it('lets the user fix the columns when the guess is wrong', function () {
    $url = uploadStatement("a,b,c\n2026-10-01,Cafe,-30000\n");
    $this->get($url)->assertSee('Chọn cột ngày giao dịch.');
    $this->get($url.'?dong_tieu_de=1&date=0&description=1&amount=2&debit=&credit=&kieu_ngay=dmy')->assertSee('Sẽ nhập: 1');
});

it('rejects unsupported files', function () {
    $this->post(IMPORT, ['wallet_id' => $this->bank->id, 'file' => UploadedFile::fake()->create('sao-ke.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['file' => 'Chỉ nhận file .csv hoặc .xlsx.']);
    $this->post(IMPORT, ['wallet_id' => $this->bank->id, 'file' => UploadedFile::fake()->createWithContent('sao-ke.xls', 'xx')])
        ->assertSessionHasErrors('file');
    $this->post(IMPORT, ['wallet_id' => $this->bank->id, 'file' => UploadedFile::fake()->createWithContent('sao-ke.csv', "\xC0\xC1\xFE")])
        ->assertSessionHasErrors('file');
});

it('keeps uploads and wallets private to their owner', function () {
    $url = uploadStatement();
    $stranger = User::factory()->create();
    $theirWallet = Wallet::factory()->for($stranger)->create();

    $this->post(IMPORT, ['wallet_id' => $theirWallet->id, 'file' => UploadedFile::fake()->createWithContent('a.csv', STATEMENT_CSV)])
        ->assertSessionHasErrors('wallet_id');

    $this->actingAs($stranger);
    $this->get($url)->assertNotFound();
    $this->post($url, ['expense_category_id' => 1, 'income_category_id' => 1])->assertNotFound();
    $this->get(IMPORT.'/khong-phai-uuid')->assertNotFound();
});

it('deletes pending uploads with the account and hides the screen when the app is off', function () {
    uploadStatement();
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);

    app(DeleteAccount::class)->handle($this->user);
    expect(Storage::disk('local')->allFiles())->toBe([]);

    Feature::deactivate('personal');
    $this->actingAs(User::factory()->create())->get(IMPORT)->assertNotFound();
});
