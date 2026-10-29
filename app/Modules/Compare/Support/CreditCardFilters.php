<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\CardBenefit;
use Illuminate\Http\Request;

/**
 * Bộ lọc trang danh sách thẻ, đọc từ query string. Giá trị lạ bị bỏ qua thay vì báo lỗi,
 * để link cũ hoặc link bị sửa tay vẫn mở được trang.
 */
final readonly class CreditCardFilters
{
    /** Mức phí thường niên tối đa theo lựa chọn. */
    public const FEES = [
        'mien-phi' => ['max' => 0, 'label' => 'Miễn phí'],
        'den-500k' => ['max' => 500_000, 'label' => 'Đến 500.000 đ'],
        'den-1tr' => ['max' => 1_000_000, 'label' => 'Đến 1.000.000 đ'],
    ];

    public const SORTS = [
        'phi-thap' => 'Phí thường niên thấp',
        'hoan-tien-cao' => 'Hoàn tiền cao',
        'thu-nhap-thap' => 'Thu nhập yêu cầu thấp',
        'moi-xac-minh' => 'Mới xác minh',
    ];

    public const DEFAULT_SORT = 'phi-thap';

    public const QUERY_KEYS = ['ngan_hang', 'phi', 'nhu_cau', 'thu_nhap', 'sap_xep'];

    /** Chặn số quá lớn trên URL: 1.000 tỷ đồng/tháng. */
    private const MAX_INCOME = 1_000_000_000_000;

    public function __construct(
        public ?string $institution = null,
        public ?string $fee = null,
        public ?CardBenefit $need = null,
        public ?int $income = null,
        public string $sort = self::DEFAULT_SORT,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Chỉ nhận chuỗi: ?phi[]=... không được làm lỗi trang.
        $text = fn (string $key) => is_string($value = $request->query($key)) ? $value : '';

        $institution = $text('ngan_hang');
        $fee = $text('phi');
        $income = preg_replace('/\D/', '', $text('thu_nhap'));
        $sort = $text('sap_xep');

        return new self(
            institution: preg_match('/^[a-z0-9-]{1,120}$/', $institution) ? $institution : null,
            fee: array_key_exists($fee, self::FEES) ? $fee : null,
            need: CardBenefit::fromSlug($text('nhu_cau')),
            income: $income !== '' && strlen($income) <= 13 && (int) $income > 0 ? min((int) $income, self::MAX_INCOME) : null,
            sort: array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT,
        );
    }

    public function maxAnnualFee(): ?int
    {
        return $this->fee === null ? null : self::FEES[$this->fee]['max'];
    }

    /** Số bộ lọc đang bật (không tính sắp xếp). */
    public function activeCount(): int
    {
        return count(array_filter([$this->institution, $this->fee, $this->need, $this->income], fn ($value) => $value !== null));
    }

    /**
     * Query string chuẩn hóa, bỏ giá trị mặc định.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'ngan_hang' => $this->institution,
            'phi' => $this->fee,
            'nhu_cau' => $this->need?->slug(),
            'thu_nhap' => $this->income,
            'sap_xep' => $this->sort === self::DEFAULT_SORT ? null : $this->sort,
        ], fn ($value) => $value !== null);
    }
}
