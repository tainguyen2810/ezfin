<?php

namespace App\Modules\Personal\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Xác nhận email: không bắt buộc để dùng ứng dụng, chỉ nhắc trên khung app tới khi xác nhận. */
class EmailVerificationController
{
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('personal.dashboard')
            ->with('toast', ['message' => 'Đã xác nhận email. Cảm ơn bạn!', 'type' => 'success']);
    }

    public function send(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('toast', ['message' => 'Đã gửi lại thư xác nhận. Hãy kiểm tra hộp thư (cả mục Spam).', 'type' => 'success']);
    }
}
