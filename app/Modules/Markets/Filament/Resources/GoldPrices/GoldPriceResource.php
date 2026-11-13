<?php

namespace App\Modules\Markets\Filament\Resources\GoldPrices;

use App\Modules\Markets\Filament\Resources\GoldPrices\Pages\ManageGoldPrices;
use App\Modules\Markets\Models\GoldPrice;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Giá vàng (đồng/lượng) nhập tay hoặc nhập file từ bảng doanh nghiệp vàng công bố. */
class GoldPriceResource extends Resource
{
    protected static ?string $model = GoldPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 61;

    protected static ?string $slug = 'gia-vang';

    protected static ?string $modelLabel = 'giá vàng';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Giá vàng';

    public static function form(Schema $schema): Schema
    {
        $price = fn (string $name, string $label) => TextInput::make($name)->label($label)->integer()->minValue(1)->maxValue(999_999_999_999)->suffix('đ/lượng');

        return $schema->columns(2)->components([
            TextInput::make('source')->label('Nguồn công bố')->required()->maxLength(60)->placeholder('SJC'),
            TextInput::make('product')->label('Loại vàng')->required()->maxLength(120)->placeholder('Vàng miếng SJC 1 lượng')
                ->datalist(fn () => GoldPrice::query()->distinct()->orderBy('product')->pluck('product')->all()),
            DateTimePicker::make('quoted_at')->label('Thời điểm công bố')->seconds(false)->maxDate(now())->required()->native(false)->displayFormat('d/m/Y H:i'),
            TextInput::make('source_url')->label('Link nguồn')->url()->maxLength(500)->placeholder('https://'),
            $price('buy', 'Mua vào')->lte('sell'),
            $price('sell', 'Bán ra')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quoted_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('source')->label('Nguồn')->searchable(),
                TextColumn::make('product')->label('Loại vàng')->searchable()->wrap(),
                TextColumn::make('buy')->label('Mua vào')->money('VND', locale: 'vi')->placeholder('—'),
                TextColumn::make('sell')->label('Bán ra')->money('VND', locale: 'vi'),
            ])
            ->filters([
                SelectFilter::make('source')->label('Nguồn')->options(fn () => GoldPrice::query()->distinct()->orderBy('source')->pluck('source', 'source')->all()),
            ])
            ->defaultSort('quoted_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGoldPrices::route('/')];
    }
}
