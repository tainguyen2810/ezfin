<?php

namespace App\Modules\Newsletter\Actions;

use App\Modules\Newsletter\Mail\ConfirmSubscriptionMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Bước 1 của double opt-in: gửi email chứa link xác nhận. Chưa ghi gì vào database; lựa chọn của người dùng
 * nằm trong link (mã hóa, có hạn) và chỉ được lưu khi họ bấm xác nhận.
 * Mỗi email nhận tối đa MAX_PER_EMAIL thư xác nhận mỗi giờ (chống dùng form để gửi thư rác tới người khác).
 */
class RequestSubscription
{
    public const LINK_DAYS = 7;

    public const MAX_PER_EMAIL = 3;

    /** @return bool true nếu đã gửi thư (false: vượt giới hạn, im lặng để không lộ thông tin) */
    public function handle(string $email, ?int $term, bool $weekly, ?string $sourcePath): bool
    {
        $email = Str::lower(trim($email));

        if (! RateLimiter::attempt('newsletter-email:'.hash('sha256', $email), self::MAX_PER_EMAIL, fn () => true, 3600)) {
            return false;
        }

        $payload = Crypt::encryptString(json_encode([
            'e' => $email,
            't' => $term,
            'w' => $weekly,
            's' => $sourcePath ? Str::limit($sourcePath, 250, '') : null,
            'c' => now()->timestamp,
            'x' => now()->addDays(self::LINK_DAYS)->timestamp,
        ]));

        Mail::to($email)->queue(new ConfirmSubscriptionMail(route('newsletter.confirm', $payload), $term, $weekly));

        return true;
    }
}
