<?php

namespace App\Modules\Content\Filament\Resources\Authors\Pages;

use App\Modules\Content\Filament\Resources\Authors\AuthorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuthors extends ListRecords
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
