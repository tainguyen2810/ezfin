<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages;

use App\Modules\Compare\Filament\Resources\FinancialInstitutions\FinancialInstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialInstitution extends CreateRecord
{
    protected static string $resource = FinancialInstitutionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
