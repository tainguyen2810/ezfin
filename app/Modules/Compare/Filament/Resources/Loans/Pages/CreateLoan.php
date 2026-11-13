<?php

namespace App\Modules\Compare\Filament\Resources\Loans\Pages;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'type' => ProductType::Loan];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
