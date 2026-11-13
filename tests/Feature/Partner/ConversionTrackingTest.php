<?php

use App\Models\User;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Partner\Actions\AffiliateReport;
use App\Modules\Partner\Actions\ImportConversions;
use App\Modules\Partner\Enums\ConversionStatus;
use App\Modules\Partner\Filament\Resources\AffiliateConversions\Pages\ManageAffiliateConversions;
use App\Modules\Partner\Models\AffiliateConversion;
use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const POSTBACK_TOKEN = 'bi-mat-thu-nghiem-0123456789';

beforeEach(function () {
    Feature::activate('partner');
    config(['ezfin.partner.postback_token' => POSTBACK_TOKEN]);

    $bank = FinancialInstitution::factory()->create(['short_name' => 'Alpha']);
    $card = FinancialProduct::factory()->published()->create(['institution_id' => $bank->id, 'name' => 'Thẻ Alpha']);
    $this->link = OutboundLink::factory()->create(['product_id' => $card->id, 'name' => 'Link thẻ Alpha']);
    $this->click = OutboundClick::create(['link_id' => $this->link->id, 'click_id' => str_repeat('A', 24), 'referer_path' => '/the-tin-dung', 'created_at' => now()->subDay()]);
    OutboundClick::create(['link_id' => $this->link->id, 'click_id' => str_repeat('B', 24), 'referer_path' => '/', 'created_at' => now()->subDay()]);
});

function postback(array $query)
{
    return test()->get('http://ezfin.test/postback/accesstrade?'.http_build_query(['token' => POSTBACK_TOKEN, ...$query]));
}

it('records a postback, matches the click and updates on status change without duplicating', function () {
    postback(['click_id' => str_repeat('A', 24), 'transaction_id' => 'DH-1', 'status' => 'pending', 'commission' => '150000'])
        ->assertOk()->assertSee('OK: đã ghi');
    postback(['click_id' => str_repeat('A', 24), 'transaction_id' => 'DH-1', 'status' => 'approved', 'commission' => '180000'])
        ->assertOk()->assertSee('OK: đã cập nhật');

    $conversion = AffiliateConversion::sole();
    expect($conversion->status)->toBe(ConversionStatus::Approved)
        ->and($conversion->commission)->toBe('180000.00')
        ->and($conversion->link_id)->toBe($this->link->id)
        ->and($conversion->source_path)->toBe('/the-tin-dung')
        ->and(AuditLog::where('auditable_type', $conversion->getMorphClass())->count())->toBe(0);
});

it('rejects postbacks with a wrong token, bad data, and is disabled without a token', function () {
    postback(['token' => 'sai', 'click_id' => str_repeat('A', 24), 'status' => 'approved'])->assertForbidden();
    postback(['click_id' => str_repeat('A', 24), 'status' => 'không biết'])->assertStatus(422);
    postback(['click_id' => str_repeat('A', 24), 'status' => 'approved', 'commission' => '150.000,5'])->assertStatus(422);
    postback(['status' => 'approved'])->assertStatus(422);
    expect(AffiliateConversion::count())->toBe(0);

    config(['ezfin.partner.postback_token' => null]);
    postback(['click_id' => str_repeat('A', 24), 'status' => 'approved'])->assertNotFound();

    config(['ezfin.partner.postback_token' => POSTBACK_TOKEN]);
    Feature::deactivate('partner');
    postback(['click_id' => str_repeat('A', 24), 'status' => 'approved'])->assertNotFound();
});

it('keeps conversions without a known click, using the click id as transaction id', function () {
    postback(['click_id' => str_repeat('Z', 24), 'status' => '1'])->assertOk();

    expect(AffiliateConversion::sole())->external_id->toBe(str_repeat('Z', 24))->link_id->toBeNull()->status->toBe(ConversionStatus::Approved);
});

