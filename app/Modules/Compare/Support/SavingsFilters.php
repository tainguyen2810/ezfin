<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\SavingsRate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Điều kiện so sánh lãi suất tiết kiệm, đọc từ query string. Giá trị lạ quay về mặc định thay vì báo lỗi.
 */
final readonly class SavingsFilters
{
    /** Kỳ hạn hay gửi nhất (tháng). */
    public const TERMS = [1, 2, 3, 6, 9, 12, 13, 18, 24, 36];

    public const DEFAULT_AMOUNT = 100_000_000;

    public const DEFAULT_TERM = 12;

    public const MIN_AMOUNT = 1_000_000;

    /** Giá trị "mọi hình thức lĩnh lãi" trên URL. */
    public const ANY_PAYMENT = 'tat-ca';

    public const QUERY_KEYS = ['so_tien', 'ky_han', 'kenh', 'linh_lai', 'sap_xep'];

    /** Cách sắp xếp kết quả; mặc định lãi suất (cũng là tiền lãi) cao nhất trước. */
    public const SORTS = [
        'lai-cao' => 'Lãi suất cao nhất',
        'moi-ngan-hang' => 'Cao nhất mỗi ngân hàng',
        'ten-ngan-hang' => 'Tên ngân hàng A–Z',
    ];

    public const DEFAULT_SORT = 'lai-cao';

    public function __construct(
        public int $amount = self::DEFAULT_AMOUNT,
        public int $term = self::DEFAULT_TERM,
        public ?SavingsChannel $channel = null,
        public ?InterestPayment $payment = InterestPayment::EndOfTerm,
        public string $sort = self::DEFAULT_SORT,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Chỉ nhận chuỗi: ?so_tien[]=... không được làm lỗi trang.
        $text = fn (string $key) => is_string($value = $request->query($key)) ? $value : '';

        $amount = preg_replace('/\D/', '', $text('so_tien'));
        $amount = $amount !== '' && strlen($amount) <= 12 ? (int) $amount : self::DEFAULT_AMOUNT;
        $term = (int) $text('ky_han');
        $payment = $text('linh_lai');
        $sort = $text('sap_xep');

        return new self(
            amount: max(self::MIN_AMOUNT, min($amount, SavingsInterest::MAX_AMOUNT)),
            term: in_array($term, self::TERMS, true) ? $term : self::DEFAULT_TERM,
            channel: SavingsChannel::fromSlug($text('kenh')),
            payment: $payment === self::ANY_PAYMENT ? null : (InterestPayment::fromSlug($payment) ?? InterestPayment::EndOfTerm),
            sort: array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT,
        );
    }

    public function isDefault(): bool
    {
        return $this->toQuery() === [];
    }

    /**
     * Query string chuẩn hóa, bỏ giá trị mặc định.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'so_tien' => $this->amount === self::DEFAULT_AMOUNT ? null : $this->amount,
            'ky_han' => $this->term === self::DEFAULT_TERM ? null : $this->term,
            'kenh' => $this->channel?->slug(),
            'linh_lai' => match ($this->payment) {
                InterestPayment::EndOfTerm => null,
                null => self::ANY_PAYMENT,
                default => $this->payment->slug(),
            },
            'sap_xep' => $this->sort === self::DEFAULT_SORT ? null : $this->sort,
        ], fn ($value) => $value !== null);
    }

    /**
     * Sắp xếp kết quả đã xếp theo tiền lãi (cao trước) theo lựa chọn của người dùng.
     *
     * @param  Collection<int, array{rate: SavingsRate, interest: int, total: int}>  $results
     * @return Collection<int, array{rate: SavingsRate, interest: int, total: int}>
     */
    public function arrange(Collection $results): Collection
    {
        return match ($this->sort) {
            'moi-ngan-hang' => $results->unique(fn (array $result) => $result['rate']->product->institution_id)->values(),
            'ten-ngan-hang' => $results->sortBy(fn (array $result) => mb_strtolower($result['rate']->product->institution->short_name ?? ''), SORT_NATURAL)->values(),
            default => $results->values(),
        };
    }
}
