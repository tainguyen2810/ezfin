<?php

namespace App\Modules\Markets\Filament\Resources\GoldPrices\Pages;

use App\Modules\Markets\Actions\RecordQuote;
use App\Modules\Markets\Filament\Resources\GoldPrices\GoldPriceResource;
use App\Modules\Markets\Filament\Resources\QuoteImportAction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use InvalidArgumentException;

class ManageGoldPrices extends ManageRecords
{
    protected static string $resource = GoldPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuoteImportAction::group('gold', GoldPriceResource::canCreate()),
            // Ghi qua RecordQuote: cùng nguồn, loại vàng, thời điểm thì cập nhật thay vì báo lỗi trùng.
            CreateAction::make()->using(function (array $data, Action $action) {
                try {
                    return app(RecordQuote::class)->gold([...array_map(fn ($value) => $value === null ? null : (string) $value, $data)])['quote'];
                } catch (InvalidArgumentException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();
                    $action->halt();
                }
            }),
        ];
    }
}
