<?php

use App\Models\User;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\Author;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use App\Support\Demo\DemoManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    config(['ezfin.demo_manifest' => sys_get_temp_dir().'/ezfin-demo-test-'.getmypid().'.json']);
    DemoManifest::forget();
});
afterEach(fn () => DemoManifest::forget());

it('fills every section with demo data and shows the demo notice', function () {
    $this->artisan('ezfin:demo')->assertSuccessful();

    expect(FinancialInstitution::count())->toBe(14)
        ->and(FinancialProduct::published()->count())->toBe(54)
        ->and(OutboundLink::count())->toBeGreaterThan(30)
        ->and(OutboundClick::count())->toBeGreaterThan(100)
        ->and(Article::count())->toBe(10)
        ->and(Author::count())->toBe(3)
        ->and(User::where('email', 'demo@ezfin.test')->exists())->toBeTrue()
        ->and(Storage::disk('public')->allFiles())->not->toBeEmpty();

    foreach (['/', '/the-tin-dung', '/lai-suat-tiet-kiem', '/tai-khoan-ngan-hang', '/kien-thuc'] as $path) {
        $this->get('http://ezfin.test'.$path)->assertOk()->assertSee('Bản demo');
    }
    $this->get('http://ezfin.test/the-tin-dung')->assertSee('VPBank StepUP');
});

it('refuses to run twice and purges exactly what it created', function () {
    $own = FinancialInstitution::factory()->create();
    $this->artisan('ezfin:demo')->assertSuccessful();
    $this->artisan('ezfin:demo')->assertFailed();

    $this->artisan('ezfin:demo --purge')->assertSuccessful();

    expect(FinancialInstitution::pluck('id')->all())->toBe([$own->id])
        ->and(FinancialProduct::count())->toBe(0)
        ->and(OutboundClick::count())->toBe(0)
        ->and(Article::withTrashed()->count())->toBe(0)
        ->and(Author::count())->toBe(0)
        ->and(AuditLog::where('auditable_type', (new FinancialProduct)->getMorphClass())->count())->toBe(0)
        ->and(User::where('email', 'demo@ezfin.test')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(DemoManifest::active())->toBeFalse();

    $this->get('http://ezfin.test/')->assertDontSee('Bản demo');
});

it('never runs on production', function () {
    app()->detectEnvironment(fn () => 'production');
    $this->artisan('ezfin:demo')->assertFailed();
    expect(FinancialInstitution::count())->toBe(0);
});
