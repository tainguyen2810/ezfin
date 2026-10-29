<?php

namespace App\Modules\Core\Filament\Resources\Users\Schemas;

use App\Modules\Core\Enums\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin tài khoản')->columns(2)->schema([
                TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                    ->rule(Password::min(12)->mixedCase()->numbers())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Để trống nếu không đổi mật khẩu.'
                        : 'Tối thiểu 12 ký tự, có chữ hoa, chữ thường và số.'),
            ]),
            Section::make('Vai trò quản trị')
                ->description('Không chọn vai trò nào = tài khoản khách hàng, không vào được trang quản trị.')
                ->schema([
                    CheckboxList::make('roles')->hiddenLabel()
                        ->relationship('roles', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Model $record) => Role::tryFrom($record->name)?->label() ?? $record->name)
                        // Không tự gỡ quyền của chính mình để tránh khóa hệ thống.
                        ->disabled(fn (?Model $record) => $record?->is(auth()->user()))
                        ->columns(2),
                ]),
        ]);
    }
}
