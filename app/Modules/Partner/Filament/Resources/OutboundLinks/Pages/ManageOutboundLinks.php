<?php

namespace App\Modules\Partner\Filament\Resources\OutboundLinks\Pages;

use App\Modules\Partner\Filament\Resources\OutboundLinks\OutboundLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOutboundLinks extends ManageRecords
{
    protected static string $resource = OutboundLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
