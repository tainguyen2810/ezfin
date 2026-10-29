<?php

namespace App\Modules\Content\Filament\Resources\Authors;

use App\Modules\Content\Filament\Resources\Authors\Pages\CreateAuthor;
use App\Modules\Content\Filament\Resources\Authors\Pages\EditAuthor;
use App\Modules\Content\Filament\Resources\Authors\Pages\ListAuthors;
use App\Modules\Content\Models\Author;
use App\Modules\Core\Enums\Role;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'tac-gia';

    protected static ?string $modelLabel = 'tác giả';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Tác giả';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Hồ sơ')->columnSpan(['lg' => 2])->columns(2)->schema([
                TextInput::make('name')->label('Họ tên')->required()->maxLength(255)
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
                        'unique' => 'Slug này đã được dùng cho tác giả khác.',
                    ]),
                TextInput::make('job_title')->label('Chức danh')->maxLength(255)
                    ->placeholder('Ví dụ: Chuyên viên phân tích tài chính'),
                TextInput::make('expertise')->label('Chuyên môn')->maxLength(255)
                    ->placeholder('Ví dụ: Tín dụng tiêu dùng, thẻ tín dụng'),
                Textarea::make('bio')->label('Giới thiệu')->rows(5)->maxLength(2000)->columnSpanFull()
                    ->helperText('Kinh nghiệm, bằng cấp, chứng chỉ liên quan. Chỉ ghi thông tin có thật, kiểm chứng được.'),
                Repeater::make('links')->label('Liên kết hồ sơ')->columnSpanFull()
                    ->simple(TextInput::make('url')->url()->required()->maxLength(255)
                        ->startsWith(['https://', 'http://'])
                        ->validationMessages(['starts_with' => 'Liên kết phải bắt đầu bằng https:// hoặc http://'])
                        ->placeholder('https://'))
                    ->addActionLabel('Thêm liên kết')->defaultItems(0)->maxItems(5),
            ]),
            Section::make('Ảnh và tài khoản')->columnSpan(['lg' => 1])->schema([
                FileUpload::make('avatar_path')->label('Ảnh đại diện')
                    ->image()->avatar()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(1024)
                    ->disk('public')->directory(Author::AVATAR_DIRECTORY)->visibility('public'),
                Select::make('user_id')->label('Tài khoản quản trị')
                    ->relationship('user', 'name', fn ($query) => $query->role(Role::values()))
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Tài khoản này đã gắn với tác giả khác.'])
                    ->searchable()->preload()
                    ->helperText('Không bắt buộc. Gắn để bài mới của người này tự chọn đúng tác giả.'),
                Toggle::make('is_active')->label('Đang hoạt động')->default(true)
                    ->helperText('Tắt để ẩn trang tác giả; bài đã đăng vẫn hiển thị tên.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_path')->label('Ảnh')->disk('public')->circular()->imageHeight(32),
                TextColumn::make('name')->label('Họ tên')->searchable()->sortable()
                    ->description(fn (Author $record) => $record->job_title),
                TextColumn::make('user.name')->label('Tài khoản')->placeholder('—'),
                TextColumn::make('articles_count')->label('Số bài')->counts('articles'),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
        ];
    }
}
