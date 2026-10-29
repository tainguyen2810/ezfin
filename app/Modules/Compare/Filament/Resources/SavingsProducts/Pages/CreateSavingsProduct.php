<?php

namespace App\Modules\Compare\Filament\Resources\SavingsProducts\Pages;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\SavingsProducts\SavingsProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSavingsProduct extends CreateRecord
{
    protected static string $resource = SavingsProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'type' => ProductType::Savings];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
