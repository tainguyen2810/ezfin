<?php

namespace App\Modules\Partner\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Trạng thái chuyển đổi theo đối tác: chờ duyệt, đã duyệt (được trả hoa hồng), bị từ chối. */
enum ConversionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Nhận các cách viết phổ biến của mạng affiliate (AccessTrade, MasOffer, Ecomobi...). Không nhận ra thì null. */
    public static function fromPartner(?string $value): ?self
    {
        return match (mb_strtolower(trim((string) $value))) {
            '', 'pending', 'hold', 'on_hold', '0', 'cho-duyet', 'chờ duyệt', 'new' => self::Pending,
            'approved', 'approve', 'confirmed', 'success', '1', 'da-duyet', 'đã duyệt' => self::Approved,
            'rejected', 'reject', 'declined', 'cancelled', 'canceled', 'cancel', '2', 'tu-choi', 'từ chối', 'huy', 'hủy' => self::Rejected,
            default => null,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'gray',
        };
    }
}
