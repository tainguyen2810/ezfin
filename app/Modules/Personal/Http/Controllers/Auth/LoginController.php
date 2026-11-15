<?php

namespace App\Modules\Personal\Http\Controllers\Auth;

use App\Modules\Core\Actions\AdminSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController
{
    public function create(): View
    {
        return view('personal::auth.login');
    }

    public function store(Request $request, AdminSignIn $admin): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Khóa tạm theo email + IP sau 5 lần sai.
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Bạn đã nhập sai quá nhiều lần. Thử lại sau '.ceil(RateLimiter::availableIn($key) / 60).' phút.',
            ]);
        }

        // Kiểm tra mật khẩu trước khi đăng nhập (cùng sự kiện và thời gian chờ như Auth::attempt),
        // để quyết định có cần bước 2FA của trang quản trị không.
        $guard = Auth::guard('web');
        $valid = app(Timebox::class)->call(function (Timebox $timebox) use ($guard, $credentials): bool {
            $valid = $guard->validate($credentials);

            if ($valid) {
                $timebox->returnEarly();
            }

            return $valid;
        }, (int) config('auth.timebox_duration', 200_000));
        $user = $guard->getLastAttempted();

        if (! $valid || ! $user) {
            RateLimiter::hit($key, 15 * 60);

            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        RateLimiter::clear($key);

        // Quản trị viên đã bật 2FA: không đăng nhập bằng mật khẩu ở đây, chuyển sang form quản trị để nhập mã.
        if ($admin->isAdmin($user) && $admin->needsChallenge($user)) {
            return $admin->redirectToChallenge($user);
        }

        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if ($admin->isAdmin($user)) {
            $request->session()->forget('url.intended');

            return redirect()->to($admin->dashboardUrl());
        }

        return redirect()->intended(route('personal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('personal.login');
    }
}
