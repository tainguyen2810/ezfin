<?php

namespace App\Modules\Newsletter\Filament\Resources\Subscribers;

use App\Modules\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Modules\Newsletter\Models\Subscriber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Người nhận email đã xác nhận. Xóa khi người dùng yêu cầu xóa dữ liệu. */
class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'nguoi-nhan-tin';

    protected static ?string $modelLabel = 'người nhận tin';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Người nhận tin';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('alerts'))
            ->columns([
                TextColumn::make('email')->label('Email')->searchable(),
                IconColumn::make('weekly_digest')->label('Bản tin tuần')->boolean(),
                TextColumn::make('alerts.term_months')->label('Báo lãi tăng (tháng)')->badge()->placeholder('—'),
                TextColumn::make('source_path')->label('Đăng ký từ')->placeholder('—')->limit(40),
                TextColumn::make('confirmed_at')->label('Xác nhận')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([TernaryFilter::make('weekly_digest')->label('Bản tin tuần')])
            ->defaultSort('confirmed_at', 'desc')
            ->recordActions([DeleteAction::make()->label('Xóa (theo yêu cầu)')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSubscribers::route('/')];
    }
}
