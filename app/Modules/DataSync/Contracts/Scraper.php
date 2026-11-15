<?php

namespace App\Modules\DataSync\Contracts;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;

/** Một bộ lấy dữ liệu tự động cho một nguồn. Đăng ký trong SourceRegistry. */
interface Scraper
{
    /** Mã ổn định, ví dụ "savings.vietcombank". */
    public function key(): string;

    public function kind(): SyncKind;

    /** Tên hiển thị trong quản trị. */
    public function name(): string;

    /** Trang công khai của nguồn (ghi làm nguồn dữ liệu). */
    public function url(): string;

    /** @throws ScrapeException */
    public function scrape(Fetcher $http): ScrapeResult;

    /**
     * Lỗi khiến dữ liệu không dùng được (bảng rỗng, số vô lý). Có lỗi = lần chạy thất bại và gửi cảnh báo.
     *
     * @return list<string>
     */
    public function validate(array $payload): array;

    /**
     * Cảnh báo so với bản đã duyệt trước (thay đổi lớn bất thường). Có cảnh báo thì vẫn tạo bản nháp nhưng không tự duyệt.
     *
     * @return list<string>
     */
    public function compare(array $payload, ?array $previous): array;

    /** Ghi dữ liệu đã duyệt vào bảng sản phẩm qua Action công khai của module sở hữu. Trả mô tả ngắn kết quả. */
    public function apply(SyncDraft $draft): string;
}
