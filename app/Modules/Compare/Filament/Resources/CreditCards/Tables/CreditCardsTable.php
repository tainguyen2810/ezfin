<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards\Tables;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreditCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['institution', 'creditCard']))
            ->columns([
                ImageColumn::make('image_path')->label('Ảnh')->disk('public')->imageHeight(32),
                TextColumn::make('name')->label('Tên thẻ')->searchable()->sortable()
                    ->description(fn (FinancialProduct $record) => $record->institution->short_name),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('creditCard.annual_fee')->label('Phí thường niên')
                    ->formatStateUsing(fn ($state) => Format::money($state)),
                TextColumn::make('creditCard.cashback_rate')->label('Hoàn tiền')
                    ->formatStateUsing(fn ($state) => Format::percent($state))->placeholder('—'),
                TextColumn::make('verified_at')->label('Xác minh')->date('d/m/Y')->sortable()->placeholder('Chưa xác minh'),
                TextColumn::make('next_review_at')->label('Kiểm tra lại')->date('d/m/Y')->sortable()
                    ->color(fn (FinancialProduct $record) => $record->isDueForReview() ? 'danger' : null)
                    ->icon(fn (FinancialProduct $record) => $record->isDueForReview() ? 'heroicon-o-exclamation-triangle' : null)
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('institution')->label('Ngân hàng')->relationship('institution', 'short_name')->searchable()->preload(),
                SelectFilter::make('status')->label('Trạng thái')->options(ProductStatus::class),
                Filter::make('due_for_review')->label('Đến hạn kiểm tra lại')
                    ->query(fn (Builder $query) => $query->whereDate('next_review_at', '<=', today())),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
