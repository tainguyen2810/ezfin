<?php

namespace App\Modules\Core\Actions;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;

/**
 * Tài khoản quản trị đăng nhập ở form chung /dang-nhap (đường dẫn quản trị khó nhớ).
 * Chỉ gọi sau khi mật khẩu đã đúng, nên thông báo lỗi không lộ tài khoản nào là quản trị.
 */
class AdminSignIn
{
    /** Session key: email điền sẵn cho form đăng nhập quản trị. */
    public const EMAIL_SESSION_KEY = 'ezfin.admin_login_email';

    public function isAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'canAccessPanel') && $user->canAccessPanel($this->panel());
    }

    /**
     * Tài khoản đã bật xác thực 2 lớp thì phải qua bước nhập mã của trang quản trị,
     * không được đăng nhập chỉ bằng mật khẩu ở form chung.
     */
    public function needsChallenge(Authenticatable $user): bool
    {
        foreach ($this->panel()->getMultiFactorAuthenticationProviders() as $provider) {
            if ($provider->isEnabled($user)) {
                return true;
            }
        }

        return false;
    }

    /** Chuyển sang form đăng nhập quản trị, điền sẵn email, để nhập lại mật khẩu và mã 2 lớp. */
    public function redirectToChallenge(Authenticatable $user): RedirectResponse
    {
        return redirect()->to($this->panel()->getLoginUrl())
            ->with(self::EMAIL_SESSION_KEY, $user->getAttribute('email'));
    }

    /** Trang đầu của quản trị (bắt buộc 2FA mà chưa cài thì Filament tự chuyển sang trang cài đặt). */
    public function dashboardUrl(): string
    {
        return $this->panel()->getUrl();
    }

    private function panel(): Panel
    {
        return Filament::getPanel('admin');
    }
}
