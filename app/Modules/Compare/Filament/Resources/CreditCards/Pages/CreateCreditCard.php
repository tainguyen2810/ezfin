<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards\Pages;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditCard extends CreateRecord
{
    protected static string $resource = CreditCardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'type' => ProductType::CreditCard];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
