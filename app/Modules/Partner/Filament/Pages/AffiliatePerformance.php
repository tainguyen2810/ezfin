<?php

namespace App\Modules\Partner\Filament\Pages;

use App\Modules\Core\Enums\Role;
use App\Modules\Partner\Actions\AffiliateReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use UnitEnum;

/** Dashboard hiệu quả đối tác: click, chuyển đổi, hoa hồng theo ngân hàng, link/sản phẩm, trang nguồn, theo ngày. */
class AffiliatePerformance extends Page
{
    public const PERIODS = [
        '7' => '7 ngày',
        '30' => '30 ngày',
        '90' => '90 ngày',
        'thang-nay' => 'Tháng này',
        'thang-truoc' => 'Tháng trước',
    ];

    protected string $view = 'partner::filament.affiliate-performance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Đối tác';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'hieu-qua-doi-tac';

    protected static ?string $title = 'Hiệu quả đối tác';

    #[Url(as: 'ky')]
    public string $period = '30';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([Role::SuperAdmin->value, Role::Finance->value]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function range(): array
    {
        return match ($this->period) {
            '7', '90' => [today()->subDays((int) $this->period - 1), today()],
            'thang-nay' => [today()->startOfMonth(), today()],
            'thang-truoc' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            default => [today()->subDays(29), today()],
        };
    }

    protected function getViewData(): array
    {
        if (! array_key_exists($this->period, self::PERIODS)) {
            $this->period = '30';
        }

        [$from, $to] = $this->range();

        return [
            'from' => $from,
            'to' => $to,
            'report' => app(AffiliateReport::class)->handle($from, $to),
        ];
    }
}
