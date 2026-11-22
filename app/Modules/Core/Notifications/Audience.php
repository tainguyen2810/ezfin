<?php

namespace App\Modules\Core\Notifications;

/** Nhóm người nhận của một loại thông báo. */
enum Audience: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Subscriber = 'subscriber';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Quản trị viên',
            self::Customer => 'Khách dùng ứng dụng',
            self::Subscriber => 'Người nhận tin qua email',
        };
    }
}
