<?php

namespace App\Modules\Compare\Filament\Resources\SavingsProducts\Schemas;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Filament\Resources\Shared\ProductFormComponents;
use App\Modules\Compare\Models\SavingsRate;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SavingsProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                Section::make('Thông tin sản phẩm')->columns(2)->schema([
                    Select::make('institution_id')->label('Ngân hàng')
                        ->relationship('institution', 'short_name')
                        ->searchable()->preload()->required(),
                    ...ProductFormComponents::nameAndSlug('Tên sản phẩm'),
                    Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->maxLength(500)->columnSpanFull()
                        ->helperText('Ví dụ: gửi online qua ứng dụng, rút trước hạn hưởng lãi không kỳ hạn. Không khuyến nghị cá nhân hóa.'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['lg' => 1])->schema([
                ProductFormComponents::publishingSection(),
            ]),
            self::ratesSection()->columnSpanFull(),
        ]);
    }

    private static function ratesSection(): Section
    {
        return Section::make('Bảng lãi suất')
            ->description('Kỳ hạn tính bằng tháng (0 = không kỳ hạn), lãi suất %/năm theo biểu lãi suất chính thức. Có thể nhập nhiều lần theo ngày hiệu lực; trang so sánh dùng dòng có ngày hiệu lực gần nhất. Nhập nhiều dòng một lúc: dùng "Nhập CSV" ở danh sách.')
            ->schema([
                Repeater::make('savingsRates')->hiddenLabel()
                    ->relationship()
                    ->table([
                        TableColumn::make('Kỳ hạn')->markAsRequired()->width('80px'),
                        TableColumn::make('Kênh')->markAsRequired()->width('125px'),
                        TableColumn::make('Lĩnh lãi')->markAsRequired()->width('140px'),
                        TableColumn::make('Lãi suất')->markAsRequired()->width('135px'),
                        TableColumn::make('Số tiền từ')->width('125px'),
                        TableColumn::make('Số tiền đến')->width('125px'),
                        TableColumn::make('Hiệu lực từ')->markAsRequired()->width('130px'),
                    ])
                    ->schema([
                        TextInput::make('term_months')->integer()->minValue(0)->maxValue(SavingsRate::MAX_TERM_MONTHS)->required()
                            ->placeholder('tháng'),
                        Select::make('channel')->options(SavingsChannel::class)->required(),
                        Select::make('interest_payment')->options(InterestPayment::class)->default(InterestPayment::EndOfTerm)->required(),
                        ProductFormComponents::rate('rate', 'Lãi suất', '%', SavingsRate::MAX_RATE)->required(),
                        ProductFormComponents::money('min_amount', 'Số tiền từ')
                            // Trống = áp dụng từ 0 đ.
                            ->dehydrateStateUsing(fn ($state) => blank($state) ? 0 : str_replace('.', '', (string) $state)),
                        ProductFormComponents::money('max_amount', 'Số tiền đến'),
                        DatePicker::make('effective_from')->native(false)->displayFormat('d/m/Y')->default(today())->required(),
                    ])
                    ->rule(fn (): Closure => self::noDuplicateRates(...))
                    ->defaultItems(0)
                    ->addActionLabel('Thêm dòng lãi suất')
                    ->reorderable(false)
                    ->maxItems(300),
            ]);
    }

    /** Hai dòng cùng kỳ hạn, kênh, lĩnh lãi, số tiền từ, ngày hiệu lực là trùng. */
    private static function noDuplicateRates(string $attribute, mixed $value, Closure $fail): void
    {
        $seen = [];
        foreach (array_values((array) $value) as $index => $row) {
            $key = implode('|', [
                (int) ($row['term_months'] ?? 0),
                $row['channel'] instanceof SavingsChannel ? $row['channel']->value : ($row['channel'] ?? ''),
                $row['interest_payment'] instanceof InterestPayment ? $row['interest_payment']->value : ($row['interest_payment'] ?? ''),
                (int) str_replace('.', '', (string) ($row['min_amount'] ?? '0')),
                substr((string) ($row['effective_from'] ?? ''), 0, 10),
            ]);

            if (isset($seen[$key])) {
                $fail('Dòng '.($index + 1).' trùng với dòng '.$seen[$key].' (cùng kỳ hạn, kênh, lĩnh lãi, số tiền từ, ngày hiệu lực).');

                return;
            }
            $seen[$key] = $index + 1;
        }
    }
}
