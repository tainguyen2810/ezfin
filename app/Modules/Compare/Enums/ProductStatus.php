<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Discontinued = 'discontinued';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Published => 'Đã xuất bản',
            self::Discontinued => 'Ngừng phát hành',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Discontinued => 'warning',
        };
    }
}
