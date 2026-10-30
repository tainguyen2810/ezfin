<?php

use App\Models\User;
use App\Modules\Personal\Actions\NetWorth;
use App\Modules\Personal\Enums\NetWorthKind;
use App\Modules\Personal\Models\NetWorthItem;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const NET_WORTH = 'http://app.ezfin.test/tai-san-no';

beforeEach(function () {
    Feature::activate('personal');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function netWorthItem(NetWorthKind $kind, int $amount, array $attributes = []): NetWorthItem
{
    return NetWorthItem::create([...['kind' => $kind, 'name' => $kind->label(), 'amount' => $amount, 'valued_on' => '2026-10-30'], ...$attributes]);
}

it('adds an asset and a debt', function () {
    $this->post(NET_WORTH, ['kind' => 'real_estate', 'name' => 'Căn hộ Thủ Đức', 'amount' => '2.500.000.000', 'valued_on' => '2026-10-30',
        'interest_rate' => '9', 'monthly_payment' => '1.000.000'])->assertRedirect(NET_WORTH);
    $this->post(NET_WORTH, ['kind' => 'mortgage', 'name' => 'Vay mua nhà', 'amount' => '1.200.000.000', 'valued_on' => '2026-10-30',
        'interest_rate' => '9,5', 'monthly_payment' => '15.800.000']);

    $asset = NetWorthItem::firstWhere('kind', 'real_estate');
    $debt = NetWorthItem::firstWhere('kind', 'mortgage');
    expect($asset->amountInt())->toBe(2_500_000_000)
        ->and($asset->interest_rate)->toBeNull()   // tài sản không lưu lãi suất
        ->and($asset->monthly_payment)->toBeNull()
        ->and($debt->interest_rate)->toBe('9.5000')
        ->and((int) $debt->monthly_payment)->toBe(15_800_000)
        ->and($debt->user_id)->toBe($this->user->id);
});

it('validates the form', function () {
    $this->post(NET_WORTH, ['kind' => 'khac', 'name' => '', 'amount' => '', 'valued_on' => ''])
        ->assertSessionHasErrors(['kind', 'name', 'amount', 'valued_on']);
    $this->post(NET_WORTH, ['kind' => 'mortgage', 'name' => 'Vay', 'amount' => '1', 'valued_on' => '2026-10-30', 'interest_rate' => '150'])
        ->assertSessionHasErrors('interest_rate');
});

it('computes net worth from wallets, assets and debts', function () {
    Wallet::factory()->for($this->user)->create(['name' => 'Tài khoản lương', 'opening_balance' => 100_000_000]);
    Wallet::factory()->for($this->user)->create(['name' => 'Giữ hộ', 'opening_balance' => 50_000_000, 'include_in_total' => false]);
    Wallet::factory()->for($this->user)->create(['name' => 'Ví cũ', 'opening_balance' => 70_000_000, 'archived_at' => now()]);
    netWorthItem(NetWorthKind::RealEstate, 2_500_000_000);
    netWorthItem(NetWorthKind::Gold, 300_000_000);
    netWorthItem(NetWorthKind::Mortgage, 1_200_000_000, ['monthly_payment' => 15_800_000, 'interest_rate' => 9.5]);
    netWorthItem(NetWorthKind::CreditCard, 50_000_000);

    $summary = app(NetWorth::class)->handle();
    expect($summary->cash)->toBe(100_000_000)
        ->and($summary->assetTotal)->toBe(2_900_000_000)
        ->and($summary->debtTotal)->toBe(1_250_000_000)
        ->and($summary->total)->toBe(1_650_000_000)
        ->and($summary->debtRatio)->toBe(43)
        ->and($summary->monthlyPayment)->toBe(15_800_000);

    $this->get(NET_WORTH)->assertOk()
        ->assertSee('1.650.000.000 đ')->assertSee('Tài khoản lương')->assertDontSee('Giữ hộ')->assertDontSee('Ví cũ')
        ->assertSee('Vay mua nhà')->assertSee('9,5%/năm')->assertSee('trả 15.800.000 đ/tháng')
        ->assertSee('Tỷ lệ nợ trên tài sản');
});

it('shows a negative net worth in red', function () {
    netWorthItem(NetWorthKind::ConsumerLoan, 20_000_000);

    $this->get(NET_WORTH)->assertOk()->assertSee('-20.000.000 đ')->assertSee('text-expense', false);
});

it('shows only the fields for the chosen side', function () {
    $this->get(NET_WORTH.'/create')->assertOk()->assertSee('Thêm tài sản')->assertSee('Bất động sản')->assertDontSee('Lãi suất')->assertDontSee('Vay mua nhà');
    $this->get(NET_WORTH.'/create?loai=no')->assertOk()->assertSee('Thêm khoản nợ')->assertSee('Lãi suất')->assertSee('Vay mua nhà')->assertDontSee('Bất động sản');
});

it('updates and deletes an item', function () {
    $item = netWorthItem(NetWorthKind::Vehicle, 400_000_000);

    $this->put(NET_WORTH."/{$item->id}", ['kind' => 'vehicle', 'name' => 'Ô tô', 'amount' => '380.000.000', 'valued_on' => '2026-10-30'])
        ->assertRedirect(NET_WORTH);
    expect($item->fresh()->amountInt())->toBe(380_000_000);

    $this->delete(NET_WORTH."/{$item->id}")->assertRedirect(NET_WORTH);
    expect(NetWorthItem::count())->toBe(0);
});

it('keeps assets and debts private to their owner', function () {
    $item = netWorthItem(NetWorthKind::Mortgage, 1_000_000_000, ['name' => 'Khoản vay bí mật']);
    $this->actingAs(User::factory()->create());

    $this->get(NET_WORTH)->assertOk()->assertDontSee('Khoản vay bí mật');
    $this->get(NET_WORTH."/{$item->id}/edit")->assertNotFound();
    $this->put(NET_WORTH."/{$item->id}", ['kind' => 'mortgage', 'name' => 'Sửa', 'amount' => '1', 'valued_on' => '2026-10-30'])->assertNotFound();
    $this->delete(NET_WORTH."/{$item->id}")->assertNotFound();

    expect(app(NetWorth::class)->handle()->debtTotal)->toBe(0)
        ->and(NetWorthItem::withoutGlobalScopes()->find($item->id)->name)->toBe('Khoản vay bí mật');
});

it('is hidden when the flag is off', function () {
    Feature::deactivate('personal');

    $this->get(NET_WORTH)->assertNotFound();
});
