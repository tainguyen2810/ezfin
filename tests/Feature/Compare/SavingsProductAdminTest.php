<?php

use App\Models\User;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\CreateSavingsProduct;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\EditSavingsProduct;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\ListSavingsProducts;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const SAVINGS_URL = 'http://app.ezfin.test/quan-tri/tiet-kiem';

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function rateRow(array $overrides = []): array
{
    return [
        'term_months' => 12,
        'channel' => SavingsChannel::Online->value,
        'interest_payment' => InterestPayment::EndOfTerm->value,
        'rate' => '5.2',
        'min_amount' => '',
        'max_amount' => '',
        'effective_from' => '2026-10-01',
        ...$overrides,
    ];
}

it('lets data operators and super admins manage savings rates, editors only view', function (Role $role, int $list, int $create) {
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get(SAVINGS_URL)->assertStatus($list);
    $this->get(SAVINGS_URL.'/create')->assertStatus($create);
})->with([
    'super admin' => [Role::SuperAdmin, 200, 200],
    'data operator' => [Role::DataOperator, 200, 200],
    'editor' => [Role::Editor, 200, 403],
    'finance' => [Role::Finance, 403, 403],
]);

it('creates a savings product with its rate table', function () {
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());
    $institution = FinancialInstitution::factory()->create();

    Livewire::test(CreateSavingsProduct::class)
        ->fillForm([
            'institution_id' => $institution->id,
            'name' => 'Tiết kiệm online',
            'slug' => 'tiet-kiem-online',
            'status' => ProductStatus::Draft->value,
            'savingsRates' => [
                rateRow(),
                rateRow(['term_months' => 6, 'rate' => '4.75', 'channel' => SavingsChannel::Counter->value, 'min_amount' => '1.000.000.000', 'max_amount' => '5.000.000.000']),
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = FinancialProduct::sole();
    $rates = $product->savingsRates()->get()->keyBy('term_months');

    expect($product->type)->toBe(ProductType::Savings)
        ->and($rates)->toHaveCount(2)
        ->and($rates[12]->rate)->toBe('5.2000')
        ->and($rates[12]->min_amount)->toBe('0.00')
        ->and($rates[6]->channel)->toBe(SavingsChannel::Counter)
        ->and($rates[6]->min_amount)->toBe('1000000000.00')
        ->and($rates[6]->max_amount)->toBe('5000000000.00')
        ->and(AuditLog::query()->where('auditable_type', (new SavingsRate)->getMorphClass())->count())->toBe(2);
});

it('rejects duplicate rows and out-of-range values in the rate table', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = FinancialProduct::factory()->savings()->create();

    Livewire::test(EditSavingsProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['savingsRates' => [rateRow(), rateRow(['rate' => '5.5'])]])
        ->call('save')
        ->assertHasFormErrors(['savingsRates']);

    Livewire::test(EditSavingsProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['savingsRates' => [rateRow(['rate' => '52'])]])
        ->call('save')
        ->assertHasFormErrors();

    expect(SavingsRate::count())->toBe(0);
});

it('lists only savings products', function () {
    $this->actingAs(User::factory()->admin()->create());
    $savings = FinancialProduct::factory()->savings()->create();
    $card = FinancialProduct::factory()->create();

    Livewire::test(ListSavingsProducts::class)
        ->assertCanSeeTableRecords([$savings])
        ->assertCanNotSeeTableRecords([$card]);
});

it('imports a CSV from the list page and reports errors without saving', function () {
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());
    FinancialProduct::factory()->savings()->create(['slug' => 'online-a']);

    $bad = UploadedFile::fake()->createWithContent('lai-suat.csv', "san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nonline-a,12,online,5,01/11/2026\nonline-a,x,online,5,01/11/2026\n");
    Livewire::test(ListSavingsProducts::class)
        ->callAction('importRates', ['file' => $bad])
        ->assertNotified('Không nhập được: file có 1 lỗi, chưa dòng nào được lưu');
    expect(SavingsRate::count())->toBe(0);

    $good = UploadedFile::fake()->createWithContent('lai-suat.csv', "san_pham,ky_han,kenh,lai_suat,hieu_luc_tu\nonline-a,12,online,5,01/11/2026\n");
    Livewire::test(ListSavingsProducts::class)
        ->callAction('importRates', ['file' => $good])
        ->assertNotified('Đã nhập lãi suất');
    expect(SavingsRate::count())->toBe(1);
});

it('hides the import for editors', function () {
    $this->actingAs(User::factory()->admin(Role::Editor)->create());

    Livewire::test(ListSavingsProducts::class)->assertActionHidden('importRates');
});
