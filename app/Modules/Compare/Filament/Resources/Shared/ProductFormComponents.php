<?php

namespace App\Modules\Compare\Filament\Resources\Shared;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialProduct;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Str;

/** Trường dùng chung cho form các loại sản phẩm tài chính (thẻ, tiết kiệm, ...). */
class ProductFormComponents
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Tên sản phẩm và slug (tự điền slug khi tạo mới). */
    public static function nameAndSlug(string $nameLabel): array
    {
        return [
            TextInput::make('name')->label($nameLabel)->required()->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation) {
                    // Chỉ tự điền slug khi tạo mới: đổi slug sau khi xuất bản sẽ làm hỏng URL.
                    if ($operation === 'create' && blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')->label('Đường dẫn (slug)')->required()->maxLength(150)
                ->regex(self::SLUG_PATTERN)
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang.',
                    'unique' => 'Slug này đã được dùng cho sản phẩm khác.',
                ]),
        ];
    }

    public static function publishingSection(): Section
    {
        $publishing = fn (Get $get) => self::isPublishing($get('status'));

        return Section::make('Xuất bản và nguồn')
            ->description('Chỉ xuất bản khi có nguồn chính thức và ngày xác minh.')
            ->schema([
                Select::make('status')->label('Trạng thái')->options(ProductStatus::class)
                    ->default(ProductStatus::Draft)->required()->live(),
                TextInput::make('source_url')->label('Nguồn chính thức')->url()->maxLength(500)
                    ->placeholder('https://')
                    ->required($publishing)
                    ->validationMessages(['required' => 'Cần nguồn chính thức trước khi xuất bản.'])
                    ->helperText('Trang biểu phí, điều khoản hoặc trang sản phẩm của ngân hàng.'),
                TextInput::make('source_note')->label('Ghi chú nguồn')->maxLength(500)
                    ->placeholder('Ví dụ: Biểu phí, biểu lãi suất hiệu lực 01/10/2026'),
                DatePicker::make('verified_at')->label('Ngày xác minh')
                    ->native(false)->displayFormat('d/m/Y')->maxDate(today())
                    ->required($publishing)
                    ->validationMessages(['required' => 'Cần ngày xác minh trước khi xuất bản.'])
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        if ($state && blank($get('next_review_at'))) {
                            $set('next_review_at', now()->parse($state)->addMonths(FinancialProduct::REVIEW_INTERVAL_MONTHS)->toDateString());
                        }
                    }),
                DatePicker::make('next_review_at')->label('Hẹn kiểm tra lại')
                    ->native(false)->displayFormat('d/m/Y')
                    ->afterOrEqual('verified_at')
                    ->helperText('Mặc định 3 tháng sau ngày xác minh.'),
            ]);
    }

    private static function isPublishing(mixed $status): bool
    {
        return ($status instanceof ProductStatus ? $status : ProductStatus::tryFrom((string) $status)) === ProductStatus::Published;
    }

    /**
     * Ô nhập tiền VND có dấu chấm phân cách hàng nghìn: 1.200.000. Không dùng numeric()/stripCharacters()
     * vì chúng cũng chạy khi nạp giá trị "1200000.00" từ DB và biến nó thành 120000000.
     */
    public static function money(string $name, string $label): TextInput
    {
        return TextInput::make($name)->label($label)
            ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
            ->formatStateUsing(fn ($state) => blank($state) ? null : number_format((float) $state, 0, ',', '.'))
            ->dehydrateStateUsing(fn ($state) => blank($state) ? null : str_replace('.', '', (string) $state))
            ->regex('/^\d{1,3}(\.\d{3}){0,4}$/')
            ->validationMessages(['regex' => 'Số tiền không hợp lệ (số nguyên, không âm, ví dụ 1.200.000).'])
            ->inputMode('numeric')
            ->suffix('đ');
    }

    public static function rate(string $name, string $label, string $suffix, int $max): TextInput
    {
        return TextInput::make($name)->label($label)
            ->numeric()->minValue(0)->maxValue($max)->step('0.01')
            ->suffix($suffix);
    }
}
