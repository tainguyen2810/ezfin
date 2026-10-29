<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\BankAccountFeature;
use Illuminate\Http\Request;

/**
 * Bộ lọc trang danh sách tài khoản thanh toán, đọc từ query string. Giá trị lạ bị bỏ qua thay vì báo lỗi.
 */
final readonly class BankAccountFilters
{
    /** Mức phí quản lý tài khoản/tháng tối đa theo lựa chọn. */
    public const FEES = [
        'mien-phi' => ['max' => 0, 'label' => 'Miễn phí'],
        'den-10k' => ['max' => 10_000, 'label' => 'Đến 10.000 đ/tháng'],
    ];

    public const SORTS = [
        'phi-thap' => 'Phí quản lý thấp',
        'so-du-thap' => 'Số dư tối thiểu thấp',
        'chuyen-khoan-re' => 'Phí chuyển khoản thấp',
        'moi-xac-minh' => 'Mới xác minh',
    ];

    public const DEFAULT_SORT = 'phi-thap';

    public const QUERY_KEYS = ['ngan_hang', 'phi', 'tinh_nang', 'sap_xep'];

    public function __construct(
        public ?string $institution = null,
        public ?string $fee = null,
        public ?BankAccountFeature $feature = null,
        public string $sort = self::DEFAULT_SORT,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Chỉ nhận chuỗi: ?phi[]=... không được làm lỗi trang.
        $text = fn (string $key) => is_string($value = $request->query($key)) ? $value : '';

        $institution = $text('ngan_hang');
        $fee = $text('phi');
        $sort = $text('sap_xep');

        return new self(
            institution: preg_match('/^[a-z0-9-]{1,120}$/', $institution) ? $institution : null,
            fee: array_key_exists($fee, self::FEES) ? $fee : null,
            feature: BankAccountFeature::fromSlug($text('tinh_nang')),
            sort: array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT,
        );
    }

    public function maxMonthlyFee(): ?int
    {
        return $this->fee === null ? null : self::FEES[$this->fee]['max'];
    }

    /** Số bộ lọc đang bật (không tính sắp xếp). */
    public function activeCount(): int
    {
        return count(array_filter([$this->institution, $this->fee, $this->feature], fn ($value) => $value !== null));
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return array_filter([
            'ngan_hang' => $this->institution,
            'phi' => $this->fee,
            'tinh_nang' => $this->feature?->slug(),
            'sap_xep' => $this->sort === self::DEFAULT_SORT ? null : $this->sort,
        ], fn ($value) => $value !== null);
    }
}
