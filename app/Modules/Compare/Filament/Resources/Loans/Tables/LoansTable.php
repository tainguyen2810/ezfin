<?php

namespace App\Modules\Compare\Filament\Resources\Loans\Tables;

use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['institution', 'loan']))
            ->columns([
                TextColumn::make('name')->label('Gói vay')->searchable()->sortable()
                    ->description(fn (FinancialProduct $record) => $record->institution->short_name),
                TextColumn::make('loan.kind')->label('Loại vay')->badge(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('loan.promo_rate')->label('Lãi ưu đãi')
                    ->formatStateUsing(fn ($state) => Format::rate($state))->placeholder('—'),
                TextColumn::make('loan.promo_months')->label('Ưu đãi')
                    ->formatStateUsing(fn ($state) => $state.' tháng')->placeholder('Cố định'),
                TextColumn::make('verified_at')->label('Xác minh')->date('d/m/Y')->sortable()->placeholder('Chưa xác minh'),
                TextColumn::make('next_review_at')->label('Kiểm tra lại')->date('d/m/Y')->sortable()
                    ->color(fn (FinancialProduct $record) => $record->isDueForReview() ? 'danger' : null)
                    ->icon(fn (FinancialProduct $record) => $record->isDueForReview() ? 'heroicon-o-exclamation-triangle' : null)
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('kind')->label('Loại vay')->options(LoanKind::class)
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $query, $kind) => $query->whereHas('loan', fn (Builder $query) => $query->where('kind', $kind)))),
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
