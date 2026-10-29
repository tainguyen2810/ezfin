<?php

namespace App\Modules\Content\Filament\Resources\ArticleCategories\Pages;

use App\Modules\Content\Filament\Resources\ArticleCategories\ArticleCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleCategory extends CreateRecord
{
    protected static string $resource = ArticleCategoryResource::class;
}
