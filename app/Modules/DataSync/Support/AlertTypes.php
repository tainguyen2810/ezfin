<?php

namespace App\Modules\DataSync\Support;

use App\Modules\Core\Enums\Role;
use App\Modules\Core\Notifications\Audience;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationType;

/** Các loại thông báo của dữ liệu tự động, sửa được trong trang quản trị Thông báo. Chữ mặc định giữ như trước đây. */
final class AlertTypes
{
    public const FAILED = 'data_sync.failed';

    public const STALE = 'data_sync.stale';

    public const RECOVERED = 'data_sync.recovered';

    public const DRAFT_WAITING = 'data_sync.draft_waiting';

    private const ROLES = [Role::SuperAdmin->value, Role::DataOperator->value];

    private const MAIL_NOTE = 'Email này gửi tới quản trị viên và người vận hành dữ liệu của EzFin.';

    /** @return list<NotificationType> */
    public static function all(): array
    {
        $source = ['ten_nguon' => ['Tên nguồn dữ liệu', 'Lãi suất tiết kiệm ACB'], 'loai_du_lieu' => ['Loại dữ liệu', 'Lãi suất tiết kiệm']];

        return [
            new NotificationType(
                key: self::FAILED,
                audience: Audience::Admin,
                label: 'Nguồn dữ liệu bị lỗi',
                description: 'Khi một nguồn dữ liệu tự động không lấy được dữ liệu (bị chặn, trang đổi giao diện, số liệu vô lý).',
                templates: [
                    Channel::Database->value => ['subject' => 'Không lấy được dữ liệu: {ten_nguon} ({loai_du_lieu})', 'body' => '{loi} (lỗi liên tiếp {so_lan_loi} lần)', 'action_label' => 'Xem nguồn dữ liệu'],
                    Channel::Mail->value => ['subject' => '[EzFin] Không lấy được dữ liệu: {ten_nguon} ({loai_du_lieu})', 'greeting' => 'Cảnh báo dữ liệu tự động', 'body' => "{loi} (lỗi liên tiếp {so_lan_loi} lần)\n\n".self::MAIL_NOTE, 'action_label' => 'Mở trang Nguồn dữ liệu'],
                ],
                variables: $source + [
                    'loi' => ['Nội dung lỗi', 'Không tìm thấy bảng lãi suất trên trang'],
                    'so_lan_loi' => ['Số lần lỗi liên tiếp', '3'],
                ],
                defaultRoles: self::ROLES,
                defaultRepeatHours: 24,
            ),
            new NotificationType(
                key: self::STALE,
                audience: Audience::Admin,
                label: 'Nguồn dữ liệu quá hạn',
                description: 'Khi một nguồn quá lâu chưa lấy được dữ liệu mới (thường do lịch chạy cron dừng).',
                templates: [
                    Channel::Database->value => ['subject' => 'Quá hạn chưa cập nhật: {ten_nguon} ({loai_du_lieu})', 'body' => 'Đã quá {so_gio} giờ chưa lấy được dữ liệu mới ({lan_thanh_cong_cuoi}). Kiểm tra lịch chạy (cron) hoặc nguồn.', 'action_label' => 'Xem nguồn dữ liệu'],
                    Channel::Mail->value => ['subject' => '[EzFin] Quá hạn chưa cập nhật: {ten_nguon} ({loai_du_lieu})', 'greeting' => 'Cảnh báo dữ liệu tự động', 'body' => "Đã quá {so_gio} giờ chưa lấy được dữ liệu mới ({lan_thanh_cong_cuoi}). Kiểm tra lịch chạy (cron) hoặc nguồn.\n\n".self::MAIL_NOTE, 'action_label' => 'Mở trang Nguồn dữ liệu'],
                ],
                variables: $source + [
                    'so_gio' => ['Số giờ cho phép trước khi coi là quá hạn', '36'],
                    'lan_thanh_cong_cuoi' => ['Lần thành công cuối', 'lần thành công cuối lúc 06:00 15/11/2026'],
                ],
                defaultRoles: self::ROLES,
                defaultRepeatHours: 24,
            ),
            new NotificationType(
                key: self::RECOVERED,
                audience: Audience::Admin,
                label: 'Nguồn dữ liệu chạy lại được',
                description: 'Khi một nguồn đã báo lỗi hoặc quá hạn lấy được dữ liệu trở lại.',
                templates: [
                    Channel::Database->value => ['subject' => 'Đã lấy lại được dữ liệu: {ten_nguon}', 'body' => 'Nguồn hoạt động bình thường trở lại.', 'action_label' => 'Xem nguồn dữ liệu'],
                    Channel::Mail->value => ['subject' => '[EzFin] Đã lấy lại được dữ liệu: {ten_nguon}', 'greeting' => 'Cảnh báo dữ liệu tự động', 'body' => "Nguồn hoạt động bình thường trở lại.\n\n".self::MAIL_NOTE, 'action_label' => 'Mở trang Nguồn dữ liệu'],
                ],
                variables: $source,
                defaultRoles: self::ROLES,
            ),
            new NotificationType(
                key: self::DRAFT_WAITING,
                audience: Audience::Admin,
                label: 'Dữ liệu mới chờ duyệt',
                description: 'Khi một nguồn lấy được dữ liệu mới cần quản trị viên xem và duyệt. Mặc định chỉ báo trên chuông.',
                templates: [
                    Channel::Database->value => ['subject' => 'Dữ liệu mới chờ duyệt: {ten_nguon}', 'body' => '{loai_du_lieu}. {ghi_chu_canh_bao}', 'action_label' => 'Xem và duyệt'],
                    Channel::Mail->value => ['subject' => '[EzFin] Dữ liệu mới chờ duyệt: {ten_nguon}', 'greeting' => 'Dữ liệu mới chờ duyệt', 'body' => "{loai_du_lieu}. {ghi_chu_canh_bao}\n\n".self::MAIL_NOTE, 'action_label' => 'Xem và duyệt'],
                ],
                variables: $source + [
                    'so_canh_bao' => ['Số cảnh báo của bản nháp', '2'],
                    'ghi_chu_canh_bao' => ['Câu nhắc khi có cảnh báo (trống nếu không có)', 'Có 2 cảnh báo cần xem kỹ.'],
                ],
                defaultChannels: [Channel::Database],
                defaultRoles: self::ROLES,
            ),
        ];
    }
}
