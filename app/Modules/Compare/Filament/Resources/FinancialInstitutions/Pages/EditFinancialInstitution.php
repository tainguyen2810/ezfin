<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages;

use App\Modules\Compare\Filament\Resources\FinancialInstitutions\FinancialInstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialInstitution extends EditRecord
{
    protected static string $resource = FinancialInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
