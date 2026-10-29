<?php

namespace App\Modules\Content\Filament\Resources\Articles;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Filament\Resources\Articles\Pages\CreateArticle;
use App\Modules\Content\Filament\Resources\Articles\Pages\EditArticle;
use App\Modules\Content\Filament\Resources\Articles\Pages\ListArticles;
use App\Modules\Content\Filament\Resources\Articles\Pages\ViewArticle;
use App\Modules\Content\Filament\Resources\Articles\RelationManagers\RevisionsRelationManager;
use App\Modules\Content\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Modules\Content\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Modules\Content\Models\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'bai-viet';

    protected static ?string $modelLabel = 'bài viết';

    // Tiếng Việt không viết hoa mọi chữ cái đầu từ.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Bài viết';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** Số bài đang chờ duyệt, chỉ hiện cho người duyệt. */
    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->can('publish', Article::class)) {
            return null;
        }

        $count = Article::where('status', ArticleStatus::InReview)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Bài chờ duyệt';
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'view' => ViewArticle::route('/{record}'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
