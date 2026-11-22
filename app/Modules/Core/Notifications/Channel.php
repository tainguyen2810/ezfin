<?php

namespace App\Modules\Core\Notifications;

/** Kênh gửi thông báo. Thêm kênh mới (Telegram, Zalo...) = thêm một giá trị ở đây và cách gửi trong SendAdminNotification. */
enum Channel: string
{
    case Database = 'database';
    case Mail = 'mail';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Chuông trong trang quản trị',
            self::Mail => 'Email',
        };
    }

    /** Kênh có tiêu đề thư và lời chào riêng. */
    public function hasGreeting(): bool
    {
        return $this === self::Mail;
    }
}
