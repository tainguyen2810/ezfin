<?php

namespace App\Modules\Personal\Imports;

use Illuminate\Support\Carbon;

/** Đọc số tiền và ngày từ ô sao kê theo cách viết thường gặp ở Việt Nam. */
final class StatementValues
{
    /** Số tiền đồng (làm tròn), null nếu ô trống hoặc không phải số. "1.250.000", "1,250,000.00", "(500.000)", "-12.000 VND". */
    public static function amount(mixed $value): ?int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value);
        }
        $text = trim((string) $value);
        if ($text === '' || $text === '-') {
            return null;
        }

        $text = str_replace(['−', '–'], '-', $text);
        $negative = str_starts_with($text, '-') || str_ends_with($text, '-') || (str_starts_with($text, '(') && str_ends_with($text, ')'));
        $text = preg_replace('/[^\d.,]/u', '', $text);
        if ($text === '' || ! preg_match('/\d/', $text)) {
            return null;
        }

        $lastDot = strrpos($text, '.');
        $lastComma = strrpos($text, ',');
        if ($lastDot !== false && $lastComma !== false) {
            // Có cả hai: dấu xuất hiện sau cùng là dấu thập phân.
            $decimal = $lastDot > $lastComma ? '.' : ',';
        } else {
            $separator = $lastDot !== false ? '.' : ($lastComma !== false ? ',' : null);
            // Một loại dấu: lặp lại hoặc đứng trước đúng 3 chữ số là dấu nghìn.
            $decimal = $separator !== null && substr_count($text, $separator) === 1 && ! preg_match('/\\'.$separator.'\d{3}$/', $text) ? $separator : null;
        }

        $thousands = $decimal === '.' ? ',' : '.';
        $text = str_replace($decimal === null ? ['.', ','] : $thousands, '', $text);
        if ($decimal !== null) {
            $text = str_replace($decimal, '.', $text);
        }
        if (! is_numeric($text)) {
            return null;
        }
        $amount = (int) round((float) $text);

        return $negative ? -$amount : $amount;
    }

    /** Ngày giao dịch; $format dmy (Việt Nam) hoặc mdy cho dạng có dấu gạch chéo. Nhận cả số ngày kiểu Excel. */
    public static function date(mixed $value, string $format = 'dmy'): ?Carbon
    {
        if ((is_int($value) || is_float($value)) && $value > 20000 && $value < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value);
        }
        $text = trim((string) $value);

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $text, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})(?!\d)/', $text, $m)) {
            [$first, $second, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            [$day, $month] = $format === 'mdy' ? [$second, $first] : [$first, $second];
            $year = $year < 100 ? 2000 + $year : $year;
        } else {
            return null;
        }

        return checkdate($month, $day, $year) && $year >= 2000 && $year <= 2100 ? Carbon::create($year, $month, $day)->startOfDay() : null;
    }
}
