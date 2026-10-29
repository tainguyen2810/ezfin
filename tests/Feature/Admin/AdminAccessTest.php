<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(RefreshDatabase::class);

function admin_url(string $path = ''): string
{
    return 'http://app.ezfin.test/quan-tri'.$path;
}

it('seeds the four internal roles', function () {
    expect(RoleModel::pluck('name')->sort()->values()->all())
        ->toBe(collect(Role::values())->sort()->values()->all());
});

it('shows the admin login page on the app domain only, with noindex', function () {
    $this->get(admin_url('/login'))
        ->assertOk()
        ->assertSee('Đăng nhập')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->get('http://ezfin.test/quan-tri/login')->assertNotFound();
});

it('redirects guests to the login page', function () {
    $this->get(admin_url())->assertRedirect(admin_url('/login'));
});

it('forbids accounts without an internal role', function () {
    $this->actingAs(User::factory()->create())
        ->get(admin_url())
        ->assertForbidden();
});

it('lets every internal role open the dashboard', function (Role $role) {
    $this->actingAs(User::factory()->admin($role)->create())
        ->get(admin_url())
        ->assertOk();
})->with(Role::cases());

it('requires two-factor setup before using the panel', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::SuperAdmin->value);

    $this->actingAs($user)
        ->get(admin_url())
        ->assertRedirect(admin_url('/multi-factor-authentication/set-up'));
});

it('limits user management to super admins', function (Role $role, int $status) {
    $this->actingAs(User::factory()->admin($role)->create())
        ->get(admin_url('/users'))
        ->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'editor' => [Role::Editor, 403],
    'data operator' => [Role::DataOperator, 403],
    'finance' => [Role::Finance, 403],
]);

it('limits the audit log to super admins', function (Role $role, int $status) {
    $this->actingAs(User::factory()->admin($role)->create())
        ->get(admin_url('/audit-logs'))
        ->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'editor' => [Role::Editor, 403],
]);

it('does not let a super admin delete their own account', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse()
        ->and($admin->can('delete', $other))->toBeTrue()
        ->and($admin->can('update', AuditLog::record('login')))->toBeFalse();
});

it('renders avatars locally instead of calling an external service', function () {
    $user = User::factory()->admin()->create(['name' => 'Nguyễn Thị Đào']);

    $avatar = filament()->getUserAvatarUrl($user);

    expect($avatar)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(str($avatar)->after('base64,')))->toContain('>NĐ<');
});
