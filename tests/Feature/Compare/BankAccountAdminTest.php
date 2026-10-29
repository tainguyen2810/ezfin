<?php

use App\Models\User;
use App\Modules\Compare\Enums\BankAccountFeature;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\EditBankAccount;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\ListBankAccounts;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const BANK_ACCOUNTS_ADMIN_URL = 'http://app.ezfin.test/quan-tri/tai-khoan-ngan-hang';

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function bankAccountForm(FinancialInstitution $institution, array $overrides = [], array $account = []): array
{
    return [
        'institution_id' => $institution->id,
        'name' => 'Tài khoản Số Thử',
        'slug' => 'tai-khoan-so-thu',
        'status' => ProductStatus::Draft->value,
        'pros' => [['text' => 'Miễn phí chuyển khoản']],
        'cons' => [['text' => 'Phí rút ATM ngoại mạng']],
        'bankAccount' => [
            'features' => [BankAccountFeature::OnlineOpening->value, BankAccountFeature::FreeTransfer->value],
            'monthly_fee' => '11.000',
            'monthly_fee_note' => 'Miễn phí khi số dư từ 2.000.000 đ',
            'min_balance' => '50.000',
            'transfer_fee' => '0',
            'atm_withdrawal_fee' => '1.100',
            ...$account,
        ],
        ...$overrides,
    ];
}

it('lets data operators and super admins manage accounts, editors only view', function (Role $role, int $list, int $create) {
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get(BANK_ACCOUNTS_ADMIN_URL)->assertStatus($list);
    $this->get(BANK_ACCOUNTS_ADMIN_URL.'/create')->assertStatus($create);
})->with([
    'super admin' => [Role::SuperAdmin, 200, 200],
    'data operator' => [Role::DataOperator, 200, 200],
    'editor' => [Role::Editor, 200, 403],
    'finance' => [Role::Finance, 403, 403],
]);

it('saves a draft account with exact money values', function () {
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());

    Livewire::test(CreateBankAccount::class)
        ->fillForm(bankAccountForm(FinancialInstitution::factory()->create()))
        ->call('create')
        ->assertHasNoFormErrors();

    $product = FinancialProduct::with('bankAccount')->sole();

    expect($product->type)->toBe(ProductType::BankAccount)
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->pros)->toBe(['Miễn phí chuyển khoản'])
        ->and($product->bankAccount->monthly_fee)->toBe('11000.00')
        ->and($product->bankAccount->min_balance)->toBe('50000.00')
        ->and($product->bankAccount->transfer_fee)->toBe('0.00')
        ->and($product->bankAccount->atm_withdrawal_fee)->toBe('1100.00')
        ->and($product->bankAccount->features)->toBe(['online_opening', 'free_transfer']);
});

it('refuses to publish without a source or verification date', function (array $overrides, array $errors) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateBankAccount::class)
        ->fillForm(bankAccountForm(FinancialInstitution::factory()->create(), [
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
]);

it('rejects invalid account numbers', function (array $account, array $errors) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateBankAccount::class)
        ->fillForm(bankAccountForm(FinancialInstitution::factory()->create(), account: $account))
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'missing monthly fee' => [['monthly_fee' => null], ['bankAccount.monthly_fee' => 'required']],
    'negative fee' => [['transfer_fee' => '-1'], ['bankAccount.transfer_fee']],
]);

it('updates account details and audits the change', function () {
    $this->actingAs($admin = User::factory()->admin(Role::DataOperator)->create());

    Livewire::test(CreateBankAccount::class)->fillForm(bankAccountForm(FinancialInstitution::factory()->create()))->call('create');
    $product = FinancialProduct::sole();

    Livewire::test(EditBankAccount::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet(['bankAccount.monthly_fee' => '11.000', 'bankAccount.min_balance' => '50.000'])
        ->fillForm(['bankAccount' => ['monthly_fee' => '0']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->bankAccount()->first()->monthly_fee)->toBe('0.00')
        ->and($product->bankAccount()->first()->min_balance)->toBe('50000.00')
        ->and(AuditLog::where('event', 'updated')->where('user_id', $admin->id)->count())->toBe(1);
});

it('lists bank accounts only', function () {
    $this->actingAs(User::factory()->admin()->create());
    $account = FinancialProduct::factory()->bankAccount()->create();
    $card = FinancialProduct::factory()->create();

    Livewire::test(ListBankAccounts::class)
        ->assertCanSeeTableRecords([$account])
        ->assertCanNotSeeTableRecords([$card]);
});
