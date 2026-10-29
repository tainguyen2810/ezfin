<?php

use App\Models\User;
use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\CreateCreditCard;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\EditCreditCard;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\ListCreditCards;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const CARDS_URL = 'http://app.ezfin.test/quan-tri/the-tin-dung';

beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');
});

function cardForm(FinancialInstitution $institution, array $overrides = [], array $card = []): array
{
    return [
        'institution_id' => $institution->id,
        'name' => 'Thẻ Hoàn Tiền Thử',
        'slug' => 'the-hoan-tien-thu',
        'status' => ProductStatus::Draft->value,
        'pros' => [['text' => 'Hoàn tiền mọi chi tiêu']],
        'cons' => [['text' => 'Phí thường niên cao']],
        'creditCard' => [
            'network' => CardNetwork::Visa->value,
            'benefits' => [CardBenefit::Cashback->value, CardBenefit::Shopping->value],
            'annual_fee' => '1.200.000',
            'min_monthly_income' => '10.000.000',
            'interest_rate' => '30.5',
            'interest_free_days' => 45,
            'cashback_rate' => '5',
            'cashback_monthly_cap' => '600.000',
            ...$card,
        ],
        ...$overrides,
    ];
}

it('lets data operators and super admins manage cards, editors only view', function (Role $role, int $list, int $create) {
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get(CARDS_URL)->assertStatus($list);
    $this->get(CARDS_URL.'/create')->assertStatus($create);
})->with([
    'super admin' => [Role::SuperAdmin, 200, 200],
    'data operator' => [Role::DataOperator, 200, 200],
    'editor' => [Role::Editor, 200, 403],
    'finance' => [Role::Finance, 403, 403],
]);

it('saves a draft card with exact money and rate values', function () {
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());
    $institution = FinancialInstitution::factory()->create();

    Livewire::test(CreateCreditCard::class)
        ->fillForm(cardForm($institution))
        ->call('create')
        ->assertHasNoFormErrors();

    $product = FinancialProduct::with('creditCard')->sole();

    expect($product->type)->toBe(ProductType::CreditCard)
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->published_at)->toBeNull()
        ->and($product->pros)->toBe(['Hoàn tiền mọi chi tiêu'])
        ->and($product->creditCard->annual_fee)->toBe('1200000.00')
        ->and($product->creditCard->min_monthly_income)->toBe('10000000.00')
        ->and($product->creditCard->interest_rate)->toBe('30.5000')
        ->and($product->creditCard->cashback_monthly_cap)->toBe('600000.00')
        ->and($product->creditCard->network)->toBe(CardNetwork::Visa)
        ->and($product->creditCard->benefits)->toBe(['cashback', 'shopping']);
});

it('refuses to publish without a source or verification date', function (array|Closure $overrides, array $errors) {
    $overrides = value($overrides);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateCreditCard::class)
        ->fillForm(cardForm(FinancialInstitution::factory()->create(), [
            'status' => ProductStatus::Published->value,
            'source_url' => 'https://example.com/bieu-phi',
            'verified_at' => today()->subDay()->toDateString(),
            ...$overrides,
        ]))
        ->call('create')
        ->assertHasFormErrors($errors);

    expect(FinancialProduct::count())->toBe(0);
})->with([
    'no source' => [['source_url' => null], ['source_url' => 'required']],
    'no verification date' => [['verified_at' => null], ['verified_at' => 'required']],
    // Tính trong closure để dùng múi giờ của ứng dụng; dataset được dựng trước khi app khởi động (UTC).
    'verification date in the future' => [fn () => ['verified_at' => today()->addDay()->toDateString()], ['verified_at']],
    'source not a url' => [['source_url' => 'bieu phi'], ['source_url' => 'url']],
]);

