<?php

namespace App\Modules\Content\Filament\Resources\Articles\Schemas;

use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\Author;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ArticleForm
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Nội dung')->columnSpan(['lg' => 2])->schema([
                TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation) {
                        // Chỉ tự điền slug khi tạo mới: đổi slug sau khi xuất bản sẽ làm hỏng URL.
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::limit(Str::slug((string) $state), 150, ''));
                        }
                    }),
                TextInput::make('slug')->label('Đường dẫn (slug)')->required()->maxLength(150)
                    ->regex(self::SLUG_PATTERN)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang.',
                        'unique' => 'Slug này đã được dùng cho bài viết khác.',
                    ])
                    ->helperText('Hạn chế đổi sau khi xuất bản.'),
                Textarea::make('excerpt')->label('Tóm tắt')->rows(3)->maxLength(500)
                    ->helperText('Hiện dưới tiêu đề và trong kết quả tìm kiếm (nên 120–160 ký tự). Bắt buộc khi gửi duyệt.'),
                RichEditor::make('body')->label('Nội dung')
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList', 'blockquote'],
                        ['table', 'attachFiles'],
                        ['undo', 'redo'],
                    ])
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory(Article::COVER_DIRECTORY.'/noi-dung')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->fileAttachmentsMaxSize(2048)
                    ->helperText('Mã HTML không an toàn (script, iframe, thuộc tính sự kiện) được tự động loại bỏ khi lưu.'),
                Repeater::make('references')->label('Nguồn tham khảo')
                    ->schema([
                        TextInput::make('title')->label('Tên nguồn')->required()->maxLength(255)
                            ->placeholder('Ví dụ: Thông tư 39/2016/TT-NHNN'),
                        TextInput::make('url')->label('Liên kết')->url()->maxLength(500)
                            ->startsWith(['https://', 'http://'])
                            ->validationMessages(['starts_with' => 'Liên kết phải bắt đầu bằng https:// hoặc http://'])
                            ->placeholder('https://'),
                    ])
                    ->columns(2)
                    ->addActionLabel('Thêm nguồn')
                    ->defaultItems(0)
                    ->maxItems(20),
            ]),
            Section::make('Xuất bản')->columnSpan(['lg' => 1])->schema([
                TextEntry::make('status')->label('Trạng thái')->badge()->hiddenOn('create'),
                TextEntry::make('review_note')->label('Ghi chú của người duyệt')->color('warning')
                    ->visible(fn (?Article $record) => filled($record?->review_note)),
                TextEntry::make('published_at')->label('Xuất bản lúc')->dateTime('d/m/Y H:i')
                    ->visible(fn (?Article $record) => $record?->published_at !== null),
                Select::make('category_id')->label('Chuyên mục')->required()
                    ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('sort_order')->orderBy('name'))
                    ->preload(),
                Select::make('author_id')->label('Tác giả')->required()
                    ->relationship('author', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('name'))
                    ->default(fn () => Author::where('user_id', auth()->id())->value('id'))
                    ->searchable()
                    ->preload(),
                FileUpload::make('cover_path')->label('Ảnh bìa')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory(Article::COVER_DIRECTORY)
                    ->visibility('public')
                    ->helperText('Tỉ lệ 16:9, PNG, JPG hoặc WebP, tối đa 2 MB.'),
            ]),
        ]);
    }
}
