<?php

namespace App\Modules\Partner\Filament\Resources\OutboundLinks;

use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Partner\Filament\Resources\OutboundLinks\Pages\ManageOutboundLinks;
use App\Modules\Partner\Models\OutboundLink;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Liên kết đối tác và số click. Nút trên trang so sánh chỉ hiện khi link gắn với sản phẩm và đang bật. */
class OutboundLinkResource extends Resource
{
    protected static ?string $model = OutboundLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTopRightOnSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Đối tác';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'lien-ket-doi-tac';

    protected static ?string $modelLabel = 'liên kết đối tác';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Liên kết đối tác';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Tên gọi nội bộ')->required()->maxLength(150)
                ->placeholder('VD: Thẻ ABC Platinum — chiến dịch tháng 11'),
            Select::make('product_id')->label('Sản phẩm')
                ->relationship('product', 'name', fn (Builder $query) => $query->with('institution:id,short_name')->orderBy('name'))
                ->getOptionLabelFromRecordUsing(fn (FinancialProduct $product) => $product->name.' — '.$product->institution?->short_name.' ('.$product->type->getLabel().')')
                ->searchable()->preload()
                ->helperText('Nút đối tác hiện trên thẻ sản phẩm và trang chi tiết. Để trống nếu link chỉ dùng trong bài viết.'),
            TextInput::make('partner')->label('Đối tác / mạng affiliate')->maxLength(100),
            TextInput::make('destination_url')->label('URL đối tác')->required()->maxLength(2000)
                ->placeholder('https://doitac.example/dang-ky?sub_id='.OutboundLink::CLICK_PLACEHOLDER)
                ->helperText('Bắt đầu bằng https://. Chèn '.OutboundLink::CLICK_PLACEHOLDER.' ở tham số subID để đối soát từng click với báo cáo của đối tác.')
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) {
                        if (! OutboundLink::isValidDestination(trim((string) $value))) {
                            $fail('URL đối tác phải là địa chỉ https:// hợp lệ.');
                        }
                    },
                ]),
            TextInput::make('cta_label')->label('Chữ trên nút')->required()->maxLength(40)->default(OutboundLink::DEFAULT_CTA)
                ->helperText('VD: Mở thẻ, Mở tài khoản, Gửi tiết kiệm.'),
            Toggle::make('is_active')->label('Đang bật')->default(true)
                ->helperText('Tắt thì nút ẩn và /di/... trả 404. Link đã có click không xóa được, chỉ tắt.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('product.institution:id,short_name')
                ->withCount([
                    'clicks',
                    'clicks as recent_clicks_count' => fn (Builder $clicks) => $clicks->where('created_at', '>=', now()->subDays(30)),
                ]))
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable()->wrap()
                    ->description(fn (OutboundLink $link) => $link->partner),
                TextColumn::make('product.name')->label('Sản phẩm')->placeholder('—')->wrap()
                    ->description(fn (OutboundLink $link) => $link->product?->institution?->short_name),
                TextColumn::make('code')->label('Đường dẫn')
                    ->formatStateUsing(fn (OutboundLink $link) => $link->url())
                    ->copyable()->copyableState(fn (OutboundLink $link) => url($link->url()))->copyMessage('Đã sao chép'),
                IconColumn::make('is_active')->label('Bật')->boolean(),
                TextColumn::make('recent_clicks_count')->label('Click 30 ngày')->numeric(thousandsSeparator: '.')->sortable(),
                TextColumn::make('clicks_count')->label('Tổng click')->numeric(thousandsSeparator: '.')->sortable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Đang bật')])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOutboundLinks::route('/')];
    }
}
