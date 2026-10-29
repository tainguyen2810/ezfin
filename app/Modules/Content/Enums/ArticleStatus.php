<?php

namespace App\Modules\Content\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Quy trình bài viết: nháp → chờ duyệt → đã xuất bản. Bài bị trả lại quay về nháp kèm ghi chú. */
enum ArticleStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::InReview => 'Chờ duyệt',
            self::Published => 'Đã xuất bản',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'warning',
            self::Published => 'success',
        };
    }
}
