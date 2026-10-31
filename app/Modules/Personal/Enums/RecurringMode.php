<?php

namespace App\Modules\Personal\Enums;

/** Cách xử lý khi một khoản định kỳ đến hạn. */
enum RecurringMode: string
{
    /** Tự ghi giao dịch vào ví ngày đến hạn (lương, tiền nhà cố định). */
    case Auto = 'auto';
    /** Nhắc; người dùng bấm "Đã trả" để ghi giao dịch (số tiền có thể đổi, cần xác nhận). */
    case Confirm = 'confirm';
    /** Chỉ nhắc hạn, không ghi giao dịch (vd. trả thẻ tín dụng khi chi tiêu thẻ đã ghi từng lần). */
    case Remind = 'remind';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Tự ghi giao dịch',
            self::Confirm => 'Nhắc, tôi xác nhận rồi mới ghi',
            self::Remind => 'Chỉ nhắc hạn, không ghi giao dịch',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Auto => 'tự ghi',
            self::Confirm => 'chờ xác nhận',
            self::Remind => 'chỉ nhắc',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Auto => 'EzFin ghi giao dịch vào ví lúc 6 giờ sáng ngày đến hạn.',
            self::Confirm => 'Đến hạn, bấm "Đã trả" để ghi giao dịch. Hợp với hóa đơn có số tiền thay đổi.',
            self::Remind => 'Dùng cho hạn trả thẻ tín dụng, khoản vay khi các khoản chi đã ghi riêng. Qua hạn tự chuyển sang kỳ sau.',
        };
    }

    public function recordsTransaction(): bool
    {
        return $this !== self::Remind;
    }
}
