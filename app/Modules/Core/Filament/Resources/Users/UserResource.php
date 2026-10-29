<?php

namespace App\Modules\Core\Filament\Resources\Users;

use App\Models\User;
use App\Modules\Core\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Core\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Core\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Core\Filament\Resources\Users\Schemas\UserForm;
use App\Modules\Core\Filament\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $modelLabel = 'người dùng';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Người dùng';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
