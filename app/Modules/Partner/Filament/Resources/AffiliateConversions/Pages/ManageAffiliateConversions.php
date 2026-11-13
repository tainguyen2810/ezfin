<?php

namespace App\Modules\Partner\Filament\Resources\AffiliateConversions\Pages;

use App\Modules\Partner\Actions\ImportConversions;
use App\Modules\Partner\Actions\RecordConversion;
use App\Modules\Partner\Filament\Resources\AffiliateConversions\AffiliateConversionResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageAffiliateConversions extends ManageRecords
{
    protected static string $resource = AffiliateConversionResource::class;

    private const SHOWN_ERRORS = 10;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->importAction(),
                Action::make('downloadTemplate')->label('Tải file mẫu')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->action(fn () => response()->streamDownload(
                        fn () => print (ImportConversions::template()),
                        'mau-doi-soat-chuyen-doi.csv',
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    )),
            ])->label('Nhập file đối soát')->icon(Heroicon::OutlinedArrowUpTray)->button()->color('gray')
                ->visible(fn () => AffiliateConversionResource::canCreate()),
        ];
    }

    private function importAction(): Action
    {
        return Action::make('importConversions')->label('Nhập từ CSV')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading('Nhập file đối soát chuyển đổi')
            ->modalDescription('Có lỗi thì không dòng nào được lưu. Nhập lại cùng file chỉ cập nhật trạng thái và hoa hồng, không tạo trùng.')
            ->modalSubmitActionLabel('Nhập')
            ->schema([
                TextInput::make('network')->label('Mạng affiliate')->required()->maxLength(50)
                    ->regex(RecordConversion::NETWORK_PATTERN)
                    ->placeholder('accesstrade')
                    ->helperText('Chữ thường không dấu, giống tên dùng trong URL postback (ví dụ accesstrade, masoffer).'),
                FileUpload::make('file')->label('File CSV (UTF-8)')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->maxSize(5120)
                    ->storeFiles(false)
                    ->required()
                    ->helperText('Cột: '.implode(', ', ImportConversions::COLUMNS).'. status: pending, approved, rejected. commission: số đồng, ví dụ 150000. Ngày: dd/mm/yyyy.'),
            ])
            ->action(function (array $data, Action $action, ImportConversions $import) {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];

                try {
                    $result = $import->handle($data['network'], $file->getRealPath());
                } catch (InvalidArgumentException $exception) {
                    $messages = explode("\n", $exception->getMessage());
                    $more = count($messages) - self::SHOWN_ERRORS;

                    Notification::make()->danger()->persistent()
                        ->title('Không nhập được, chưa dòng nào được lưu')
                        ->body(new HtmlString(collect($messages)->take(self::SHOWN_ERRORS)
                            ->when($more > 0, fn ($lines) => $lines->push("và {$more} lỗi khác."))
                            ->map(fn (string $line) => e($line))->implode('<br>')))
                        ->send();

                    $action->halt();

                    return;
                }

                Notification::make()->success()->title('Đã nhập file đối soát')
                    ->body("Thêm mới {$result['created']}, cập nhật {$result['updated']} chuyển đổi.")
                    ->send();
            });
    }
}
