<?php

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\PdfText;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/helpers.php';

beforeEach(fn () => config(['ezfin.data_sync.delay_ms' => 0]));

/** Biểu lãi suất mẫu xuất ra PDF từ bảng (giống cách ngân hàng xuất từ Word/Excel). */
function samplePdf(): string
{
    return file_get_contents(base_path('tests/Fixtures/DataSync/sample-rates.pdf'));
}

it('reads PDF text line by line with one tab between cells', function () {
    $lines = PdfText::lines(samplePdf());

    expect($lines)->toBe([
        'BIỂU LÃI SUẤT TIỀN GỬI TIẾT KIỆM VND',
        'Hiệu lực từ ngày 06/11/2026',
        "Kỳ hạn\tLãi cuối kỳ\tLãi hằng tháng",
        "1 tháng\t4,75\t-",
        "6 tháng\t5,90\t5,80",
        "12 tháng\t6,20\t6,00",
    ])->and(PdfText::cells($lines[5]))->toBe(['12 tháng', '6,20', '6,00'])
        ->and(PdfText::cellPages(samplePdf())[0][2][1]['x'])->toBeGreaterThan(PdfText::cellPages(samplePdf())[0][2][0]['x']);
});

it('rejects files that are not PDF', function () {
    PdfText::pages('<html>not a pdf</html>');
})->throws(ScrapeException::class, 'không phải PDF');

it('downloads PDF sources through the fetcher', function () {
    fakeBankSites(['bank.test/bieu-lai-suat.pdf' => Http::response(samplePdf(), 200, ['Content-Type' => 'application/pdf'])]);

    expect(app(Fetcher::class)->pdf('https://bank.test/bieu-lai-suat.pdf')[0])->toContain("12 tháng\t6,20\t6,00");
});

it('previews a PDF or the HTML tables of a new source without saving anything', function () {
    fakeBankSites(['bank.test/bieu-lai-suat.pdf' => Http::response(samplePdf())]);

    $this->artisan('ezfin:probe', ['url' => 'https://bank.test/bieu-lai-suat.pdf'])
        ->expectsOutputToContain('=== Trang 1 (6 dòng)')
        ->expectsOutputToContain('12 tháng | 6,20 | 6,00')
        ->assertSuccessful();

    $this->artisan('ezfin:probe', ['url' => 'https://ocb.com.vn/vi/cong-cu/lai-suat', '--rows' => 3])
        ->expectsOutputToContain('2 bảng')
        ->expectsOutputToContain('Kỳ hạn | Tiền gửi có kỳ hạn | Tiết kiệm thông thường | Tiết kiệm Online')
        ->assertSuccessful();

    fakeBankSites(['bank.test/*' => Http::response('', 403)]);
    $this->artisan('ezfin:probe', ['url' => 'https://bank.test/x'])->expectsOutputToContain('từ chối truy cập')->assertFailed();
});
