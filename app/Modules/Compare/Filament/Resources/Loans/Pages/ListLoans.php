<?php

namespace App\Modules\Compare\Filament\Resources\Loans\Pages;

use App\Modules\Compare\Filament\Resources\Loans\LoanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
