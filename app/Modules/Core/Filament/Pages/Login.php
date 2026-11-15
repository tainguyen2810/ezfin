<?php

namespace App\Modules\Core\Filament\Pages;

use App\Modules\Core\Actions\AdminSignIn;
use App\Modules\Core\Models\AuditLog;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;

/** Đăng nhập quản trị: giới hạn 5 lần/phút theo IP (Filament) và ghi audit khi bị khóa tạm. */
class Login extends BaseLogin
{
    /** Đến từ form chung /dang-nhap với tài khoản đã bật 2FA: điền sẵn email, nhắc nhập lại mật khẩu và mã. */
    public function mount(): void
    {
        parent::mount();

        $email = session(AdminSignIn::EMAIL_SESSION_KEY);

        if (is_string($email) && $email !== '') {
            $this->form->fill(['email' => $email]);

            Notification::make()
                ->title('Tài khoản quản trị đã bật xác thực 2 lớp')
                ->body('Nhập lại mật khẩu, sau đó nhập mã từ ứng dụng xác thực.')
                ->info()
                ->send();
        }
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        AuditLog::record('lockout', null, null, [
            'email' => mb_substr((string) ($this->data['email'] ?? ''), 0, 255),
            'seconds' => $exception->secondsUntilAvailable,
        ]);

        return parent::getRateLimitedNotification($exception);
    }
}
