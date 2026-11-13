<?php

namespace App\Modules\Markets\Filament\Resources;

use App\Modules\Markets\Actions\ImportQuotes;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** Nút "Nhập file" và "Tải file mẫu" dùng chung cho trang quản trị tỷ giá và giá vàng. */
final class QuoteImportAction
{
    private const SHOWN_ERRORS = 10;

    /** @param  'fx'|'gold'  $kind */
    public static function group(string $kind, bool $visible): ActionGroup
    {
        $file = $kind === 'fx' ? 'mau-ty-gia.csv' : 'mau-gia-vang.csv';

        return ActionGroup::make([
            Action::make('import')->label('Nhập từ CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading($kind === 'fx' ? 'Nhập bảng tỷ giá' : 'Nhập bảng giá vàng')
                ->modalDescription('Có lỗi thì không dòng nào được lưu. Nhập lại cùng file chỉ cập nhật, không tạo trùng.')
                ->modalSubmitActionLabel('Nhập')
                ->schema([
                    FileUpload::make('file')->label('File CSV (UTF-8)')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(2048)
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Cột: '.implode(', ', array_keys(ImportQuotes::COLUMNS[$kind])).'. Thời điểm: dd/mm/yyyy HH:mm. Giá: 25.440 hoặc 165,32.'),
                ])
                ->action(function (array $data, Action $action, ImportQuotes $import) use ($kind) {
                    /** @var TemporaryUploadedFile $upload */
                    $upload = $data['file'];

                    try {
                        $result = $import->handle($kind, $upload->getRealPath());
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

                    Notification::make()->success()->title('Đã nhập file')
                        ->body("Thêm mới {$result['created']}, cập nhật {$result['updated']} dòng.")
                        ->send();
                }),
            Action::make('downloadTemplate')->label('Tải file mẫu')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->action(fn () => response()->streamDownload(fn () => print (ImportQuotes::template($kind)), $file, ['Content-Type' => 'text/csv; charset=UTF-8'])),
        ])->label('Nhập file')->icon(Heroicon::OutlinedArrowUpTray)->button()->color('gray')->visible($visible);
    }
}
