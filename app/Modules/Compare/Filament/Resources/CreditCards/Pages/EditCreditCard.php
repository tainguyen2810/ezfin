<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards\Pages;

use App\Modules\Compare\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCreditCard extends EditRecord
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
