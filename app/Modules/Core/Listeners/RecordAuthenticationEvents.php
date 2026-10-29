<?php

namespace App\Modules\Core\Listeners;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/** Ghi nhật ký đăng nhập, đăng nhập sai, đăng xuất. Không bao giờ ghi mật khẩu. */
class RecordAuthenticationEvents
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }

            AuditLog::record('login', $event->user instanceof User ? $event->user : null, userId: $event->user->getAuthIdentifier());
        });

        $events->listen(Failed::class, function (Failed $event) {
            AuditLog::record('login_failed', null, null, [
                'email' => mb_substr((string) ($event->credentials['email'] ?? ''), 0, 255),
            ], userId: $event->user?->getAuthIdentifier());
        });

        $events->listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLog::record('logout', $event->user instanceof User ? $event->user : null, userId: $event->user->getAuthIdentifier());
            }
        });
    }
}
