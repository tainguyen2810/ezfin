<?php

namespace App\Modules\Markets\Filament\Resources\FxRates\Pages;

use App\Modules\Markets\Actions\RecordQuote;
use App\Modules\Markets\Filament\Resources\FxRates\FxRateResource;
use App\Modules\Markets\Filament\Resources\QuoteImportAction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use InvalidArgumentException;

class ManageFxRates extends ManageRecords
{
    protected static string $resource = FxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuoteImportAction::group('fx', FxRateResource::canCreate()),
            // Ghi qua RecordQuote: cùng nguồn, ngoại tệ, thời điểm thì cập nhật thay vì báo lỗi trùng.
            CreateAction::make()->using(function (array $data, Action $action) {
                try {
                    return app(RecordQuote::class)->fx([...array_map(fn ($value) => $value === null ? null : (string) $value, $data)])['quote'];
                } catch (InvalidArgumentException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();
                    $action->halt();
                }
            }),
        ];
    }
}
