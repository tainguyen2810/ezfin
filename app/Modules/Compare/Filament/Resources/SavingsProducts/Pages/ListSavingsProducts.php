<?php

namespace App\Modules\Compare\Filament\Resources\SavingsProducts\Pages;

use App\Modules\Compare\Actions\ImportSavingsRates;
use App\Modules\Compare\Filament\Resources\SavingsProducts\SavingsProductResource;
use App\Modules\Compare\Support\SavingsRateImportException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListSavingsProducts extends ListRecords
{
    protected static string $resource = SavingsProductResource::class;

    /** Số lỗi hiện trong thông báo; phần còn lại chỉ báo số lượng. */
    private const SHOWN_ERRORS = 10;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                $this->importAction(),
                Action::make('downloadTemplate')->label('Tải file mẫu')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->action(fn () => response()->streamDownload(
                        fn () => print (ImportSavingsRates::template()),
                        'mau-lai-suat-tiet-kiem.csv',
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    )),
            ])->label('Nhập CSV')->icon(Heroicon::OutlinedArrowUpTray)->button()->color('gray')
                ->visible(fn () => SavingsProductResource::canCreate()),
        ];
    }

    private function importAction(): Action
    {
        return Action::make('importRates')->label('Nhập lãi suất từ CSV')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading('Nhập lãi suất từ file CSV')
            ->modalDescription('Kiểm tra toàn bộ file trước khi ghi: có lỗi thì không dòng nào được lưu. Nhập lại cùng file không tạo trùng, dòng đã có sẽ được cập nhật lãi suất.')
            ->modalSubmitActionLabel('Nhập')
            ->schema([
                FileUpload::make('file')->label('File CSV (UTF-8)')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->maxSize(2048)
                    ->storeFiles(false)
                    ->required()
                    ->helperText('Cột: '.implode(', ', ImportSavingsRates::COLUMNS).'. Bắt buộc: '.implode(', ', ImportSavingsRates::REQUIRED_COLUMNS).'.'),
            ])
            ->action(function (array $data, Action $action, ImportSavingsRates $import) {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];

                try {
                    $result = $import->handle($file->getRealPath());
                } catch (SavingsRateImportException $exception) {
                    $messages = $exception->messages();
                    $more = count($messages) - self::SHOWN_ERRORS;

                    Notification::make()->danger()->persistent()
                        ->title('Không nhập được: file có '.count($messages).' lỗi, chưa dòng nào được lưu')
                        ->body(new HtmlString(collect($messages)->take(self::SHOWN_ERRORS)
                            ->when($more > 0, fn ($lines) => $lines->push("và {$more} lỗi khác."))
                            ->map(fn (string $line) => e($line))->implode('<br>')))
                        ->send();

                    $action->halt();

                    return;
                }

                Notification::make()->success()
                    ->title('Đã nhập lãi suất')
                    ->body("Thêm mới {$result['created']} dòng, cập nhật {$result['updated']} dòng, không đổi {$result['unchanged']} dòng.")
                    ->send();
            });
    }
}
