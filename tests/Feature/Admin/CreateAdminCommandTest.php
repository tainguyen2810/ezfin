<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a verified super admin', function () {
    $this->artisan('ezfin:admin', ['--name' => 'Quản trị', '--email' => 'admin@ezfin.vn'])
        ->expectsQuestion('Mật khẩu (tối thiểu 12 ký tự, có chữ hoa, chữ thường, số)', 'Strong-password-2026')
        ->expectsOutputToContain('app.ezfin.test/quan-tri')
        ->assertSuccessful();

    $user = User::where('email', 'admin@ezfin.vn')->sole();

    expect($user->hasRole(Role::SuperAdmin->value))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('rejects weak passwords and unknown roles', function (string $role, string $password) {
    $this->artisan('ezfin:admin', ['--name' => 'A', '--email' => 'a@ezfin.vn', '--role' => $role])
        ->expectsQuestion('Mật khẩu (tối thiểu 12 ký tự, có chữ hoa, chữ thường, số)', $password)
        ->assertFailed();

    expect(User::count())->toBe(0);
})->with([
    'weak password' => ['super-admin', 'short'],
    'unknown role' => ['owner', 'Strong-password-2026'],
]);
