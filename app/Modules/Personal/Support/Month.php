<?php

namespace App\Modules\Personal\Support;

use Illuminate\Support\Carbon;

/** Tháng trên URL dạng ?thang=2026-10; sai hoặc thiếu thì lấy tháng hiện tại. */
final class Month
{
    public static function fromQuery(mixed $value): Carbon
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)
            ? Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfDay()
            : today()->startOfMonth();
    }
}
