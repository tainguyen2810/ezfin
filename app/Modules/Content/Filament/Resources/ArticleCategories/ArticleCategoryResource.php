<?php

namespace App\Modules\Content\Filament\Resources\ArticleCategories;

use App\Modules\Content\Filament\Resources\ArticleCategories\Pages\CreateArticleCategory;
use App\Modules\Content\Filament\Resources\ArticleCategories\Pages\EditArticleCategory;
use App\Modules\Content\Filament\Resources\ArticleCategories\Pages\ListArticleCategories;
use App\Modules\Content\Models\ArticleCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'chuyen-muc';

    protected static ?string $modelLabel = 'chuyên mục';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Chuyên mục';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Tên chuyên mục')->required()->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation) {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->label('Đường dẫn (slug)')->required()->maxLength(120)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang.',
                        'unique' => 'Slug này đã được dùng cho chuyên mục khác.',
                    ])
                    ->helperText('URL: /kien-thuc/{slug}. Đổi slug sẽ đổi URL mọi bài trong chuyên mục.'),
                Textarea::make('description')->label('Mô tả')->rows(3)->maxLength(300)->columnSpanFull()
                    ->helperText('Hiện ở đầu trang chuyên mục và trong kết quả tìm kiếm.'),
                TextInput::make('sort_order')->label('Thứ tự')->integer()->minValue(0)->maxValue(1000)->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('articles_count')->label('Số bài')->counts('articles'),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleCategories::route('/'),
            'create' => CreateArticleCategory::route('/create'),
            'edit' => EditArticleCategory::route('/{record}/edit'),
        ];
    }
}
