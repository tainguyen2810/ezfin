<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\Compare\Actions\PublishProductCatalog;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Banks;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use Throwable;

/**
 * Danh mục sản phẩm một ngân hàng công bố (thẻ tín dụng, khoản vay, tài khoản thanh toán): tên, trang chi tiết, ảnh gốc
 * và các thông số đọc được (phí, thời hạn...). Bộ con đọc trang nguồn và trả danh sách qua item().
 * Duyệt: tạo/cập nhật sản phẩm qua Compare\Actions\PublishProductCatalog, tải ảnh gốc của ngân hàng (không vẽ lại).
 */
abstract class CatalogScraper implements Scraper
{
    /** Phí thường niên hợp lý (đồng); ngoài khoảng này gần như chắc chắn đọc sai. */
    public const MAX_ANNUAL_FEE = 50_000_000;

    public const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

    /** Mã ngắn của ngân hàng trong Banks::ALL. */
    abstract protected function bank(): string;

    /**
     * Đọc danh mục từ trang ngân hàng.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function read(Fetcher $http): array;

    /** @return array{slug: string, name: string, short_name: string, website: string} */
    public function institution(): array
    {
        return Banks::get($this->bank());
    }

    public function key(): string
    {
        return $this->kind()->value.'.'.$this->bank();
    }

    public function name(): string
    {
        return $this->institution()['short_name'];
    }

    /**
     * Một sản phẩm. $key ổn định giữa các lần lấy (thường là đoạn cuối URL chi tiết), dùng làm slug.
     *
     * @param  array<string, mixed>  $details  cột của bảng chi tiết (credit_card_details, loan_details, bank_account_details)
     * @return array<string, mixed>
     */
    protected function item(string $key, string $name, string $url, ?string $image, ?string $summary, array $details): array
    {
        return [
            'key' => $key,
            'name' => trim(preg_replace('/\s+/u', ' ', $name)),
            'url' => $url,
            'image_url' => $image,
            'summary' => $summary === null ? null : mb_substr(trim(preg_replace('/\s+/u', ' ', $summary)), 0, 500),
            'details' => $details,
        ];
    }

    /** Mã ổn định từ đoạn cuối đường dẫn trang chi tiết. */
    protected function slugFromUrl(string $url): string
    {
        return mb_strtolower(basename(parse_url($url, PHP_URL_PATH) ?: $url));
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $items = collect($this->read($http))->unique('key')->sortBy('key')->values()->all();
        if ($items === []) {
            throw ScrapeException::layoutChanged('không đọc được sản phẩm nào trong danh mục.');
        }

        return new ScrapeResult(['items' => $items]);
    }

    public function validate(array $payload): array
    {
        $errors = [];
        foreach ($payload['items'] ?? [] as $item) {
            $label = $item['name'] ?: $item['key'];
            if (blank($item['name'])) {
                $errors[] = "Sản phẩm {$item['key']} không có tên.";
            }
            if (! str_starts_with((string) $item['url'], 'https://')) {
                $errors[] = "{$label}: trang chi tiết phải là https.";
            }
            if ($item['image_url'] !== null && ! str_starts_with($item['image_url'], 'https://')) {
                $errors[] = "{$label}: ảnh phải lấy qua https.";
            }
            $fee = $item['details']['annual_fee'] ?? null;
            if ($fee !== null && ($fee < 0 || $fee > self::MAX_ANNUAL_FEE)) {
                $errors[] = "{$label}: phí thường niên {$fee} đ không hợp lý.";
            }
            $term = $item['details']['max_term_months'] ?? null;
            if ($term !== null && ($term < 1 || $term > 600)) {
                $errors[] = "{$label}: thời hạn vay {$term} tháng không hợp lý.";
            }
        }

        return $errors;
    }

    public function compare(array $payload, ?array $previous): array
    {
        if ($previous === null) {
            return [];
        }

        $warnings = [];
        $now = collect($payload['items'])->keyBy('key');
        $before = collect($previous['items'] ?? [])->keyBy('key');

        foreach ($before->diffKeys($now) as $item) {
            $warnings[] = "{$item['name']} không còn trên trang ngân hàng (sản phẩm trên EzFin không bị xóa, kiểm tra và ẩn nếu đã ngừng).";
        }
        foreach ($now->intersectByKeys($before) as $key => $item) {
            $old = $before[$key]['details']['annual_fee'] ?? null;
            $new = $item['details']['annual_fee'] ?? null;
            if ($old !== null && $new !== null && $old !== $new) {
                $warnings[] = "{$item['name']}: phí thường niên đổi từ {$old} đ thành {$new} đ.";
            }
        }

        return $warnings;
    }

    public function apply(SyncDraft $draft): string
    {
        $items = $draft->payload['items'];
        $before = collect($draft->previousApproved()?->payload['items'] ?? [])->keyBy('key');
        $images = [];
        $failed = 0;

        foreach ($items as $item) {
            if ($item['image_url'] === null || ($before[$item['key']]['image_url'] ?? null) === $item['image_url']) {
                continue;
            }
            try {
                $bytes = app(Fetcher::class)->get($item['image_url'])->body();
                $info = @getimagesizefromstring($bytes);
                if ($info !== false && in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true) && strlen($bytes) <= self::MAX_IMAGE_BYTES) {
                    $images[$item['key']] = [$bytes, image_type_to_extension($info[2], false) === 'jpeg' ? 'jpg' : image_type_to_extension($info[2], false)];

                    continue;
                }
            } catch (Throwable) {
                // Ảnh lỗi không chặn việc ghi thông tin sản phẩm.
            }
            $failed++;
        }

        $result = app(PublishProductCatalog::class)->handle(
            $this->institution(),
            ProductType::from($this->kind()->value),
            array_map(fn (array $item) => array_diff_key($item, ['image_url' => true]), $items),
            $images,
        );

        return "Đã tạo {$result['created']}, cập nhật {$result['updated']} sản phẩm, tải ".count($images).' ảnh'
            .($failed ? ", {$failed} ảnh không tải được" : '').'.';
    }
}
