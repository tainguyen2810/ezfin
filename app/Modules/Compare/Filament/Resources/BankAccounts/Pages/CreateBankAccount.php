<?php

namespace App\Modules\Compare\Filament\Resources\BankAccounts\Pages;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\BankAccounts\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'type' => ProductType::BankAccount];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
