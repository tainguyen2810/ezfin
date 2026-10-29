<?php

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records who created, updated and deleted a record', function () {
    $actor = User::factory()->admin()->create();
    $this->actingAs($actor);

    $user = User::factory()->create(['name' => 'Nguyễn Văn A']);
    $user->update(['name' => 'Nguyễn Văn B']);
    $user->delete();

    $logs = AuditLog::where('auditable_type', $user->getMorphClass())
        ->where('auditable_id', $user->id)
        ->orderBy('id')
        ->get();

    expect($logs->pluck('event')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($logs->pluck('user_id')->unique()->all())->toBe([$actor->id])
        ->and($logs[1]->old_values)->toBe(['name' => 'Nguyễn Văn A'])
        ->and($logs[1]->new_values)->toBe(['name' => 'Nguyễn Văn B'])
        ->and($logs[2]->old_values['email'])->toBe($user->email);
});

it('never stores passwords or two-factor secrets', function () {
    $user = User::factory()->admin()->create(['password' => 'Old-password-123']);
    $user->update(['password' => 'New-password-456']);

    $logs = AuditLog::where('auditable_id', $user->id)->get();
    $serialized = $logs->map(fn (AuditLog $log) => json_encode([$log->old_values, $log->new_values]))->implode(' ');

    expect($serialized)
        ->not->toContain('password-')
        ->not->toContain('$2y$')
        ->not->toContain((string) $user->app_authentication_secret)
        ->and($logs->firstWhere('event', 'updated')->new_values)->toBe(['password' => '[đã thay đổi]']);
});

it('skips updates that only touch technical fields', function () {
    $user = User::factory()->create();
    $user->forceFill(['remember_token' => 'abc'])->save();

    expect(AuditLog::where('event', 'updated')->count())->toBe(0);
});

it('cannot be edited or deleted', function () {
    $log = AuditLog::record('login');

    expect(fn () => $log->update(['event' => 'logout']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and($log->fresh()->event)->toBe('login');
});
