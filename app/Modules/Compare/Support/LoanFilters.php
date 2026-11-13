<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\LoanKind;
use Illuminate\Http\Request;

/**
 * Bộ lọc trang so sánh khoản vay, đọc từ query string. Giá trị lạ bị bỏ qua thay vì báo lỗi.
 * Số tiền và thời hạn vay (nếu có) dùng để loại khoản vay không đáp ứng và ước tính tiền trả tháng đầu.
 */
final readonly class LoanFilters
{
    public const MIN_AMOUNT = 1_000_000;

    public const MAX_AMOUNT = 100_000_000_000;

    /** Thời hạn vay chọn được, tính bằng tháng. */
    public const TERMS = [6, 12, 24, 36, 48, 60, 84, 120, 180, 240, 300, 360];

    public const SORTS = [
        'lai-thap' => 'Lãi ưu đãi thấp',
        'uu-dai-dai' => 'Thời gian ưu đãi dài',
        'han-muc-cao' => 'Hạn mức vay cao',
        'moi-xac-minh' => 'Mới xác minh',
    ];

    public const DEFAULT_SORT = 'lai-thap';

    public const QUERY_KEYS = ['loai', 'ngan_hang', 'so_tien', 'thoi_han', 'sap_xep'];

    public function __construct(
        public ?LoanKind $kind = null,
        public ?string $institution = null,
        public ?int $amount = null,
        public ?int $term = null,
        public string $sort = self::DEFAULT_SORT,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Chỉ nhận chuỗi: ?so_tien[]=... không được làm lỗi trang.
        $text = fn (string $key) => is_string($value = $request->query($key)) ? $value : '';

        $institution = $text('ngan_hang');
        $amount = preg_replace('/\D/', '', $text('so_tien'));
        $amount = $amount !== '' && strlen($amount) <= 12 ? (int) $amount : null;
        $term = $text('thoi_han');
        $sort = $text('sap_xep');

        return new self(
            kind: LoanKind::fromSlug($text('loai')),
            institution: preg_match('/^[a-z0-9-]{1,120}$/', $institution) ? $institution : null,
            amount: $amount !== null && $amount >= self::MIN_AMOUNT && $amount <= self::MAX_AMOUNT ? $amount : null,
            term: ctype_digit($term) && in_array((int) $term, self::TERMS, true) ? (int) $term : null,
            sort: array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT,
        );
    }

    /** Đủ số tiền và thời hạn để ước tính tiền trả hằng tháng. */
    public function canEstimate(): bool
    {
        return $this->amount !== null && $this->term !== null;
    }

    /** Số bộ lọc đang bật (không tính loại vay và sắp xếp). */
    public function activeCount(): int
    {
        return count(array_filter([$this->institution, $this->amount, $this->term], fn ($value) => $value !== null));
    }

    /** Trang đích theo loại vay được lập chỉ mục; thêm điều kiện khác thì không (tránh trang trùng lặp). */
    public function isIndexable(): bool
    {
        return $this->activeCount() === 0 && $this->sort === self::DEFAULT_SORT;
    }

    /** @return array<int, string> */
    public static function termOptions(): array
    {
        return collect(self::TERMS)->mapWithKeys(fn (int $months) => [
            $months => $months % 12 === 0 && $months >= 24 ? ($months / 12).' năm' : "$months tháng",
        ])->all();
    }

    /** @return array<string, string|int> */
    public function toQuery(): array
    {
        return array_filter([
            'loai' => $this->kind?->slug(),
            'ngan_hang' => $this->institution,
            'so_tien' => $this->amount,
            'thoi_han' => $this->term,
            'sap_xep' => $this->sort === self::DEFAULT_SORT ? null : $this->sort,
        ], fn ($value) => $value !== null);
    }
}
