<?php

namespace App\Modules\Core\Filament\Resources\Redirects;

use App\Modules\Core\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Modules\Core\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Chuyển hướng URL cũ (301/302). Đổi slug bài viết, sản phẩm đã xuất bản thì hệ thống tự thêm. */
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'chuyen-huong';

    protected static ?string $modelLabel = 'chuyển hướng';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Chuyển hướng URL';

    protected static ?string $recordTitleAttribute = 'from_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('from_path')->label('URL cũ')->required()->maxLength(255)
                ->placeholder('/kien-thuc/ten-bai-cu')
                ->helperText('Đường dẫn trên ezfin.vn (dán cả URL cũng được). Chỉ áp dụng khi URL này không còn trang.')
                ->rules([
                    fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                        $path = Redirect::normalizePath((string) $value);

                        if ($path === '/') {
                            $fail('Không chuyển hướng trang chủ.');
                        } elseif (Redirect::where('from_path', $path)->whereKeyNot($record?->getKey())->exists()) {
                            $fail('URL này đã có chuyển hướng.');
                        }
                    },
                ]),
            TextInput::make('to_url')->label('Chuyển tới')->required()->maxLength(500)
                ->placeholder('/kien-thuc/ten-bai-moi')
                ->helperText('Đường dẫn bắt đầu bằng / hoặc URL https:// đầy đủ.')
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get) {
                        $value = trim((string) $value);

                        if (! Redirect::isValidTarget($value)) {
                            $fail('Đích phải là đường dẫn bắt đầu bằng / hoặc URL https://.');
                        } elseif (str_starts_with($value, '/') && Redirect::normalizePath($value) === Redirect::normalizePath((string) $get('from_path'))) {
                            $fail('Đích trùng với URL cũ.');
                        }
                    },
                ]),
            Select::make('status_code')->label('Loại')->options(Redirect::STATUS_CODES)->default(301)->required()->selectablePlaceholder(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_path')->label('URL cũ')->searchable()->wrap(),
                TextColumn::make('to_url')->label('Chuyển tới')->searchable()->wrap(),
                TextColumn::make('status_code')->label('Loại')->badge(),
                TextColumn::make('hits')->label('Lượt')->numeric(thousandsSeparator: '.')->sortable(),
                TextColumn::make('last_hit_at')->label('Lần cuối')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
