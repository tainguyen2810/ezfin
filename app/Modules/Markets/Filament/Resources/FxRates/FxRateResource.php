<?php

namespace App\Modules\Markets\Filament\Resources\FxRates;

use App\Modules\Markets\Filament\Resources\FxRates\Pages\ManageFxRates;
use App\Modules\Markets\Models\FxRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Tỷ giá nhập tay hoặc nhập file từ bảng ngân hàng công bố. */
class FxRateResource extends Resource
{
    protected static ?string $model = FxRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'ty-gia';

    protected static ?string $modelLabel = 'tỷ giá';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Tỷ giá';

    public static function form(Schema $schema): Schema
    {
        $price = fn (string $name, string $label) => TextInput::make($name)->label($label)->numeric()->minValue(0.01)->maxValue(999_999_999)->step('0.01')->suffix('đ');

        return $schema->columns(2)->components([
            TextInput::make('source')->label('Nguồn công bố')->required()->maxLength(60)->placeholder('Vietcombank'),
            Select::make('currency')->label('Ngoại tệ')->options(collect(FxRate::CURRENCIES)->mapWithKeys(fn ($name, $code) => [$code => "$code – $name"]))->required()->searchable(),
            DateTimePicker::make('quoted_at')->label('Thời điểm công bố')->seconds(false)->maxDate(now())->required()->native(false)->displayFormat('d/m/Y H:i'),
            TextInput::make('source_url')->label('Link nguồn')->url()->maxLength(500)->placeholder('https://'),
            $price('buy_cash', 'Mua tiền mặt')->lte('sell'),
            $price('buy_transfer', 'Mua chuyển khoản')->lte('sell'),
            $price('sell', 'Bán')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quoted_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('source')->label('Nguồn')->searchable(),
                TextColumn::make('currency')->label('Ngoại tệ')->badge()->color('gray'),
                TextColumn::make('buy_cash')->label('Mua tiền mặt')->numeric(2, ',', '.')->placeholder('—'),
                TextColumn::make('buy_transfer')->label('Mua chuyển khoản')->numeric(2, ',', '.')->placeholder('—'),
                TextColumn::make('sell')->label('Bán')->numeric(2, ',', '.'),
            ])
            ->filters([
                SelectFilter::make('currency')->label('Ngoại tệ')->options(array_combine(array_keys(FxRate::CURRENCIES), array_keys(FxRate::CURRENCIES))),
                SelectFilter::make('source')->label('Nguồn')->options(fn () => FxRate::query()->distinct()->orderBy('source')->pluck('source', 'source')->all()),
            ])
            ->defaultSort('quoted_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFxRates::route('/')];
    }
}
