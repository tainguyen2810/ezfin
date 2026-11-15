<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Support\SourceRegistry;
use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-15 08:05:00');
    config(['ezfin.data_sync.delay_ms' => 0]);
    Filament::setCurrentPanel('admin');
    Feature::activate('data_sync');
    Feature::activate('markets');
    fakeBankSites();
    app(SourceRegistry::class)->sync();
});

afterEach(fn () => Carbon::setTestNow());

it('auto-approves Vietcombank FX rates into the public FX page with source and time', function () {
    $source = SyncSource::where('key', 'fx.vietcombank')->sole();
    expect($source->auto_approve)->toBeTrue();

    $result = app(RunSource::class)->handle($source);

    expect($result['status'])->toBe('approved')
        ->and(FxRate::count())->toBe(8) // DKK không nằm trong danh sách EzFin hiển thị
        ->and(FxRate::where('currency', 'USD')->value('sell'))->toBe('25910.00')
        ->and(FxRate::where('currency', 'USD')->value('quoted_at')->format('d/m/Y H:i'))->toBe('14/11/2026 23:00')
        ->and(FxRate::where('currency', 'KRW')->value('buy_cash'))->toBe('16.63')
        ->and(FxRate::where('currency', 'USD')->value('source'))->toBe('Vietcombank');

    $this->get('http://ezfin.test/ty-gia')->assertOk()->assertSee('Vietcombank')->assertSee('25.910');

    // Chạy lại cùng bảng: không ghi trùng.
    expect(app(RunSource::class)->handle($source->fresh())['status'])->toBe('unchanged')->and(FxRate::count())->toBe(8);
});

it('auto-approves SJC gold prices and rounds per-tael prices', function () {
    $result = app(RunSource::class)->handle(SyncSource::where('key', 'gold.sjc')->sole());

    expect($result['status'])->toBe('approved')
        ->and(GoldPrice::count())->toBe(5)
        ->and(GoldPrice::where('product', 'Vàng SJC 1L, 10L, 1KG')->value('sell'))->toBe('137500000.00')
        ->and(GoldPrice::where('product', 'Nữ trang 99%')->value('buy'))->toBe('127950495.00')
        ->and(GoldPrice::first()->quoted_at->format('d/m/Y H:i'))->toBe('14/11/2026 08:31');
    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['method'] === 'GetCurrentGoldPricesByBranch');
});

it('holds big FX jumps for review and alerts on broken gold data', function () {
    Notification::fake();
    User::factory()->admin(Role::SuperAdmin)->create();
    app(RunSource::class)->handle(SyncSource::where('key', 'fx.vietcombank')->sole());

    Carbon::setTestNow('2026-11-15 10:05:00');
    $html = str_replace(['25910.00', '2026-11-14T23:00:00'], ['28910.00', '2026-11-15T09:30:00'], file_get_contents(base_path('tests/Fixtures/DataSync/vietcombank-fx.html')));
    fakeBankSites([
        'www.vietcombank.com.vn/vi-VN/KHCN/Cong-cu-tien-ich/Ty-gia' => Http::response($html),
        'sjc.com.vn/GoldPrice/Services/PriceService.ashx' => Http::response(['success' => true, 'latestDate' => '08:31 14/11/2026', 'data' => [['TypeName' => 'Vàng SJC 1L, 10L, 1KG', 'BuyValue' => 0, 'SellValue' => 1375]]]),
    ]);

    $fx = app(RunSource::class)->handle(SyncSource::where('key', 'fx.vietcombank')->sole());
    expect($fx['status'])->toBe('new')->and($fx['draft']->status)->toBe(DraftStatus::Pending)
        ->and($fx['draft']->warnings[0])->toContain('USD')
        ->and(FxRate::where('currency', 'USD')->count())->toBe(1);

    $gold = app(RunSource::class)->handle(SyncSource::where('key', 'gold.sjc')->sole());
    expect($gold['status'])->toBe('failed')
        ->and(SyncSource::where('key', 'gold.sjc')->value('last_error'))->toContain('ngoài khoảng hợp lý');
});
