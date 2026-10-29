<?php

namespace App\Modules\Compare\Filament\Resources\BankAccounts\Pages;

use App\Modules\Compare\Filament\Resources\BankAccounts\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
