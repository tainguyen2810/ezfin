<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\CreateCreditCard;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\EditCreditCard;
use App\Modules\Compare\Filament\Resources\CreditCards\Pages\ListCreditCards;
use App\Modules\Compare\Filament\Resources\CreditCards\Schemas\CreditCardForm;
use App\Modules\Compare\Filament\Resources\CreditCards\Tables\CreditCardsTable;
use App\Modules\Compare\Models\FinancialProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Sản phẩm loại thẻ tín dụng: trường chung ở financial_products, thông số ở credit_card_details. */
class CreditCardResource extends Resource
{
    protected static ?string $model = FinancialProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'the-tin-dung';

    protected static ?string $modelLabel = 'thẻ tín dụng';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Thẻ tín dụng';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ofType(ProductType::CreditCard);
    }

    public static function form(Schema $schema): Schema
    {
        return CreditCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditCardsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditCards::route('/'),
            'create' => CreateCreditCard::route('/create'),
            'edit' => EditCreditCard::route('/{record}/edit'),
        ];
    }
}
