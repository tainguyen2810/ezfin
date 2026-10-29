<?php

namespace App\Modules\Compare\Filament\Resources\FinancialInstitutions\Schemas;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialInstitution;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FinancialInstitutionForm
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Thông tin')->columnSpan(['lg' => 2])->columns(2)->schema([
                TextInput::make('short_name')->label('Tên ngắn')->required()->maxLength(100)
                    ->helperText('Tên thường gọi, hiển thị trên thẻ sản phẩm. Ví dụ: tên viết tắt của ngân hàng.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation) {
                        // Chỉ tự điền slug khi tạo mới: đổi slug sau khi xuất bản sẽ làm hỏng URL.
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                Select::make('type')->label('Loại')->options(InstitutionType::class)->required()
                    ->default(InstitutionType::Bank),
                TextInput::make('name')->label('Tên đầy đủ')->required()->maxLength(255)->columnSpanFull()
                    ->helperText('Tên pháp lý theo giấy phép hoạt động.'),
                TextInput::make('slug')->label('Đường dẫn (slug)')->required()->maxLength(120)
                    ->regex(self::SLUG_PATTERN)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang.',
                        'unique' => 'Slug này đã được dùng cho tổ chức khác.',
                    ])
                    ->helperText('Dùng trong URL công khai; hạn chế đổi sau khi đã xuất bản sản phẩm.'),
                TextInput::make('website')->label('Website chính thức')->url()->maxLength(255)
                    ->startsWith(['https://'])
                    ->validationMessages(['starts_with' => 'Website phải bắt đầu bằng https://'])
                    ->placeholder('https://'),
            ]),
            Section::make('Logo và trạng thái')->columnSpan(['lg' => 1])->schema([
                FileUpload::make('logo_path')->label('Logo')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(1024)
                    ->disk(FinancialInstitution::LOGO_DISK)
                    ->directory(FinancialInstitution::LOGO_DIRECTORY)
                    ->visibility('public')
                    ->helperText('PNG, JPG hoặc WebP, tối đa 1 MB, nên là ảnh vuông nền trong suốt.'),
                Toggle::make('is_active')->label('Đang hoạt động')->default(true)
                    ->helperText('Tắt khi tổ chức ngừng hoạt động hoặc sáp nhập.'),
            ]),
        ]);
    }
}
