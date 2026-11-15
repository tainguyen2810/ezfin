<?php

namespace App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages;

use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use Filament\Resources\Pages\ListRecords;

class ListSyncDrafts extends ListRecords
{
    protected static string $resource = SyncDraftResource::class;
}
