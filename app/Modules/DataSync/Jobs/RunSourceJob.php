<?php

namespace App\Modules\DataSync\Jobs;

use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Models\SyncSource;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Chạy một nguồn qua hàng đợi (thao tác hàng loạt trong quản trị), tránh treo trang khi chạy nhiều nguồn. Lỗi được RunSource ghi và báo. */
class RunSourceJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public int $uniqueFor = 900;

    public function __construct(public int $sourceId) {}

    public function uniqueId(): string
    {
        return (string) $this->sourceId;
    }

    public function handle(RunSource $run): void
    {
        if ($source = SyncSource::find($this->sourceId)) {
            $run->handle($source);
        }
    }
}
