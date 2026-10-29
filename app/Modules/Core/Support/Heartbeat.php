<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** Dấu thời gian cron (scheduler) và queue worker chạy lần cuối, để `ezfin:check` biết chúng còn sống. */
class Heartbeat
{
    public const SCHEDULE = 'schedule';

    public const QUEUE = 'queue';

    public static function beat(string $name): void
    {
        Cache::forever("ezfin:heartbeat:$name", now()->getTimestamp());
    }

    public static function last(string $name): ?Carbon
    {
        $timestamp = Cache::get("ezfin:heartbeat:$name");

        return is_int($timestamp) ? Carbon::createFromTimestamp($timestamp) : null;
    }
}
