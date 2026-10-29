<?php

namespace App\Modules\Content\Filament\Resources\Authors\Pages;

use App\Modules\Content\Filament\Resources\Authors\AuthorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuthor extends CreateRecord
{
    protected static string $resource = AuthorResource::class;
}
