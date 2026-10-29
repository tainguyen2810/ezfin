<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages;

use App\Modules\Compare\Filament\Resources\FinancialInstitutions\FinancialInstitutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialInstitutions extends ListRecords
{
    protected static string $resource = FinancialInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
