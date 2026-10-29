<?php

namespace App\Modules\Compare\Filament\Resources\BankAccounts;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\CreateBankAccount;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\EditBankAccount;
use App\Modules\Compare\Filament\Resources\BankAccounts\Pages\ListBankAccounts;
use App\Modules\Compare\Filament\Resources\BankAccounts\Schemas\BankAccountForm;
use App\Modules\Compare\Filament\Resources\BankAccounts\Tables\BankAccountsTable;
use App\Modules\Compare\Models\FinancialProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Tài khoản thanh toán: trường chung ở financial_products, thông số ở bank_account_details. */
class BankAccountResource extends Resource
{
    protected static ?string $model = FinancialProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'tai-khoan-ngan-hang';

    protected static ?string $modelLabel = 'tài khoản thanh toán';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Tài khoản thanh toán';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ofType(ProductType::BankAccount);
    }

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankAccounts::route('/'),
            'create' => CreateBankAccount::route('/create'),
            'edit' => EditBankAccount::route('/{record}/edit'),
        ];
    }
}
