<?php

namespace App\Modules\Core\Jobs;

use App\Modules\Core\Support\Heartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Job rỗng do scheduler đẩy định kỳ: worker xử lý được thì ghi heartbeat của queue. */
class QueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Heartbeat::beat(Heartbeat::QUEUE);
    }
}
