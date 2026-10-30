<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\NetWorthItem;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

it('creates a demo account with months of data the app screens can show', function () {
    $this->artisan('ezfin:demo-data')->expectsOutputToContain('mật khẩu')->assertSuccessful();

    $user = User::where('email', 'demo@ezfin.test')->sole();
    $mine = fn (string $model) => $model::withoutGlobalScopes()->where('user_id', $user->id);
    expect($mine(Wallet::class)->count())->toBe(4)
        ->and($mine(Transaction::class)->count())->toBeGreaterThan(200)
        ->and($mine(Transaction::class)->where('occurred_on', '>', today()->toDateString())->exists())->toBeFalse()
        ->and($mine(Budget::class)->count())->toBe(16)
        ->and($mine(Goal::class)->count())->toBe(4)
        ->and($mine(NetWorthItem::class)->count())->toBe(6);

    Feature::activate('personal');
    $this->actingAs($user);
    foreach (['tong-quan', 'giao-dich', 'ngan-sach', 'muc-tieu', 'tai-san-no', 'bao-cao'] as $page) {
        $this->get("http://app.ezfin.test/$page")->assertOk();
    }
});

it('fills an existing account and only replaces data with --fresh', function () {
    $user = User::factory()->create(['email' => 'tai@example.com']);
    $this->artisan('ezfin:demo-data', ['email' => 'tai@example.com'])->assertSuccessful();
    $count = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count();

    $this->artisan('ezfin:demo-data', ['email' => 'tai@example.com'])->expectsOutputToContain('--fresh')->assertFailed();
    $this->artisan('ezfin:demo-data', ['email' => 'tai@example.com', '--fresh' => true])
        ->expectsConfirmation('Xóa toàn bộ ví, giao dịch, ngân sách, mục tiêu, tài sản & nợ của [tai@example.com]?', 'yes')
        ->assertSuccessful();

    expect(Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe($count)
        ->and(Wallet::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(4);
});

it('never touches other users, staff accounts or production', function () {
    $other = User::factory()->create();
    Wallet::factory()->for($other)->create();
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->assignRole(Role::values()[0]);

    $this->artisan('ezfin:demo-data', ['email' => 'admin@example.com'])->assertFailed();
    $this->artisan('ezfin:demo-data', ['email' => 'nobody@example.com'])->assertFailed();
    $this->artisan('ezfin:demo-data')->assertSuccessful();
    expect(Wallet::withoutGlobalScopes()->where('user_id', $other->id)->count())->toBe(1);

    app()->detectEnvironment(fn () => 'production');
    $this->artisan('ezfin:demo-data', ['email' => $other->email])->assertFailed();
});
