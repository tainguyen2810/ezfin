<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards\Schemas;

use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialProduct;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Str;

class CreditCardForm
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                self::productSection(),
                self::feesSection(),
                self::benefitsSection(),
            ]),
            Grid::make(1)->columnSpan(['lg' => 1])->schema([
                self::publishingSection(),
                Section::make('Ảnh thẻ')->schema([
                    FileUpload::make('image_path')->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024)
                        ->disk('public')
                        ->directory(FinancialProduct::IMAGE_DIRECTORY)
                        ->visibility('public')
                        ->helperText('Ảnh mặt trước thẻ từ trang chính thức. PNG, JPG hoặc WebP, tối đa 1 MB.'),
                ]),
            ]),
        ]);
    }

    private static function productSection(): Section
    {
        return Section::make('Thông tin thẻ')->columns(2)->schema([
            Select::make('institution_id')->label('Ngân hàng phát hành')
                ->relationship('institution', 'short_name')
                ->searchable()->preload()->required(),
            TextInput::make('name')->label('Tên thẻ')->required()->maxLength(255)
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
            Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->maxLength(500)->columnSpanFull()
                ->helperText('Một hai câu mô tả thẻ phù hợp với ai. Không khuyến nghị cá nhân hóa.'),
        ]);
    }

    private static function feesSection(): Section
    {
        return Section::make('Thông số, phí, lãi suất')
            ->description('Để trống nếu chưa có thông tin chính thức. Nhập 0 khi miễn phí.')
            ->relationship('creditCard')
            ->columns(2)
            ->schema([
                Select::make('network')->label('Tổ chức thẻ')->options(CardNetwork::class),
                CheckboxList::make('benefits')->label('Nhóm lợi ích chính')->options(CardBenefit::class)->columns(3)
                    ->columnSpanFull()
                    ->helperText('Dùng cho bộ lọc theo nhu cầu trên trang so sánh.'),
                self::money('annual_fee', 'Phí thường niên')->required(),
                TextInput::make('annual_fee_note')->label('Ghi chú phí thường niên')->maxLength(255)
                    ->placeholder('Ví dụ: miễn phí năm đầu'),
                self::money('issuance_fee', 'Phí phát hành'),
                self::money('min_monthly_income', 'Thu nhập tối thiểu/tháng'),
                self::rate('interest_rate', 'Lãi suất', '%/năm', 100),
                TextInput::make('interest_free_days')->label('Miễn lãi tối đa')->integer()->minValue(0)->maxValue(120)->suffix('ngày'),
                self::rate('cashback_rate', 'Hoàn tiền tối đa', '%', 100),
                self::money('cashback_monthly_cap', 'Hoàn tiền tối đa/tháng'),
                self::rate('foreign_transaction_fee', 'Phí giao dịch ngoại tệ', '%', 10),
            ]);
    }

    private static function benefitsSection(): Section
    {
        return Section::make('Ưu điểm, hạn chế')->schema([
            Grid::make(2)->schema([
                Repeater::make('pros')->label('Ưu điểm')->simple(TextInput::make('text')->required()->maxLength(200))
                    ->addActionLabel('Thêm ưu điểm')->maxItems(8)->defaultItems(0),
                Repeater::make('cons')->label('Hạn chế')->simple(TextInput::make('text')->required()->maxLength(200))
                    ->addActionLabel('Thêm hạn chế')->maxItems(8)->defaultItems(0),
            ]),
        ]);
    }

    private static function publishingSection(): Section
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
                    ->placeholder('Ví dụ: Biểu phí thẻ tín dụng hiệu lực 01/10/2026'),
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
    private static function money(string $name, string $label): TextInput
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

    private static function rate(string $name, string $label, string $suffix, int $max): TextInput
    {
        return TextInput::make($name)->label($label)
            ->numeric()->minValue(0)->maxValue($max)->step('0.01')
            ->suffix($suffix);
    }
}
