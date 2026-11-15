<?php

namespace App\Modules\DataSync\Filament\Resources\SyncDrafts;

use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages\ListSyncDrafts;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages\ViewSyncDraft;
use App\Modules\DataSync\Models\SyncDraft;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Dữ liệu lấy tự động chờ duyệt. Duyệt = ghi vào dữ liệu sản phẩm; từ chối = bỏ qua bản này. */
class SyncDraftResource extends Resource
{
    protected static ?string $model = SyncDraft::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu tự động';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'cho-duyet';

    protected static ?string $modelLabel = 'bản dữ liệu';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Chờ duyệt';

    public static function getNavigationBadge(): ?string
    {
        $pending = SyncDraft::query()->where('status', DraftStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 1, 'md' => 4])->schema([
                    TextEntry::make('source.name')->label('Nguồn')
                        ->url(fn (SyncDraft $record) => $record->source->url, shouldOpenInNewTab: true),
                    TextEntry::make('status')->label('Trạng thái')->badge(),
                    TextEntry::make('fetched_at')->label('Lấy lúc')->dateTime('d/m/Y H:i'),
                    TextEntry::make('effective_from')->label('Ngày hiệu lực nguồn ghi')->date('d/m/Y')->placeholder('Không ghi (dùng ngày duyệt)'),
                ]),
                TextEntry::make('warnings')->label('Cảnh báo')->listWithLineBreaks()->bulleted()->color('warning')
                    ->visible(fn (SyncDraft $record) => filled($record->warnings)),
                TextEntry::make('review')->label('Người duyệt')
                    ->state(fn (SyncDraft $record) => trim(($record->reviewer?->name ?? 'Tự động').' lúc '.$record->reviewed_at?->format('d/m/Y H:i').($record->review_note ? ". {$record->review_note}" : '')))
                    ->visible(fn (SyncDraft $record) => $record->reviewed_at !== null),
            ]),
            Section::make('Dữ liệu lấy về')
                ->description('Số trong ngoặc là giá trị ở lần đã duyệt trước, nếu khác.')
                ->schema([
                    ViewEntry::make('payload')->hiddenLabel()->view('data-sync::filament.draft-payload'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')->label('Nguồn')->searchable()
                    ->description(fn (SyncDraft $record) => $record->source->kind->getLabel()),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('rows')->label('Số dòng')->state(fn (SyncDraft $record) => count($record->rows())),
                TextColumn::make('warnings')->label('Cảnh báo')->state(fn (SyncDraft $record) => count($record->warnings ?? []))
                    ->badge()->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('fetched_at')->label('Lấy lúc')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reviewer.name')->label('Người duyệt')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('fetched_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(DraftStatus::class)->default(DraftStatus::Pending->value),
                SelectFilter::make('source')->label('Nguồn')->relationship('source', 'name'),
            ])
            ->recordActions([ViewAction::make()->label('Xem và duyệt')])
            ->emptyStateHeading('Không có dữ liệu chờ duyệt')
            ->emptyStateDescription('Khi nguồn có số liệu mới, bản nháp sẽ hiện ở đây.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncDrafts::route('/'),
            'view' => ViewSyncDraft::route('/{record}'),
        ];
    }
}
