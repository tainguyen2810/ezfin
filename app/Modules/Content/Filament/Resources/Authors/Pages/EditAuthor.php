<?php

namespace App\Modules\Content\Filament\Resources\Authors\Pages;

use App\Modules\Content\Filament\Resources\Authors\AuthorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuthor extends EditRecord
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
