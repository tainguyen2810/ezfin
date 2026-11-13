<?php

namespace App\Modules\Compare\Filament\Resources\Loans;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\Loans\Pages\CreateLoan;
use App\Modules\Compare\Filament\Resources\Loans\Pages\EditLoan;
use App\Modules\Compare\Filament\Resources\Loans\Pages\ListLoans;
use App\Modules\Compare\Filament\Resources\Loans\Schemas\LoanForm;
use App\Modules\Compare\Filament\Resources\Loans\Tables\LoansTable;
use App\Modules\Compare\Models\FinancialProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Khoản vay: trường chung ở financial_products, thông số ở loan_details. */
class LoanResource extends Resource
{
    protected static ?string $model = FinancialProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'khoan-vay';

    protected static ?string $modelLabel = 'khoản vay';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Khoản vay';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ofType(ProductType::Loan);
    }

    public static function form(Schema $schema): Schema
    {
        return LoanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'edit' => EditLoan::route('/{record}/edit'),
        ];
    }
}
