<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Markets\Actions\ImportQuotes;
use App\Modules\Markets\Actions\MarketBoard;
use App\Modules\Markets\Actions\RecordQuote;
use App\Modules\Markets\Filament\Resources\FxRates\Pages\ManageFxRates;
use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function quotesCsv(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'quotes');
    file_put_contents($path, $content);

    return $path;
}

beforeEach(function () {
    Carbon::setTestNow('2026-11-13 10:00:00');
    Feature::activate('markets');
});

afterEach(fn () => Carbon::setTestNow());

it('returns 404 and stays out of the sitemap while there is no data or the module is off', function () {
    $this->get('http://ezfin.test/ty-gia')->assertNotFound();
    $this->get('http://ezfin.test/gia-vang')->assertNotFound();
    $this->get('http://ezfin.test/sitemap.xml')->assertDontSee('/ty-gia', false)->assertDontSee('/gia-vang', false);

    app(RecordQuote::class)->fx(['source' => 'Vietcombank', 'currency' => 'USD', 'quoted_at' => '13/11/2026 08:30', 'sell' => '25.440']);
    $this->get('http://ezfin.test/ty-gia')->assertOk();
    $this->get('http://ezfin.test/sitemap.xml')->assertSee('http://ezfin.test/ty-gia', false)->assertDontSee('/gia-vang', false);

    Feature::deactivate('markets');
    $this->get('http://ezfin.test/ty-gia')->assertNotFound();
});

it('imports a rate table atomically and updates on re-import instead of duplicating', function () {
    $import = app(ImportQuotes::class);
    $file = quotesCsv(ImportQuotes::template('fx'));

    expect($import->handle('fx', $file))->toBe(['created' => 2, 'updated' => 0])
        ->and($import->handle('fx', $file))->toBe(['created' => 0, 'updated' => 2]);

    $jpy = FxRate::where('currency', 'JPY')->sole();
    expect($jpy->buy_cash)->toBe('160.12')->and($jpy->sell)->toBe('170.30')
        ->and(FxRate::where('currency', 'USD')->value('sell'))->toBe('25440.00')
        ->and(FxRate::where('currency', 'USD')->value('quoted_at')->format('d/m/Y H:i'))->toBe('13/11/2026 08:30');

    $bad = quotesCsv("thoi_diem;nguon;ma;mua_tien_mat;mua_chuyen_khoan;ban\n12/11/2026 08:30;VCB;EUR;27.000;27.100;27.600\n12/11/2026 08:30;VCB;XXX;1;1;2\n12/11/2026 08:30;VCB;GBP;34.000;;33.000\n14/11/2026 08:30;VCB;AUD;;;16.700\n");
    expect(fn () => $import->handle('fx', $bad))->toThrow(InvalidArgumentException::class, 'Dòng 3: Mã ngoại tệ "XXX" chưa hỗ trợ');
    try {
        $import->handle('fx', $bad);
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('Dòng 4: Giá mua')->toContain('Dòng 5: Thời điểm "14/11/2026 08:30" ở tương lai');
    }
    expect(FxRate::count())->toBe(2);

    expect(fn () => $import->handle('gold', quotesCsv("thoi_diem,nguon,mua\n")))->toThrow(InvalidArgumentException::class, 'Thiếu cột: loai_vang, ban');
});

it('shows the latest table per source with the change against the previous day and a 30-day chart', function () {
    $record = app(RecordQuote::class);
    foreach ([['10/11/2026 08:30', '25.300'], ['12/11/2026 08:30', '25.350'], ['12/11/2026 15:00', '25.400'], ['13/11/2026 08:30', '25.440']] as [$at, $sell]) {
        $record->fx(['source' => 'Vietcombank', 'currency' => 'USD', 'quoted_at' => $at, 'buy_transfer' => '25.100', 'sell' => $sell]);
    }
    $record->fx(['source' => 'Vietcombank', 'currency' => 'EUR', 'quoted_at' => '13/11/2026 08:30', 'sell' => '27.650']);
    $record->fx(['source' => 'Vietcombank', 'currency' => 'EUR', 'quoted_at' => '01/11/2026 08:30', 'sell' => '30.000']); // cũ, không phải bảng mới nhất

    $batch = app(MarketBoard::class)->fx()[0];
    expect($batch['quoted_at']->format('d/m/Y H:i'))->toBe('13/11/2026 08:30')
        ->and(collect($batch['rows'])->map(fn ($row) => [$row['quote']->currency, $row['change']])->all())->toBe([['USD', 40.0], ['EUR', -2350.0]])
        ->and(app(MarketBoard::class)->fxHistory('Vietcombank', 'USD'))->toBe([
            ['date' => '2026-11-10', 'value' => 25300.0], ['date' => '2026-11-12', 'value' => 25400.0], ['date' => '2026-11-13', 'value' => 25440.0],
        ]);

    $this->get('http://ezfin.test/ty-gia')->assertOk()
        ->assertSee('Vietcombank công bố lúc')
        ->assertSee('08:30 13/11/2026')
        ->assertSeeInOrder(['USD', '25.100', '25.440', '▲ 40'])
        ->assertSee('Giá bán USD 30 ngày qua')
        ->assertDontSee('chưa được cập nhật hơn');

    // Đổi tiền: 100 USD theo giá mua chuyển khoản và giá bán.
    $this->get('http://ezfin.test/ty-gia?so_tien=100&ma=USD')->assertOk()
        ->assertSee('2.510.000 đ')->assertSee('2.544.000 đ')->assertSee('noindex', false);
});

