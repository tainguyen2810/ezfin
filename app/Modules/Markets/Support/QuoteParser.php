<?php

namespace App\Modules\Markets\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Throwable;

/** Đọc số tiền và thời điểm trong file nhập tỷ giá, giá vàng. */
final class QuoteParser
{
    public const MAX_VALUE = 999_999_999_999;

    /**
     * Số tiền viết kiểu Việt Nam (25.120 hoặc 165,32) hoặc kiểu máy (25120.50). Trống = null.
     *
     * @throws InvalidArgumentException
     */
    public static function amount(?string $value, string $label): ?string
    {
        $value = str_replace([' ', 'đ', 'VND'], '', trim((string) $value));
        if ($value === '') {
            return null;
        }

        $normalised = match (true) {
            str_contains($value, ',') => str_replace(['.', ','], ['', '.'], $value),
            (bool) preg_match('/^\d{1,3}(\.\d{3})+$/', $value) => str_replace('.', '', $value),
            default => $value,
        };

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalised) || (float) $normalised <= 0 || (float) $normalised > self::MAX_VALUE) {
            throw new InvalidArgumentException("{$label} không hợp lệ: \"{$value}\".");
        }

        return $normalised;
    }

    /**
     * Thời điểm công bố: dd/mm/yyyy HH:ii hoặc yyyy-mm-dd HH:ii (giờ Việt Nam). Không nhận thời điểm ở tương lai.
     *
     * @throws InvalidArgumentException
     */
    public static function time(?string $value): CarbonImmutable
    {
        $value = trim((string) $value);
        $timezone = config('app.timezone');

        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            try {
                $time = CarbonImmutable::createFromFormat('!'.$format, $value, $timezone);
            } catch (Throwable) {
                continue;
            }
            if ($time !== null && $time->format($format) === $value) {
                if ($time->isFuture()) {
                    throw new InvalidArgumentException("Thời điểm \"{$value}\" ở tương lai.");
                }

                return $time;
            }
        }

        throw new InvalidArgumentException("Thời điểm \"{$value}\" không hợp lệ, cần dạng dd/mm/yyyy HH:mm.");
    }
}
