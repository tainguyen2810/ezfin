<?php

namespace App\Modules\DataSync\Support;

use RuntimeException;

/** Lỗi khi lấy hoặc đọc dữ liệu từ nguồn. Thông báo bằng tiếng Việt, hiện cho quản trị viên. */
class ScrapeException extends RuntimeException
{
    public static function layoutChanged(string $detail): self
    {
        return new self("Không đọc được dữ liệu, có thể trang nguồn đã đổi cấu trúc: {$detail}");
    }
}
