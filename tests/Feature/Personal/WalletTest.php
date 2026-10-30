<?php

use App\Models\User;
use App\Modules\Personal\Enums\WalletType;
use App\Modules\Personal\Models\Wallet;
use App\Modules\Personal\Support\AppMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const WALLETS = 'http://app.ezfin.test/vi';

beforeEach(function () {
    Feature::activate('personal');
    $this->user = User::factory()->create();
});

it('creates a wallet for the signed-in user and shows the total', function () {
    $this->actingAs($this->user)->post(WALLETS, [
        'name' => 'Tài khoản lương', 'type' => 'bank', 'institution' => 'Vietcombank',
        'opening_balance' => '15.000.000', 'include_in_total' => '1',
    ])->assertRedirect(WALLETS);

    $wallet = Wallet::sole();
    expect($wallet->user_id)->toBe($this->user->id)
        ->and($wallet->type)->toBe(WalletType::Bank)
        ->and($wallet->balance())->toBe(15_000_000);

    Wallet::factory()->for($this->user)->create(['name' => 'Tiền giữ hộ', 'opening_balance' => 2_000_000, 'include_in_total' => false]);

    $this->get(WALLETS)->assertOk()->assertSee('Tài khoản lương')->assertSee('15.000.000 đ')->assertSee('Không tính vào tổng');
    $this->get('http://app.ezfin.test/tong-quan')->assertOk()->assertSee('Tổng tài sản')->assertSee('15.000.000 đ');
});

it('validates the wallet form', function () {
    $this->actingAs($this->user)->from(WALLETS.'/create')->post(WALLETS, ['name' => '', 'type' => 'crypto'])
        ->assertSessionHasErrors(['name', 'type']);
});

it('never shows or changes another user\'s wallet', function () {
    $other = Wallet::factory()->create(['name' => 'Ví của người khác']);

    $this->actingAs($this->user);
    $this->get(WALLETS)->assertDontSee('Ví của người khác');
    $this->get(WALLETS."/{$other->id}/edit")->assertNotFound();
    $this->put(WALLETS."/{$other->id}", ['name' => 'Đổi tên', 'type' => 'cash', 'opening_balance' => '0'])->assertNotFound();
    $this->delete(WALLETS."/{$other->id}")->assertNotFound();

    expect(Wallet::withoutGlobalScopes()->find($other->id)->name)->toBe('Ví của người khác');
});

it('archives a wallet instead of deleting it', function () {
    $wallet = Wallet::factory()->for($this->user)->create(['name' => 'Ví cũ']);

    $this->actingAs($this->user)->delete(WALLETS."/{$wallet->id}")->assertRedirect(WALLETS);

    expect($wallet->fresh()->archived_at)->not->toBeNull();
    $this->get(WALLETS)->assertDontSee('Sửa Ví cũ');
});

it('keeps the app menu to screens that exist', function () {
    $this->actingAs($this->user)->get(WALLETS)->assertOk()
        ->assertSee('Tổng quan')->assertSee('Ví &amp; tài khoản', false);

    $routes = collect(AppMenu::items())->pluck('route');
    expect($routes)->not->toBeEmpty()
        ->and($routes->every(fn ($route) => Route::has($route)))->toBeTrue()
        ->and(count(AppMenu::bottom()))->toBeLessThanOrEqual(4);
});
