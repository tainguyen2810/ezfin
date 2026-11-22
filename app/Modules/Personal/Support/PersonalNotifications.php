<?php

namespace App\Modules\Personal\Support;

use App\Modules\Core\Notifications\Audience;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationType;

/** Thư gửi khách dùng ứng dụng, sửa được phần chữ trong trang quản trị Thông báo. Link và bảng số liệu cố định. */
final class PersonalNotifications
{
    public const VERIFY_EMAIL = 'personal.verify_email';

    public const DUE_REMINDER = 'personal.due_reminder';

    /** @return list<NotificationType> */
    public static function all(): array
    {
        return [
            new NotificationType(
                key: self::VERIFY_EMAIL,
                audience: Audience::Customer,
                label: 'Xác nhận email tài khoản',
                description: 'Gửi sau khi khách đăng ký hoặc đổi email. Bắt buộc, không tắt được.',
                templates: [Channel::Mail->value => [
                    'subject' => 'Xác nhận email tài khoản EzFin',
                    'greeting' => 'Chào bạn,',
                    'body' => 'Cảm ơn bạn đã tạo tài khoản EzFin. Bấm nút dưới đây để xác nhận địa chỉ email này là của bạn.',
                    'action_label' => 'Xác nhận email',
                ]],
                variables: ['ten' => ['Tên khách', 'Nguyễn Văn A'], 'so_phut_hieu_luc' => ['Số phút link còn hiệu lực', '60']],
                canDisable: false,
                fixedParts: 'link xác nhận, câu hiệu lực của link và lời nhắn bỏ qua nếu không tạo tài khoản.',
            ),
            new NotificationType(
                key: self::DUE_REMINDER,
                audience: Audience::Customer,
                label: 'Nhắc khoản định kỳ sắp đến hạn',
                description: 'Gửi buổi sáng cho khách đã bật nhắc hạn ở khoản thu chi định kỳ.',
                templates: [Channel::Mail->value => [
                    'subject' => 'Sắp đến hạn: {tom_tat}',
                    'greeting' => 'Chào {ten},',
                    'body' => 'Các khoản định kỳ dưới đây sắp đến hạn:',
                    'action_label' => 'Xem khoản định kỳ',
                ]],
                variables: [
                    'ten' => ['Tên khách', 'Nguyễn Văn A'],
                    'tom_tat' => ['Tóm tắt (một khoản: tên và ngày; nhiều khoản: số khoản)', 'Tiền nhà ngày 25/11/2026'],
                    'so_khoan' => ['Số khoản sắp đến hạn', '1'],
                ],
                fixedParts: 'bảng khoản, ngày đến hạn, số tiền; giải thích "tự ghi" và cách tắt nhắc.',
            ),
        ];
    }
}
