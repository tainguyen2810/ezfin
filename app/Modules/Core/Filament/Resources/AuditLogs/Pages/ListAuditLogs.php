<?php

namespace App\Modules\Core\Filament\Resources\AuditLogs\Pages;

use App\Modules\Core\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
