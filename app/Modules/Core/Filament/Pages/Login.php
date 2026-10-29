<?php

namespace App\Modules\Core\Filament\Pages;

use App\Modules\Core\Models\AuditLog;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;

/** Đăng nhập quản trị: giới hạn 5 lần/phút theo IP (Filament) và ghi audit khi bị khóa tạm. */
class Login extends BaseLogin
{
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        AuditLog::record('lockout', null, null, [
            'email' => mb_substr((string) ($this->data['email'] ?? ''), 0, 255),
            'seconds' => $exception->secondsUntilAvailable,
        ]);

        return parent::getRateLimitedNotification($exception);
    }
}