it('flags stale tables and shows gold prices per source', function () {
    $record = app(RecordQuote::class);
    $record->gold(['source' => 'SJC', 'product' => 'Vàng miếng SJC 1 lượng', 'quoted_at' => '12/11/2026 09:00', 'buy' => '84.000.000', 'sell' => '86.000.000', 'source_url' => 'https://sjc.com.vn']);
    $record->gold(['source' => 'SJC', 'product' => 'Vàng miếng SJC 1 lượng', 'quoted_at' => '13/11/2026 09:00', 'buy' => '84.500.000', 'sell' => '86.500.000']);
    $record->gold(['source' => 'PNJ', 'product' => 'Nhẫn trơn PNJ 999.9', 'quoted_at' => '05/11/2026 09:00', 'buy' => '82.000.000', 'sell' => '84.000.000']);

    $this->get('http://ezfin.test/gia-vang')->assertOk()
        ->assertSeeInOrder(['Giá vàng SJC', '86.500.000 đ', '▲ 500.000 đ', 'Giá vàng PNJ'])
        ->assertSee('Số liệu của PNJ chưa được cập nhật hơn 3 ngày')
        ->assertSee('href="https://sjc.com.vn"', false);

    $this->get('http://ezfin.test/')->assertOk()->assertSee('Giá vàng bán ra · Vàng miếng SJC 1 lượng')->assertSee('86.500.000 đ');
});

it('rejects bad manual input', function (array $data, string $message) {
    expect(fn () => app(RecordQuote::class)->fx([...['source' => 'VCB', 'currency' => 'USD', 'quoted_at' => '13/11/2026 08:30', 'sell' => '25.440'], ...$data]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'no sell' => [['sell' => ''], 'Thiếu giá bán'],
    'negative' => [['sell' => '-1'], 'Giá bán không hợp lệ'],
    'bad date' => [['quoted_at' => '31/02/2026 08:30'], 'không hợp lệ'],
    'no source' => [['source' => ' '], 'Thiếu nguồn công bố'],
    'http source' => [['source_url' => 'http://vcb.example'], 'https://'],
]);

it('lets data operators enter rates in the admin and editors only view', function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->admin(Role::Editor)->create());
    $this->get('http://ezfin.test/quan-tri/ty-gia')->assertOk();
    Livewire::test(ManageFxRates::class)->assertActionHidden(CreateAction::class);

    $this->actingAs(User::factory()->admin(Role::Finance)->create());
    $this->get('http://ezfin.test/quan-tri/gia-vang')->assertForbidden();

    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());
    foreach (['25440', '25450'] as $sell) {
        Livewire::test(ManageFxRates::class)
            ->callAction(CreateAction::class, ['source' => 'Vietcombank', 'currency' => 'USD', 'quoted_at' => '2026-11-13 08:30:00', 'buy_transfer' => '25100', 'sell' => $sell])
            ->assertHasNoActionErrors();
    }
    expect(FxRate::sole()->sell)->toBe('25450.00');

    Livewire::test(ManageFxRates::class)
        ->callAction(CreateAction::class, ['source' => 'Vietcombank', 'currency' => 'USD', 'quoted_at' => '2026-11-13 09:00:00', 'buy_transfer' => '26000', 'sell' => '25440'])
        ->assertHasActionErrors(['buy_transfer']);
    expect(GoldPrice::count())->toBe(0);
});
