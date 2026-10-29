<?php

namespace App\Modules\Content\Filament\Resources\ArticleCategories\Pages;

use App\Modules\Content\Filament\Resources\ArticleCategories\ArticleCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticleCategory extends EditRecord
{
    protected static string $resource = ArticleCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
