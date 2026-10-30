<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\DeleteAccount;
use App\Modules\Personal\Actions\ExportTransactions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Trang Tài khoản: thông tin cá nhân, mật khẩu, tải dữ liệu, xóa tài khoản. */
class AccountController
{
    public function edit(Request $request): View
    {
        return view('personal::account.edit', [
            'user' => $request->user(),
            'isStaff' => app(DeleteAccount::class)->isStaff($request->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ], [], ['name' => 'họ tên', 'email' => 'email']);

        $marketing = $request->boolean('marketing');
        $user->forceFill([
            ...$data,
            // Giữ thời điểm đồng ý cũ nếu vẫn đồng ý; bỏ chọn là rút lại đồng ý.
            'marketing_consent_at' => $marketing ? ($user->marketing_consent_at ?? now()) : null,
        ])->save();

        return back()->with('toast', ['message' => 'Đã lưu thông tin tài khoản.', 'type' => 'success']);
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới']);

        $request->user()->forceFill(['password' => $request->input('password')])->save();
        // Đăng xuất các thiết bị khác đang dùng mật khẩu cũ.
        Auth::logoutOtherDevices($request->input('password'));

        return back()->with('toast', ['message' => 'Đã đổi mật khẩu.', 'type' => 'success']);
    }

    public function export(Request $request, ExportTransactions $export): StreamedResponse
    {
        return response()->streamDownload(fn () => $export->write($request->user(), fopen('php://output', 'w')),
            'ezfin-giao-dich-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Request $request, DeleteAccount $delete): RedirectResponse
    {
        $user = $request->user();
        abort_if($delete->isStaff($user), 403, 'Tài khoản quản trị không tự xóa được.');
        $request->validate(['delete_password' => ['required', 'current_password']], [], ['delete_password' => 'mật khẩu']);

        Auth::logout();
        $delete->handle($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('personal.login')->with('status', 'Tài khoản và toàn bộ dữ liệu của bạn đã được xóa.');
    }
}
