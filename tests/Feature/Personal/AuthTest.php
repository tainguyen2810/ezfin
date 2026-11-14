<?php

use App\Models\User;
use App\Modules\Personal\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const APP = 'http://app.ezfin.test';

beforeEach(fn () => Feature::activate('personal'));

it('is hidden while the personal feature is off', function () {
    Feature::deactivate('personal');

    $this->get(APP.'/dang-ky')->assertNotFound();
    $this->get(APP.'/')->assertRedirect(route('home'));
    $this->get('http://ezfin.test/')->assertDontSee('Bắt đầu miễn phí');
});

it('links the public site to sign up when the feature is on', function () {
    $this->get('http://ezfin.test/')->assertSee('Bắt đầu miễn phí')->assertSee(route('personal.register'), false);
    $this->get(APP.'/')->assertRedirect(route('personal.dashboard'));
});

it('registers a customer with consent and signs them in', function () {
    $this->get(APP.'/dang-ky')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->post(APP.'/dang-ky', [
        'name' => 'Nguyễn Văn A', 'email' => 'a@example.com',
        'password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1',
    ])->assertRedirect(route('personal.dashboard'));

    $user = User::firstWhere('email', 'a@example.com');
    expect($user->terms_accepted_at)->not->toBeNull()
        ->and($user->marketing_consent_at)->toBeNull()
        ->and($user->roles)->toBeEmpty();
    $this->assertAuthenticatedAs($user);
});

it('requires accepting the terms and a decent password', function () {
    $this->from(APP.'/dang-ky')->post(APP.'/dang-ky', [
        'name' => 'A', 'email' => 'a@example.com', 'password' => 'ngan', 'password_confirmation' => 'ngan',
    ])->assertSessionHasErrors(['terms', 'password']);

    expect(User::count())->toBe(0);
});

it('signs in, sends guests to the login page and signs out', function () {
    $user = User::factory()->create(['password' => 'matkhau123']);

    $this->get(APP.'/tong-quan')->assertRedirect(route('personal.login'));

    $this->post(APP.'/dang-nhap', ['email' => $user->email, 'password' => 'sai-mat-khau'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(APP.'/dang-nhap', ['email' => $user->email, 'password' => 'matkhau123'])->assertRedirect(route('personal.dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->get(APP.'/tong-quan')->assertOk()->assertSee('Bắt đầu bằng ví đầu tiên');

    $this->post(APP.'/dang-xuat')->assertRedirect(route('personal.login'));
    $this->assertGuest();
});

it('locks the login after five wrong passwords', function () {
    $user = User::factory()->create(['password' => 'matkhau123']);

    foreach (range(1, 5) as $attempt) {
        $this->post(APP.'/dang-nhap', ['email' => $user->email, 'password' => 'sai']);
    }

    $this->post(APP.'/dang-nhap', ['email' => $user->email, 'password' => 'matkhau123'])
        ->assertSessionHasErrors(['email' => 'Bạn đã nhập sai quá nhiều lần. Thử lại sau 15 phút.']);
    $this->assertGuest();
});

it('resets a forgotten password by email without revealing who is registered', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(APP.'/quen-mat-khau', ['email' => 'khong-ton-tai@example.com'])->assertSessionHas('status');
    $this->post(APP.'/quen-mat-khau', ['email' => $user->email])->assertSessionHas('status');

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token, $user) {
        $token = $notification->token;

        return str_starts_with($notification->toMail($user)->actionUrl, APP.'/dat-lai-mat-khau/');
    });

    $this->post(APP.'/dat-lai-mat-khau', [
        'token' => $token, 'email' => $user->email, 'password' => 'matkhaumoi1', 'password_confirmation' => 'matkhaumoi1',
    ])->assertRedirect(route('personal.login'));

    $this->post(APP.'/dang-nhap', ['email' => $user->email, 'password' => 'matkhaumoi1'])->assertRedirect(route('personal.dashboard'));
});

it('emails a verification link after sign-up but lets the customer use the app right away', function () {
    Notification::fake();

    $this->post(APP.'/dang-ky', [
        'name' => 'Nguyễn Văn B', 'email' => 'b@example.com',
        'password' => 'matkhau123', 'password_confirmation' => 'matkhau123', 'terms' => '1',
    ])->assertRedirect(route('personal.dashboard'));

    $user = User::firstWhere('email', 'b@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);

    $this->get(APP.'/tong-quan')->assertOk()->assertSee('Hãy xác nhận email')->assertSee('Gửi lại thư');
});

it('verifies the email from the signed link and hides the reminder', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertRedirect(route('personal.dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($user->fresh())->get(APP.'/tong-quan')->assertOk()->assertDontSee('Hãy xác nhận email');
});

it('rejects a tampered verification link or one for another account', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();

    $this->actingAs($user)->get(APP.'/xac-nhan-email/'.$user->id.'/'.sha1($user->email))->assertForbidden();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $other->id, 'hash' => sha1($other->email)]);
    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email on request', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->from(APP.'/tong-quan')->post(APP.'/xac-nhan-email/gui-lai')->assertRedirect(APP.'/tong-quan');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('asks to verify again after changing the email address', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->put(APP.'/tai-khoan', ['name' => $user->name, 'email' => 'moi@example.com'])->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('writes the password reset email in Vietnamese', function () {
    $user = User::factory()->create();
    $mail = (new ResetPassword('token'))->toMail($user);

    expect($mail->subject)->toBe('Đặt lại mật khẩu EzFin')
        ->and($mail->actionText)->toBe('Đặt lại mật khẩu');
});
