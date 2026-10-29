<?php

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Filament\Pages\Login;
use App\Modules\Core\Models\AuditLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('logs a successful login and the time of last login', function () {
    $user = User::factory()->create(['password' => 'Correct-password-1']);
    $user->assignRole(Role::Editor->value);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'Correct-password-1'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($user->id)
        ->and($user->fresh()->last_login_at)->not->toBeNull()
        ->and(AuditLog::where('event', 'login')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('logs failed logins without the password', function () {
    $user = User::factory()->admin()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'wrong-password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $log = AuditLog::where('event', 'login_failed')->sole();

    expect(auth()->check())->toBeFalse()
        ->and($log->new_values)->toBe(['email' => $user->email])
        ->and(json_encode($log->getAttributes()))->not->toContain('wrong-password');
});

it('locks the login form after five failed attempts', function () {
    $user = User::factory()->admin()->create(['password' => 'Correct-password-1']);
    $component = Livewire::test(Login::class);

    foreach (range(1, 5) as $attempt) {
        $component->fillForm(['email' => $user->email, 'password' => "wrong-{$attempt}"])->call('authenticate');
    }

    // Lần thứ sáu bị chặn kể cả khi mật khẩu đúng.
    $component->fillForm(['email' => $user->email, 'password' => 'Correct-password-1'])
        ->call('authenticate')
        ->assertNotified();

    expect(auth()->check())->toBeFalse()
        ->and(AuditLog::where('event', 'login_failed')->count())->toBe(5)
        ->and(AuditLog::where('event', 'lockout')->count())->toBe(1);
});

it('logs logouts', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    auth()->logout();

    expect(AuditLog::where('event', 'logout')->where('user_id', $user->id)->exists())->toBeTrue();
});
