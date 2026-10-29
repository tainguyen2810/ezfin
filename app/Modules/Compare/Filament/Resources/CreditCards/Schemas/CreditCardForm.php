<?php

namespace App\Modules\Compare\Filament\Resources\CreditCards\Schemas;

use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Filament\Resources\Shared\ProductFormComponents;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Filament\Schemas\SeoFields;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreditCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                self::productSection(),
                self::feesSection(),
                ProductFormComponents::prosConsSection(),
            ]),
            Grid::make(1)->columnSpan(['lg' => 1])->schema([
                ProductFormComponents::publishingSection(),
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
                SeoFields::section(),
            ]),
        ]);
    }

    private static function productSection(): Section
    {
        return Section::make('Thông tin thẻ')->columns(2)->schema([
            Select::make('institution_id')->label('Ngân hàng phát hành')
                ->relationship('institution', 'short_name')
                ->searchable()->preload()->required(),
            ...ProductFormComponents::nameAndSlug('Tên thẻ'),
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
                ProductFormComponents::money('annual_fee', 'Phí thường niên')->required(),
                TextInput::make('annual_fee_note')->label('Ghi chú phí thường niên')->maxLength(255)
                    ->placeholder('Ví dụ: miễn phí năm đầu'),
                ProductFormComponents::money('issuance_fee', 'Phí phát hành'),
                ProductFormComponents::money('min_monthly_income', 'Thu nhập tối thiểu/tháng'),
                ProductFormComponents::rate('interest_rate', 'Lãi suất', '%/năm', 100),
                TextInput::make('interest_free_days')->label('Miễn lãi tối đa')->integer()->minValue(0)->maxValue(120)->suffix('ngày'),
                ProductFormComponents::rate('cashback_rate', 'Hoàn tiền tối đa', '%', 100),
                ProductFormComponents::money('cashback_monthly_cap', 'Hoàn tiền tối đa/tháng'),
                ProductFormComponents::rate('foreign_transaction_fee', 'Phí giao dịch ngoại tệ', '%', 10),
            ]);
    }
}
