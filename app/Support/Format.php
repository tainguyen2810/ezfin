<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Định dạng hiển thị theo chuẩn Việt Nam: 520.000.000 đ, 5,5%, 29/10/2026.
 * Chỉ dùng để hiển thị; tính toán tiền dùng số thập phân chính xác, không dùng kết quả ở đây.
 */
final class Format
{
    public static function money(int|float|string|null $amount, string $currency = 'đ'): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $value = (float) $amount;
        $formatted = number_format(abs(round($value)), 0, ',', '.');

        return ($value < 0 && round($value) != 0 ? '-' : '').$formatted.' '.$currency;
    }

    /** Có dấu + hoặc - đứng trước, dùng cho giao dịch thu/chi và biến động. */
    public static function signedMoney(int|float|string $amount, string $currency = 'đ'): string
    {
        $value = (float) $amount;

        return ($value > 0 ? '+' : '').self::money($value, $currency);
    }

    /** Rút gọn cho thẻ thống kê trên mobile: 28,5 tr, 1,2 tỷ. */
    public static function compactMoney(int|float|string $amount): string
    {
        $value = (float) $amount;
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        return match (true) {
            $abs >= 1_000_000_000 => $sign.self::decimal($abs / 1_000_000_000, 1).' tỷ',
            $abs >= 1_000_000 => $sign.self::decimal($abs / 1_000_000, 1).' tr',
            default => self::money($value),
        };
    }

    public static function percent(int|float|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::decimal((float) $value, $decimals).'%';
    }

    /** Lãi suất theo năm: 5,5%/năm. */
    public static function rate(int|float|string|null $value): string
    {
        return $value === null || $value === '' ? '—' : self::percent($value).'/năm';
    }

    public static function date(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    /** Số thập phân kiểu Việt Nam, bỏ số 0 thừa: 5,50 → 5,5; 6,00 → 6. */
    public static function decimal(float $value, int $decimals = 2): string
    {
        $formatted = number_format($value, $decimals, ',', '.');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }
}
