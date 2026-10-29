<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions\Tables;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialInstitution;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FinancialInstitutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')->label('Logo')->disk(FinancialInstitution::LOGO_DISK)
                    ->imageSize(40)->extraImgAttributes(['class' => 'object-contain']),
                TextColumn::make('short_name')->label('Tên')->searchable(['short_name', 'name'])->sortable()
                    ->description(fn (FinancialInstitution $record) => $record->name),
                TextColumn::make('type')->label('Loại')->badge(),
                TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
                TextColumn::make('updated_at')->label('Cập nhật')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('short_name')
            ->filters([
                SelectFilter::make('type')->label('Loại')->options(InstitutionType::class),
                TernaryFilter::make('is_active')->label('Đang hoạt động'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
