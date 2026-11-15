<?php

namespace App\Modules\DataSync\Support;

use Illuminate\Support\Carbon;

/** Kết quả một lần lấy dữ liệu: payload theo loại nguồn và ngày hiệu lực nguồn công bố (nếu có). */
final class ScrapeResult
{
    public function __construct(
        public readonly array $payload,
        public readonly ?Carbon $effectiveFrom = null,
    ) {}
}
