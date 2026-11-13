<?php

namespace App\Modules\Compare\Filament\Resources\Loans\Schemas;

use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Compare\Filament\Resources\Shared\ProductFormComponents;
use App\Modules\Core\Filament\Schemas\SeoFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                Section::make('Thông tin khoản vay')->columns(2)->schema([
                    Select::make('institution_id')->label('Ngân hàng, công ty tài chính')
                        ->relationship('institution', 'short_name')
                        ->searchable()->preload()->required(),
                    ...ProductFormComponents::nameAndSlug('Tên gói vay'),
                    Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->maxLength(500)->columnSpanFull()
                        ->helperText('Một hai câu mô tả gói vay phù hợp với ai. Không khuyến nghị cá nhân hóa.'),
                ]),
                // Một section cho cả bảng loan_details: hai section cùng relationship sẽ ghi hai lần.
                Section::make('Lãi suất, hạn mức và phí')
                    ->description('Để trống nếu chưa có thông tin chính thức. Nhập 0 khi miễn phí.')
                    ->relationship('loan')
                    ->columns(2)
                    ->schema([
                        Select::make('kind')->label('Loại vay')->options(LoanKind::class)->required(),
                        ProductFormComponents::rate('promo_rate', 'Lãi ưu đãi (hoặc lãi cố định)', '%/năm', 60)->required(),
                        TextInput::make('promo_months')->label('Số tháng ưu đãi')->integer()->minValue(1)->maxValue(120)->suffix('tháng')
                            ->helperText('Để trống nếu lãi cố định cả kỳ hạn.'),
                        ProductFormComponents::rate('floating_margin', 'Biên độ sau ưu đãi', 'điểm %', 20),
                        TextInput::make('floating_note')->label('Cách tính lãi sau ưu đãi')->maxLength(255)->columnSpanFull()
                            ->placeholder('Ví dụ: lãi tiết kiệm 24 tháng + 3,5%'),
                        ProductFormComponents::money('min_amount', 'Số tiền vay tối thiểu'),
                        ProductFormComponents::money('max_amount', 'Số tiền vay tối đa'),
                        TextInput::make('max_term_months')->label('Thời hạn tối đa')->integer()->minValue(1)->maxValue(600)->suffix('tháng'),
                        ProductFormComponents::rate('max_ltv', 'Cho vay tối đa trên giá trị tài sản', '%', 100),
                        ProductFormComponents::money('min_monthly_income', 'Thu nhập tối thiểu/tháng'),
                        ProductFormComponents::rate('processing_fee', 'Phí hồ sơ', '% số tiền vay', 20),
                        ProductFormComponents::rate('prepayment_fee', 'Phí trả nợ trước hạn cao nhất', '% số tiền trả', 20),
                        TextInput::make('prepayment_note')->label('Ghi chú phí trả nợ trước hạn')->maxLength(255)
                            ->placeholder('Ví dụ: 3% năm đầu, 2% năm thứ hai, miễn phí từ năm thứ năm'),
                    ]),
                ProductFormComponents::prosConsSection(),
            ]),
            Grid::make(1)->columnSpan(['lg' => 1])->schema([
                ProductFormComponents::publishingSection(),
                SeoFields::section(),
            ]),
        ]);
    }
}
