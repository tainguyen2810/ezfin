<?php

namespace App\Modules\DataSync\Enums;

use Filament\Support\Contracts\HasLabel;

/** Loại dữ liệu một nguồn tự động cung cấp. */
enum SyncKind: string implements HasLabel
{
    case Savings = 'savings';
    case Fx = 'fx';
    case Gold = 'gold';
    case Institution = 'institution';
    case CreditCard = 'credit_card';
    case Loan = 'loan';
    case BankAccount = 'bank_account';

    public function getLabel(): string
    {
        return match ($this) {
            self::Savings => 'Lãi suất tiết kiệm',
            self::Fx => 'Tỷ giá ngoại tệ',
            self::Gold => 'Giá vàng',
            self::Institution => 'Hồ sơ ngân hàng, logo',
            self::CreditCard => 'Thẻ tín dụng',
            self::Loan => 'Khoản vay',
            self::BankAccount => 'Tài khoản thanh toán',
        };
    }

    /**
     * Nguồn mới có bật tự duyệt không. Tỷ giá, giá vàng đổi nhiều lần mỗi ngày và trang công khai luôn ghi nguồn + giờ
     * công bố, nên tự duyệt khi số liệu hợp lệ (thay đổi bất thường vẫn chờ duyệt). Lãi suất, hồ sơ ngân hàng, danh mục sản phẩm luôn chờ người duyệt.
     */
    public function autoApproveByDefault(): bool
    {
        return in_array($this, [self::Fx, self::Gold], true);
    }

    /** Quá thời gian này (giờ) mà chưa lấy được lần nào thành công thì coi là quá hạn và cảnh báo. */
    public function staleAfterHours(): int
    {
        return match ($this) {
            self::Savings => 48,
            // Cuối tuần, ngày lễ nguồn có thể không công bố mới nhưng vẫn đọc được bảng cũ (vẫn tính là thành công).
            self::Fx, self::Gold => 26,
            self::Institution, self::CreditCard, self::Loan, self::BankAccount => 24 * 8,
        };
    }

    /** Loại danh mục sản phẩm (thẻ, vay, tài khoản) ghi qua Compare\Actions\PublishProductCatalog. */
    public function isCatalog(): bool
    {
        return in_array($this, [self::CreditCard, self::Loan, self::BankAccount], true);
    }
}
