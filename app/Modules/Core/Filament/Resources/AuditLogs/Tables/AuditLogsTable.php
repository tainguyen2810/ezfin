<?php

namespace App\Modules\Core\Filament\Resources\AuditLogs\Tables;

use App\Modules\Core\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public const EVENTS = ['created', 'updated', 'deleted', 'login', 'login_failed', 'logout', 'lockout'];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Người thực hiện')->placeholder('Hệ thống')->searchable(),
                TextColumn::make('event')->label('Sự kiện')->badge()
                    ->formatStateUsing(fn ($state, AuditLog $record) => $record->eventLabel())
                    ->color(fn (string $state) => match ($state) {
                        'deleted', 'login_failed', 'lockout' => 'danger',
                        'created' => 'success',
                        'updated' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('auditable_type')->label('Đối tượng')
                    ->formatStateUsing(fn ($state, AuditLog $record) => (method_exists($state, 'auditSubjectLabel') ? $state::auditSubjectLabel() : class_basename($state)).' #'.$record->auditable_id)
                    ->placeholder('—'),
                TextColumn::make('ip_address')->label('IP')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')->label('Sự kiện')
                    ->options(collect(self::EVENTS)->mapWithKeys(fn ($event) => [$event => (new AuditLog(['event' => $event]))->eventLabel()])),
                SelectFilter::make('user')->label('Người thực hiện')->relationship('user', 'name')->searchable(),
            ])
            ->recordActions([
                Action::make('details')->label('Chi tiết')->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (AuditLog $record) => $record->eventLabel())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s'),
                            TextEntry::make('user.name')->label('Người thực hiện')->placeholder('Hệ thống'),
                            TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                            TextEntry::make('user_agent')->label('Trình duyệt')->placeholder('—'),
                        ]),
                        KeyValueEntry::make('old_values')->state(fn (AuditLog $record) => self::flatten($record->old_values))
                            ->visible(fn (AuditLog $record) => filled($record->old_values))->label('Giá trị cũ')->placeholder('—'),
                        KeyValueEntry::make('new_values')->state(fn (AuditLog $record) => self::flatten($record->new_values))
                            ->visible(fn (AuditLog $record) => filled($record->new_values))->label('Giá trị mới')->placeholder('—'),
                    ]),
            ]);
    }

    /** KeyValueEntry chỉ hiển thị chuỗi: mảng con được đổi sang JSON. */
    private static function flatten(?array $values): ?array
    {
        return $values === null ? null : array_map(
            fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            $values,
        );
    }
}
