<?php

namespace App\Modules\DataSync\Filament\Resources\SyncSources;

use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Filament\Resources\SyncSources\Pages\ListSyncSources;
use App\Modules\DataSync\Jobs\RunSourceJob;
use App\Modules\DataSync\Models\SyncSource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Nguồn dữ liệu tự động: tình trạng từng nguồn (thành công/lỗi gần nhất), bật/tắt, tự duyệt, chạy ngay. */
class SyncSourceResource extends Resource
{
    protected static ?string $model = SyncSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu tự động';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'nguon-du-lieu';

    protected static ?string $modelLabel = 'nguồn dữ liệu';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Nguồn dữ liệu';

    public static function getNavigationBadge(): ?string
    {
        $failing = SyncSource::query()->where('is_enabled', true)->get()->filter(fn (SyncSource $source) => $source->health()->needsAttention())->count();

        return $failing > 0 ? (string) $failing : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Nguồn đang lỗi hoặc quá hạn';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('pendingDrafts'))
            ->columns([
                TextColumn::make('name')->label('Nguồn')->searchable()
                    ->description(fn (SyncSource $record) => $record->kind->getLabel()),
                TextColumn::make('health')->label('Tình trạng')->badge()
                    ->state(fn (SyncSource $record) => $record->health()),
                TextColumn::make('last_success_at')->label('Thành công gần nhất')->dateTime('d/m/Y H:i')->placeholder('Chưa có')->sortable(),
                TextColumn::make('last_error')->label('Lỗi gần nhất')->wrap()->limit(160)->placeholder('—')
                    ->description(fn (SyncSource $record) => $record->last_failure_at ? 'Lúc '.$record->last_failure_at->format('d/m/Y H:i').($record->consecutive_failures > 0 ? ", lỗi liên tiếp {$record->consecutive_failures} lần" : ', đã chạy lại được') : null),
                TextColumn::make('pending_drafts_count')->label('Chờ duyệt')->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->tooltip(fn (SyncSource $record) => $record->pending_drafts_count > 0 ? 'Xem bản chờ duyệt của nguồn này' : null)
                    ->url(fn (SyncSource $record) => $record->pending_drafts_count > 0
                        ? SyncDraftResource::getUrl('index', ['filters' => ['source' => ['value' => $record->getKey()]]])
                        : null),
                ToggleColumn::make('is_enabled')->label('Bật')
                    ->disabled(fn (SyncSource $record) => ! static::canEdit($record)),
                ToggleColumn::make('auto_approve')->label('Tự duyệt')
                    ->tooltip('Ghi thẳng vào dữ liệu sản phẩm khi dữ liệu hợp lệ và không có cảnh báo thay đổi lớn')
                    ->disabled(fn (SyncSource $record) => ! static::canEdit($record)),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderBy('kind')->orderBy('name'))
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->groups([Group::make('kind')->label('Loại dữ liệu')->collapsible()])
            ->filters([
                SelectFilter::make('kind')->label('Loại dữ liệu')->options(SyncKind::class)->multiple(),
                SelectFilter::make('state')->label('Tình trạng')
                    ->options(['failing' => 'Đang lỗi', 'ok' => 'Không lỗi', 'pending' => 'Có bản chờ duyệt', 'disabled' => 'Đang tắt'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'failing' => $query->where('consecutive_failures', '>', 0),
                        'ok' => $query->where('consecutive_failures', 0),
                        'pending' => $query->has('pendingDrafts'),
                        'disabled' => $query->where('is_enabled', false),
                        default => $query,
                    }),
                SelectFilter::make('auto_approve')->label('Tự duyệt')->options(['1' => 'Có', '0' => 'Không']),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('runSelected')->label('Chạy ngay các nguồn đã chọn')->icon(Heroicon::OutlinedPlay)
                        ->requiresConfirmation()
                        ->modalDescription('Các nguồn được xếp vào hàng chờ và chạy lần lượt trong vài phút. Kết quả hiện ở cột Tình trạng; nguồn lỗi được báo qua chuông thông báo.')
                        ->action(function (Collection $records) {
                            $records->each(fn (SyncSource $source) => RunSourceJob::dispatch($source->getKey()));
                            Notification::make()->success()->title('Đã xếp '.$records->count().' nguồn vào hàng chờ')
                                ->body('Tải lại trang sau vài phút để xem kết quả.')->send();
                        }),
                    static::bulkToggle('enable', 'Bật các nguồn đã chọn', Heroicon::OutlinedCheckCircle, ['is_enabled' => true]),
                    static::bulkToggle('disable', 'Tắt các nguồn đã chọn', Heroicon::OutlinedPauseCircle, ['is_enabled' => false]),
                    static::bulkToggle('autoOn', 'Bật tự duyệt', Heroicon::OutlinedBolt, ['auto_approve' => true]),
                    static::bulkToggle('autoOff', 'Tắt tự duyệt', Heroicon::OutlinedHandRaised, ['auto_approve' => false]),
                ])->label('Thao tác hàng loạt')->visible(fn () => static::canEditAny()),
            ])
            ->recordActions([
                Action::make('run')->label('Chạy ngay')->icon(Heroicon::OutlinedPlay)
                    ->visible(fn (SyncSource $record) => static::canEdit($record))
                    ->action(function (SyncSource $record, RunSource $run) {
                        $result = $run->handle($record);
                        $notification = Notification::make()->title($record->name)->body($result['message']);
                        ($result['status'] === 'failed' ? $notification->danger()->persistent() : $notification->success())->send();
                    }),
                Action::make('open')->label('Mở trang nguồn')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (SyncSource $record) => $record->url, shouldOpenInNewTab: true),
            ])
            ->emptyStateHeading('Chưa có nguồn nào')
            ->emptyStateDescription('Danh sách nguồn được tạo ở lần chạy đầu tiên của lệnh php artisan ezfin:sync (cron chạy tự động).');
    }

    public static function canEditAny(): bool
    {
        return auth()->user()?->can('update', new SyncSource) ?? false;
    }

    /** @param  array<string, bool>  $values */
    private static function bulkToggle(string $name, string $label, Heroicon $icon, array $values): BulkAction
    {
        return BulkAction::make($name)->label($label)->icon($icon)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($label, $values) {
                $records->each(fn (SyncSource $source) => $source->update($values));
                Notification::make()->success()->title($label.': '.$records->count().' nguồn')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncSources::route('/'),
        ];
    }
}
