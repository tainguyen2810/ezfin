<?php

namespace App\Modules\DataSync\Support;

/** Đọc nhãn kỳ hạn, lãi suất, mức tiền theo cách các ngân hàng Việt Nam hay viết. */
class SavingsParsing
{
    /**
     * Số tháng của nhãn kỳ hạn; null nếu không phải kỳ hạn tháng (tuần, ngày, "trên 36 tháng"...).
     * Ví dụ: "Không kỳ hạn"/"KKH"/"Demand" → 0, "12 tháng"/"12T"/"12-months"/"01 tháng" → 12,
     * "Từ 6 tháng đến dưới 7 tháng" → 6, "Trên 12 tháng đến 13 tháng" → 13.
     */
    public static function term(string $label): ?int
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $label)));

        if (in_array($text, ['không kỳ hạn', 'không kì hạn', 'kkh', 'demand', 'tiết kiệm không kỳ hạn'], true)) {
            return 0;
        }
        if (preg_match('/^0*(\d{1,3})\s*(tháng|t|th|-months?|months?)$/u', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^từ 0*(\d{1,3}) tháng đến dưới 0*\d{1,3} tháng$/u', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^trên 0*\d{1,3} tháng đến 0*(\d{1,3}) tháng$/u', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** "5,4 %", "5.40%", "4,90*" → "5.4000"; ô trống, "-" hoặc chữ → null. */
    public static function rate(string $value): ?string
    {
        $value = trim(str_replace(['%', '*', ' ', "\u{00A0}"], '', $value));
        if (! preg_match('/^\d{1,2}([.,]\d{1,4})?$/', $value)) {
            return null;
        }

        return number_format((float) str_replace(',', '.', $value), 4, '.', '');
    }

    /** Lãi suất dạng tỷ lệ (0.054) → "5.4000". */
    public static function fraction(float|int|string $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value * 100, 4, '.', '');
    }
}
