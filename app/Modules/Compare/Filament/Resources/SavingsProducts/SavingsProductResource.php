<?php

namespace App\Modules\Compare\Filament\Resources\SavingsProducts;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\CreateSavingsProduct;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\EditSavingsProduct;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Pages\ListSavingsProducts;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Schemas\SavingsProductForm;
use App\Modules\Compare\Filament\Resources\SavingsProducts\Tables\SavingsProductsTable;
use App\Modules\Compare\Models\FinancialProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Sản phẩm tiết kiệm: trường chung ở financial_products, bảng lãi suất ở savings_rates. */
class SavingsProductResource extends Resource
{
    protected static ?string $model = FinancialProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'tiet-kiem';

    protected static ?string $modelLabel = 'sản phẩm tiết kiệm';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Lãi suất tiết kiệm';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ofType(ProductType::Savings);
    }

    public static function form(Schema $schema): Schema
    {
        return SavingsProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavingsProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavingsProducts::route('/'),
            'create' => CreateSavingsProduct::route('/create'),
            'edit' => EditSavingsProduct::route('/{record}/edit'),
        ];
    }
}
