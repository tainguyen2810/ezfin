<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions;

use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\CreateFinancialInstitution;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\EditFinancialInstitution;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Pages\ListFinancialInstitutions;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Schemas\FinancialInstitutionForm;
use App\Modules\Compare\Filament\Resources\FinancialInstitutions\Tables\FinancialInstitutionsTable;
use App\Modules\Compare\Models\FinancialInstitution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FinancialInstitutionResource extends Resource
{
    protected static ?string $model = FinancialInstitution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu sản phẩm';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'to-chuc-tai-chinh';

    protected static ?string $modelLabel = 'tổ chức tài chính';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Ngân hàng và tổ chức tài chính';

    protected static ?string $recordTitleAttribute = 'short_name';

    public static function form(Schema $schema): Schema
    {
        return FinancialInstitutionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialInstitutionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialInstitutions::route('/'),
            'create' => CreateFinancialInstitution::route('/create'),
            'edit' => EditFinancialInstitution::route('/{record}/edit'),
        ];
    }
}
