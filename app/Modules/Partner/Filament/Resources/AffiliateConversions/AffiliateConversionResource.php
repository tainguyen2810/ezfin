<?php

namespace App\Modules\Partner\Filament\Resources\AffiliateConversions;

use App\Modules\Partner\Enums\ConversionStatus;
use App\Modules\Partner\Filament\Resources\AffiliateConversions\Pages\ManageAffiliateConversions;
use App\Modules\Partner\Models\AffiliateConversion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Chuyển đổi do đối tác báo về. Sửa tay trạng thái, hoa hồng khi đối soát; không xóa. */
class AffiliateConversionResource extends Resource
{
    protected static ?string $model = AffiliateConversion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Đối tác';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'chuyen-doi';

    protected static ?string $modelLabel = 'chuyển đổi';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Chuyển đổi';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('status')->label('Trạng thái')->options(ConversionStatus::class)->required(),
            TextInput::make('commission')->label('Hoa hồng (đ)')->numeric()->minValue(0)->maxValue(999_999_999_999)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('link.product.institution:id,short_name'))
            ->columns([
                TextColumn::make('occurred_at')->label('Ngày')->date('d/m/Y')->sortable(),
                TextColumn::make('network')->label('Mạng')->badge()->color('gray'),
                TextColumn::make('external_id')->label('Mã giao dịch')->searchable()->copyable(),
                TextColumn::make('link.name')->label('Link')->placeholder('Chưa khớp click')->wrap()
                    ->description(fn (AffiliateConversion $conversion) => $conversion->link?->product?->institution?->short_name),
                TextColumn::make('source_path')->label('Trang nguồn')->placeholder('—')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('commission')->label('Hoa hồng')->money('VND', locale: 'vi')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(ConversionStatus::class),
                SelectFilter::make('network')->label('Mạng')
                    ->options(fn () => AffiliateConversion::query()->distinct()->orderBy('network')->pluck('network', 'network')->all()),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAffiliateConversions::route('/')];
    }
}