it('imports a reconciliation CSV atomically and re-imports without duplicates', function () {
    $good = tempnam(sys_get_temp_dir(), 'conv');
    file_put_contents($good, "\xEF\xBB\xBFclick_id;transaction_id;status;commission;occurred_at\n"
        .str_repeat('A', 24).";DH-1;approved;150000;05/11/2026\n"
        .str_repeat('B', 24).";DH-2;rejected;0;06/11/2026\n");

    expect(app(ImportConversions::class)->handle('masoffer', $good))->toBe(['created' => 2, 'updated' => 0])
        ->and(app(ImportConversions::class)->handle('masoffer', $good))->toBe(['created' => 0, 'updated' => 2])
        ->and(AffiliateConversion::count())->toBe(2)
        ->and(AffiliateConversion::where('external_id', 'DH-1')->value('occurred_at')->format('d/m/Y'))->toBe('05/11/2026');

    $bad = tempnam(sys_get_temp_dir(), 'conv');
    file_put_contents($bad, "transaction_id,status,commission\nDH-9,approved,100000\nDH-10,lạ,100000\nDH-11,approved,abc\n");

    expect(fn () => app(ImportConversions::class)->handle('masoffer', $bad))
        ->toThrow(InvalidArgumentException::class, 'Dòng 3');
    expect(AffiliateConversion::where('external_id', 'DH-9')->exists())->toBeFalse();
});

it('reports clicks, conversions and commission by bank, link and source page', function () {
    AffiliateConversion::create(['network' => 'x', 'external_id' => '1', 'click_id' => str_repeat('A', 24), 'link_id' => $this->link->id,
        'source_path' => '/the-tin-dung', 'status' => 'approved', 'commission' => 150000, 'occurred_at' => now()]);
    AffiliateConversion::create(['network' => 'x', 'external_id' => '2', 'link_id' => $this->link->id,
        'source_path' => '/', 'status' => 'pending', 'commission' => 90000.5, 'occurred_at' => now()]);
    AffiliateConversion::create(['network' => 'x', 'external_id' => '3', 'link_id' => $this->link->id,
        'status' => 'rejected', 'commission' => 70000, 'occurred_at' => now()]);
    AffiliateConversion::create(['network' => 'x', 'external_id' => '4', 'status' => 'approved', 'commission' => 1, 'occurred_at' => now()->subDays(40)]);

    $report = app(AffiliateReport::class)->handle(today()->subDays(29), today());

    expect($report['totals'])->toBe(['clicks' => 2, 'conversions' => 2, 'rate' => 100.0, 'approved' => 150000, 'pending' => 90001, 'rejected' => 70000])
        ->and($report['byInstitution']->first())->toMatchArray(['label' => 'Alpha', 'clicks' => 2, 'conversions' => 2])
        ->and($report['byLink']->first())->toMatchArray(['label' => 'Link thẻ Alpha', 'product' => 'Thẻ Alpha'])
        ->and($report['bySource']->firstWhere('label', '/the-tin-dung'))->toMatchArray(['clicks' => 1, 'conversions' => 1, 'approved' => 150000])
        ->and($report['byDay'])->toHaveCount(30)
        ->and($report['byDay'][today()->subDay()->toDateString()]['clicks'])->toBe(2);
});

it('shows the performance dashboard to finance and super admins only', function (Role $role, int $status) {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin($role)->create());

    $response = $this->get('http://app.ezfin.test/quan-tri/hieu-qua-doi-tac?ky=7')->assertStatus($status);
    if ($status === 200) {
        $response->assertSee('Hoa hồng đã duyệt')->assertSee('Alpha');
        $this->get('http://app.ezfin.test/quan-tri/chuyen-doi')->assertOk();
    }
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'finance' => [Role::Finance, 200],
    'editor' => [Role::Editor, 403],
]);

it('imports a reconciliation file from the admin with an audit trail', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin(Role::Finance)->create());
    $file = UploadedFile::fake()->createWithContent('doi-soat.csv', "transaction_id,status,commission\nDH-5,approved,200000\n");

    Livewire::test(ManageAffiliateConversions::class)
        ->callAction('importConversions', ['network' => 'accesstrade', 'file' => $file])
        ->assertHasNoActionErrors()
        ->assertNotified('Đã nhập file đối soát');

    $conversion = AffiliateConversion::sole();
    expect(AuditLog::where('auditable_type', $conversion->getMorphClass())->count())->toBe(1);
});
