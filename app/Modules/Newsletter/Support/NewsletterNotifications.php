<?php

namespace App\Modules\Newsletter\Support;

use App\Modules\Core\Notifications\Audience;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationType;

/** Thư gửi người nhận tin, sửa được phần chữ trong trang quản trị Thông báo. Bảng lãi suất, lưu ý và chân thư cố định. */
final class NewsletterNotifications
{
    public const CONFIRM = 'newsletter.confirm';

    public const WEEKLY = 'newsletter.weekly';

    public const RATE_ALERT = 'newsletter.rate_alert';

    private const FOOTER = 'chân thư có link chọn loại email, hủy nhận toàn bộ (kèm header hủy một chạm) và trang Quyền riêng tư';

    /** @return list<NotificationType> */
    public static function all(): array
    {
        return [
            new NotificationType(
                key: self::CONFIRM,
                audience: Audience::Subscriber,
                label: 'Xác nhận đăng ký nhận tin',
                description: 'Gửi ngay khi ai đó đăng ký nhận email (xác nhận hai bước). Bắt buộc, không tắt được.',
                templates: [Channel::Mail->value => [
                    'subject' => 'Xác nhận nhận email từ EzFin',
                    'greeting' => 'Xác nhận nhận email từ EzFin',
                    'body' => 'Ai đó (có thể là bạn) vừa đăng ký nhận email từ EzFin bằng địa chỉ này:',
                    'action_label' => 'Xác nhận đăng ký',
                ]],
                variables: ['so_ngay_hieu_luc' => ['Số ngày link còn hiệu lực', '7']],
                canDisable: false,
                fixedParts: 'danh sách loại email đã chọn, câu hiệu lực của link và lời nhắn bỏ qua nếu không đăng ký.',
            ),
            new NotificationType(
                key: self::WEEKLY,
                audience: Audience::Subscriber,
                label: 'Bản tin tuần',
                description: 'Sáng thứ Hai, gửi người đã chọn nhận bản tin: lãi suất cao nhất và bài viết mới.',
                templates: [Channel::Mail->value => [
                    'subject' => 'Bản tin EzFin tuần {ngay}{ghi_chu_lai_12_thang}',
                    'greeting' => 'Bản tin EzFin tuần {ngay}',
                    'body' => '',
                ]],
                variables: [
                    'ngay' => ['Ngày gửi', '24/11/2026'],
                    'lai_12_thang' => ['Lãi 12 tháng cao nhất (trống nếu chưa có)', '6,1%/năm'],
                    'ghi_chu_lai_12_thang' => ['Đoạn ": lãi 12 tháng cao nhất ..." (trống nếu chưa có)', ': lãi 12 tháng cao nhất 6,1%/năm'],
                ],
                fixedParts: 'bảng lãi suất cao nhất theo kỳ hạn, danh sách bài viết mới, lưu ý về lãi suất và '.self::FOOTER.'.',
            ),
            new NotificationType(
                key: self::RATE_ALERT,
                audience: Audience::Subscriber,
                label: 'Báo lãi suất tăng',
                description: 'Gửi khi lãi suất tiết kiệm cao nhất ở kỳ hạn người nhận theo dõi tăng.',
                templates: [Channel::Mail->value => [
                    'subject' => '{tom_tat}',
                    'greeting' => 'Lãi suất tiết kiệm vừa tăng',
                    'body' => 'Mức lãi cao nhất EzFin ghi nhận cho kỳ hạn bạn theo dõi đã tăng:',
                    'action_label' => 'Xem lãi suất {ky_han} tháng các ngân hàng',
                ]],
                variables: [
                    'tom_tat' => ['Tóm tắt (một hay nhiều kỳ hạn)', 'Lãi suất tiết kiệm 12 tháng tăng lên 6,1%/năm'],
                    'ky_han' => ['Kỳ hạn đầu tiên (tháng)', '12'],
                    'lai_moi' => ['Lãi mới của kỳ hạn đầu tiên', '6,1%/năm'],
                    'so_ky_han' => ['Số kỳ hạn tăng', '1'],
                ],
                fixedParts: 'bảng kỳ hạn, lãi trước và hiện tại, ngân hàng; lưu ý "không phải khuyến nghị tài chính" và '.self::FOOTER.'.',
            ),
        ];
    }
}
