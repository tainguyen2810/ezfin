<?php

namespace App\Modules\DataSync\Enums;

use Filament\Support\Contracts\HasLabel;

/** Loại dữ liệu một nguồn tự động cung cấp. */
enum SyncKind: string implements HasLabel
{
    case Savings = 'savings';

    public function getLabel(): string
    {
        return match ($this) {
            self::Savings => 'Lãi suất tiết kiệm',
        };
    }

    /** Quá thời gian này (giờ) mà chưa lấy được lần nào thành công thì coi là quá hạn và cảnh báo. */
    public function staleAfterHours(): int
    {
        return match ($this) {
            self::Savings => 48,
        };
    }
}
