<?php

namespace App\Modules\Compare\Filament\Resources\SavingsProducts\Pages;

use App\Modules\Compare\Filament\Resources\SavingsProducts\SavingsProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavingsProduct extends EditRecord
{
    protected static string $resource = SavingsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
