<?php

namespace App\Modules\Core\Filament\Resources\Users\Tables;

use App\Modules\Core\Enums\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('roles.name')->label('Vai trò')->badge()
                    ->formatStateUsing(fn (string $state) => Role::tryFrom($state)?->label() ?? $state),
                IconColumn::make('app_authentication_secret')->label('2FA')
                    ->state(fn ($record) => filled($record->app_authentication_secret))->boolean(),
                TextColumn::make('last_login_at')->label('Đăng nhập gần nhất')->dateTime('d/m/Y H:i')->sortable()->placeholder('Chưa đăng nhập'),
                TextColumn::make('created_at')->label('Ngày tạo')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')->label('Vai trò')->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => Role::tryFrom($record->name)?->label() ?? $record->name),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
