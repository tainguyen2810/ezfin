<?php

namespace App\Modules\Compare\Filament\Resources\BankAccounts\Schemas;

use App\Modules\Compare\Enums\BankAccountFeature;
use App\Modules\Compare\Filament\Resources\Shared\ProductFormComponents;
use App\Modules\Core\Filament\Schemas\SeoFields;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                Section::make('Thông tin tài khoản')->columns(2)->schema([
                    Select::make('institution_id')->label('Ngân hàng')
                        ->relationship('institution', 'short_name')
                        ->searchable()->preload()->required(),
                    ...ProductFormComponents::nameAndSlug('Tên tài khoản'),
                    Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->maxLength(500)->columnSpanFull()
                        ->helperText('Một hai câu mô tả tài khoản phù hợp với ai. Không khuyến nghị cá nhân hóa.'),
                ]),
                Section::make('Phí và tính năng')
                    ->description('Để trống nếu chưa có thông tin chính thức. Nhập 0 khi miễn phí.')
                    ->relationship('bankAccount')
                    ->columns(2)
                    ->schema([
                        CheckboxList::make('features')->label('Tính năng')->options(BankAccountFeature::class)->columns(3)
                            ->columnSpanFull()
                            ->helperText('Dùng cho bộ lọc trên trang so sánh. Chỉ chọn khi ngân hàng công bố rõ.'),
                        ProductFormComponents::money('monthly_fee', 'Phí quản lý tài khoản/tháng')->required(),
                        TextInput::make('monthly_fee_note')->label('Ghi chú phí quản lý')->maxLength(255)
                            ->placeholder('Ví dụ: miễn phí khi số dư bình quân từ 2.000.000 đ'),
                        ProductFormComponents::money('min_balance', 'Số dư tối thiểu'),
                        ProductFormComponents::money('transfer_fee', 'Phí chuyển khoản liên ngân hàng online/giao dịch'),
                        ProductFormComponents::money('atm_withdrawal_fee', 'Phí rút tiền ATM nội mạng/giao dịch'),
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
