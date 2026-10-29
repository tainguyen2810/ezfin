<?php

namespace App\Modules\Content\Filament\Resources\Articles\Tables;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'author']))
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(80)->wrap()
                    ->description(fn (Article $record) => $record->category->name),
                TextColumn::make('author.name')->label('Tác giả'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('published_at')->label('Xuất bản')->dateTime('d/m/Y H:i')->sortable()->placeholder('—'),
                TextColumn::make('content_updated_at')->label('Sửa nội dung')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('content_updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(ArticleStatus::class),
                SelectFilter::make('category')->label('Chuyên mục')->relationship('category', 'name')->preload(),
                SelectFilter::make('author')->label('Tác giả')->relationship('author', 'name')->searchable()->preload(),
                TrashedFilter::make()->label('Bài đã xóa'),
            ])
            ->recordActions([
                ViewAction::make()->hidden(fn (Article $record) => auth()->user()->can('update', $record)),
                EditAction::make(),
                RestoreAction::make(),
            ]);
    }
}
