<?php

namespace App\Modules\Content\Filament\Resources\Articles\Pages;

use App\Modules\Content\Filament\Resources\Articles\ArticleResource;
use App\Modules\Content\Filament\Resources\Articles\ArticleWorkflowActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Biên tập viên xem bài đang chờ duyệt hoặc đã xuất bản (không được sửa). */
class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [...ArticleWorkflowActions::make(), EditAction::make()];
    }
}