it('publishes when source and verification date are present, and schedules a review', function () {
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());

    Livewire::test(CreateCreditCard::class)
        ->fillForm(cardForm(FinancialInstitution::factory()->create(), [
            'status' => ProductStatus::Published->value,
            'source_url' => 'https://example.com/bieu-phi',
            'verified_at' => '2026-10-20',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $product = FinancialProduct::sole();

    expect($product->status)->toBe(ProductStatus::Published)
        ->and($product->published_at)->not->toBeNull()
        ->and($product->next_review_at->toDateString())->toBe('2027-01-20');
});

it('blocks publishing at the model level too', function () {
    $product = FinancialProduct::factory()->create();

    expect(fn () => $product->update(['status' => ProductStatus::Published]))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->status)->toBe(ProductStatus::Draft);

    $product->update(['source_url' => 'https://example.com', 'verified_at' => today(), 'status' => ProductStatus::Published]);

    expect($product->fresh()->status)->toBe(ProductStatus::Published);
});

it('keeps a published card published when only the source is removed fails', function () {
    $product = FinancialProduct::factory()->published()->create();

    expect(fn () => $product->update(['source_url' => null]))
        ->toThrow(ValidationException::class)
        ->and($product->fresh()->source_url)->not->toBeNull();
});

it('rejects invalid card numbers', function (array $card, array $errors) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateCreditCard::class)
        ->fillForm(cardForm(FinancialInstitution::factory()->create(), card: $card))
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing annual fee' => [['annual_fee' => null], ['creditCard.annual_fee' => 'required']],
    'negative fee' => [['annual_fee' => '-1'], ['creditCard.annual_fee']],
    'interest over 100%' => [['interest_rate' => '120'], ['creditCard.interest_rate']],
    'cashback over 100%' => [['cashback_rate' => '150'], ['creditCard.cashback_rate']],
]);

it('rejects a duplicate slug', function () {
    $this->actingAs(User::factory()->admin()->create());
    FinancialProduct::factory()->create(['slug' => 'the-hoan-tien-thu']);

    Livewire::test(CreateCreditCard::class)
        ->fillForm(cardForm(FinancialInstitution::factory()->create()))
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('updates card details and audits both the product and its details', function () {
    $this->actingAs($admin = User::factory()->admin(Role::DataOperator)->create());
    $institution = FinancialInstitution::factory()->create();

    Livewire::test(CreateCreditCard::class)->fillForm(cardForm($institution))->call('create');
    $product = FinancialProduct::sole();

    Livewire::test(EditCreditCard::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet(['creditCard.annual_fee' => '1.200.000', 'creditCard.min_monthly_income' => '10.000.000'])
        ->fillForm(['name' => 'Thẻ Hoàn Tiền Mới', 'creditCard' => ['annual_fee' => '0']])
        ->call('save')
        ->assertHasNoFormErrors();

    // Lưu lại form mà không sửa ô tiền thì số tiền giữ nguyên.
    expect($product->fresh()->name)->toBe('Thẻ Hoàn Tiền Mới')
        ->and($product->creditCard()->first()->annual_fee)->toBe('0.00')
        ->and($product->creditCard()->first()->min_monthly_income)->toBe('10000000.00')
        ->and(AuditLog::where('event', 'updated')->where('user_id', $admin->id)->count())->toBe(2);
});

it('lists cards only, flags overdue reviews, and filters by bank', function () {
    $this->actingAs(User::factory()->admin()->create());
    $overdue = FinancialProduct::factory()->published()->create(['next_review_at' => today()->subDay()]);
    $fresh = FinancialProduct::factory()->published()->create();

    Livewire::test(ListCreditCards::class)
        ->assertCanSeeTableRecords([$overdue, $fresh])
        ->filterTable('due_for_review')
        ->assertCanSeeTableRecords([$overdue])
        ->assertCanNotSeeTableRecords([$fresh])
        ->resetTableFilters()
        ->filterTable('institution', $fresh->institution_id)
        ->assertCanSeeTableRecords([$fresh])
        ->assertCanNotSeeTableRecords([$overdue]);

    expect($overdue->isDueForReview())->toBeTrue()->and($fresh->isDueForReview())->toBeFalse();
});

it('does not allow deleting an institution that still has products', function () {
    $product = FinancialProduct::factory()->create();
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $product->institution))->toBeFalse();

    $product->delete();

    expect($admin->can('delete', $product->institution->fresh()))->toBeTrue();
});
