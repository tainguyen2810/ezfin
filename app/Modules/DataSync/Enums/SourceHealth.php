<?php

namespace App\Modules\DataSync\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Tình trạng một nguồn, tính từ lần chạy gần nhất. */
enum SourceHealth: string implements HasColor, HasLabel
{
    case Ok = 'ok';
    case Failing = 'failing';
    case Stale = 'stale';
    case Never = 'never';
    case Disabled = 'disabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ok => 'Hoạt động tốt',
            self::Failing => 'Lỗi, cần sửa',
            self::Stale => 'Quá hạn chưa cập nhật',
            self::Never => 'Chưa chạy',
            self::Disabled => 'Đã tắt',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Failing => 'danger',
            self::Stale => 'warning',
            self::Never, self::Disabled => 'gray',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this, [self::Failing, self::Stale], true);
    }
}
