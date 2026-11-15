<?php

use App\Models\User;
use App\Modules\Core\Actions\AdminSignIn;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Filament\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const SITE = 'http://ezfin.test';

beforeEach(fn () => Feature::activate('personal'));

function staff(array $attributes = []): User
{
    $user = User::factory()->create(['password' => 'Matkhau-dung-1', ...$attributes]);
    $user->assignRole(Role::Editor->value);

    return $user;
}

it('sends an admin without 2FA from the public login to the admin dashboard', function () {
    $user = staff();

    $this->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => 'Matkhau-dung-1'])
        ->assertRedirect(SITE.'/quan-tri');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
    $this->get(SITE.'/quan-tri')->assertOk();
});

it('ignores an intended customer page and still opens the admin dashboard', function () {
    $user = staff();

    $this->get(SITE.'/ung-dung/tong-quan')->assertRedirect(route('personal.login'));
    $this->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => 'Matkhau-dung-1'])
        ->assertRedirect(SITE.'/quan-tri');
});

it('does not sign in an admin with 2FA by password alone, and hands over to the admin login', function () {
    $user = staff(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);

    $this->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => 'Matkhau-dung-1'])
        ->assertRedirect(SITE.'/quan-tri/login')
        ->assertSessionHas(AdminSignIn::EMAIL_SESSION_KEY, $user->email);

    $this->assertGuest();
    $this->get(SITE.'/quan-tri')->assertRedirect(SITE.'/quan-tri/login');
});

it('prefills the admin login email after the hand-over', function () {
    Filament::setCurrentPanel('admin');
    session()->put(AdminSignIn::EMAIL_SESSION_KEY, 'quan-tri@example.com');

    Livewire::test(Login::class)
        ->assertSet('data.email', 'quan-tri@example.com')
        ->assertNotified('Tài khoản quản trị đã bật xác thực 2 lớp');
});

it('gives the same error for a wrong admin password as for anyone else and keeps the lockout', function () {
    $user = staff();
    $customer = User::factory()->create(['password' => 'Matkhau-dung-1']);

    $adminError = $this->from(SITE.'/dang-nhap')->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => 'sai'])
        ->assertRedirect(SITE.'/dang-nhap')->assertSessionHasErrors('email')->baseResponse->getSession()->get('errors')->first('email');
    $customerError = $this->from(SITE.'/dang-nhap')->post(SITE.'/dang-nhap', ['email' => $customer->email, 'password' => 'sai'])
        ->assertSessionHasErrors('email')->baseResponse->getSession()->get('errors')->first('email');

    expect($adminError)->toBe($customerError)->toBe('Email hoặc mật khẩu không đúng.');

    foreach (range(2, 5) as $attempt) {
        $this->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => "sai-{$attempt}"]);
    }

    // Lần thứ sáu bị khóa tạm kể cả khi mật khẩu đúng.
    $this->post(SITE.'/dang-nhap', ['email' => $user->email, 'password' => 'Matkhau-dung-1'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('still sends customers to the money app', function () {
    $customer = User::factory()->create(['password' => 'Matkhau-dung-1']);

    $this->post(SITE.'/dang-nhap', ['email' => $customer->email, 'password' => 'Matkhau-dung-1'])
        ->assertRedirect(route('personal.dashboard'));
    $this->assertAuthenticatedAs($customer);
});
