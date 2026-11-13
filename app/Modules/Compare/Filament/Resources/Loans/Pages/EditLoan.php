<?php

namespace App\Modules\Compare\Filament\Resources\Loans\Pages;

use App\Modules\Compare\Filament\Resources\Loans\LoanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoan extends EditRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
