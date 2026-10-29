<?php

namespace App\Modules\Content\Filament\Resources\Articles\Pages;

use App\Modules\Content\Filament\Resources\Articles\ArticleResource;
use App\Modules\Content\Filament\Resources\Articles\ArticleWorkflowActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [...ArticleWorkflowActions::make(), DeleteAction::make()];
    }
}
