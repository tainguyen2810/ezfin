<?php

use App\Models\User;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\CreateCreditCard;
use App\Modules\Compare\Filament\Resources\Loans\Pages\CreateLoan;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\CreateSavingsProduct;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Core\Enums\Role;
use App\Modules\DataSync\Actions\ApproveDraft;
use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Scrapers\Institutions\BankProfile;
use App\Modules\DataSync\Support\Banks;
use App\Modules\DataSync\Support\SourceRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

const BIDV_HOME = <<<'HTML'
<html><head>
<link rel="icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
</head><body>
<header><a class="logo" href="/"><img src="/images/logo-bidv-white.png" alt="BIDV"></a>
<a class="navbar-brand logo" href="/"><img src="/wps/wcm/connect/images/logo-bidv.png?v=2" alt="BIDV"></a></header>
<img src="/images/logo.svg" alt="BIDV">
<img src="/images/google-play-logo.png" alt="Google Play">
<footer><img src="/images/bhtg-logo.png" alt="Bảo hiểm tiền gửi"></footer>
</body></html>
HTML;

/** PNG 1x1 hợp lệ. */
function tinyPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
}

beforeEach(function () {
    Carbon::setTestNow('2026-11-16 05:30:00');
    config(['ezfin.data_sync.delay_ms' => 0]);
    Filament::setCurrentPanel('admin');
    Feature::activate('data_sync');
    fakeBankSites();
    app(SourceRegistry::class)->sync();
});

afterEach(fn () => Carbon::setTestNow());

it('picks the main raster logo, skipping app badges, white versions, SVG and insurance marks', function () {
    $profile = new BankProfile(Banks::get('bidv'));

    expect($profile->findLogo(BIDV_HOME))->toBe('https://bidv.com.vn/wps/wcm/connect/images/logo-bidv.png?v=2')
        ->and($profile->findLogo('<link rel="apple-touch-icon" href="//cdn.bidv.com.vn/touch.png">'))->toBe('https://cdn.bidv.com.vn/touch.png')
        ->and($profile->findLogo('<img src="/logo.svg" alt="BIDV">'))->toBeNull();
});

it('drafts the bank profile, and approving it stores the logo and creates the institution', function () {
    Storage::fake('public');
    Notification::fake();
    $operator = User::factory()->admin(Role::DataOperator)->create();
    fakeBankSites([
        'bidv.com.vn/wps/wcm/connect/images/logo-bidv.png*' => Http::response(tinyPng(), 200, ['Content-Type' => 'image/png']),
        'bidv.com.vn' => Http::response(BIDV_HOME),
        'bidv.com.vn/' => Http::response(BIDV_HOME),
    ]);
    $source = SyncSource::where('key', 'institution.bidv')->sole();

    $result = app(RunSource::class)->handle($source);
    expect($result['status'])->toBe('new')
        ->and($result['draft']->payload['logo_url'])->toBe('https://bidv.com.vn/wps/wcm/connect/images/logo-bidv.png?v=2')
        ->and(FinancialInstitution::where('slug', 'bidv')->exists())->toBeFalse();

    app(ApproveDraft::class)->handle($result['draft'], $operator);

    $bank = FinancialInstitution::where('slug', 'bidv')->sole();
    expect($result['draft']->fresh()->status)->toBe(DraftStatus::Approved)
        ->and($bank->short_name)->toBe('BIDV')
        ->and($bank->website)->toBe('https://bidv.com.vn')
        ->and($bank->logo_path)->toStartWith('institutions/bidv-')->toEndWith('.png');
    Storage::disk('public')->assertExists($bank->logo_path);
});

it('keeps the draft pending when the downloaded logo is not an image', function () {
    Storage::fake('public');
    Notification::fake();
    fakeBankSites([
        'bidv.com.vn/wps/wcm/connect/images/logo-bidv.png*' => Http::response('<html>blocked</html>', 200),
        'bidv.com.vn' => Http::response(BIDV_HOME),
        'bidv.com.vn/' => Http::response(BIDV_HOME),
    ]);
    $draft = app(RunSource::class)->handle(SyncSource::where('key', 'institution.bidv')->sole())['draft'];

    expect(fn () => app(ApproveDraft::class)->handle($draft, User::factory()->admin(Role::DataOperator)->create()))
        ->toThrow(Exception::class, 'không phải ảnh');
    expect($draft->fresh()->status)->toBe(DraftStatus::Pending)
        ->and(FinancialInstitution::where('slug', 'bidv')->exists())->toBeFalse();
});

it('offers an image upload on every product form', function (string $page) {
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());

    Livewire::test($page)->assertFormFieldExists('image_path');
})->with([CreateCreditCard::class, CreateLoan::class, CreateBankAccount::class, CreateSavingsProduct::class]);
