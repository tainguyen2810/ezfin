<?php

namespace App\Modules\Core\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

/**
 * Trang mẫu giao diện nội bộ (/_ui): chỉ bật ngoài production, dữ liệu minh họa.
 * Dùng làm chuẩn nghiệm thu component trước khi làm từng nhóm màn hình.
 */
class StyleGuideController
{
    public function components(): View
    {
        // Minh họa trạng thái lỗi của ô nhập.
        ViewFacade::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['email' => 'Email không đúng định dạng.'])));

        return view('core::style-guide.components', [
            'navigation' => $this->sampleNavigation(),
            'paginator' => new LengthAwarePaginator(range(1, 10), 97, 10, 2, ['path' => url('/_ui')]),
        ]);
    }

    public function compare(): View
    {
        return view('core::style-guide.compare', [
            'navigation' => $this->sampleNavigation(active: 'Sản phẩm tài chính'),
            'cards' => [
                ['id' => 'mau-a', 'name' => 'Thẻ mẫu A Hoàn tiền', 'institution' => 'Ngân hàng mẫu 1', 'highlights' => ['Hoàn tiền đến 10%', 'Phí 0 đ năm đầu'], 'facts' => ['Hoàn tiền' => 'Đến 10%', 'Phí thường niên' => '0 đ năm đầu', 'Thu nhập tối thiểu' => '10.000.000 đ/tháng'], 'sponsored' => true],
                ['id' => 'mau-b', 'name' => 'Thẻ mẫu B Du lịch', 'institution' => 'Ngân hàng mẫu 2', 'highlights' => ['Dặm bay', 'Phòng chờ sân bay'], 'facts' => ['Tích điểm' => '1 dặm/20.000 đ', 'Phí thường niên' => '999.000 đ', 'Thu nhập tối thiểu' => '15.000.000 đ/tháng'], 'sponsored' => false],
                ['id' => 'mau-c', 'name' => 'Thẻ mẫu C Mua sắm', 'institution' => 'Ngân hàng mẫu 3', 'highlights' => ['Mua sắm online'], 'facts' => ['Hoàn tiền' => '5%', 'Phí thường niên' => '299.000 đ', 'Thu nhập tối thiểu' => '8.000.000 đ/tháng'], 'sponsored' => false],
            ],
        ]);
    }

    public function app(): View
    {
        return view('core::style-guide.app', [
            'menu' => $this->appMenu(),
            'bottom' => [
                ['label' => 'Tổng quan', 'icon' => 'home', 'href' => url('/_ui/ung-dung'), 'active' => true],
                ['label' => 'Giao dịch', 'icon' => 'arrows-right-left', 'href' => '#'],
                ['label' => 'Báo cáo', 'icon' => 'chart-bar', 'href' => '#'],
                ['label' => 'Cài đặt', 'icon' => 'cog-6-tooth', 'href' => '#'],
            ],
            'budgets' => [
                ['Ăn uống', 5824000, 7000000],
                ['Nhà ở', 3500000, 4000000],
                ['Đi lại', 1200000, 2000000],
                ['Giải trí', 1150000, 1000000],
            ],
            'goals' => [
                ['Mua nhà', 105000000, 300000000, 'Còn 18 tháng'],
                ['Du lịch Nhật Bản', 30000000, 50000000, 'Còn 6 tháng'],
            ],
            'transactions' => [
                ['Lương tháng 10', 'Ví ngân hàng', '05/10/2026', 15000000, 'banknotes'],
                ['Ăn trưa', 'Tiền mặt', '05/10/2026', -120000, 'shopping-bag'],
                ['Siêu thị', 'Ví ngân hàng', '04/10/2026', -450000, 'shopping-bag'],
                ['Chuyển sang ví tiết kiệm', 'Chuyển nội bộ', '03/10/2026', 0, 'arrows-right-left'],
            ],
        ]);
    }

    /** @return list<array{label: string, href: string, active?: bool}> */
    private function sampleNavigation(?string $active = null): array
    {
        return collect(['Sản phẩm tài chính', 'Công cụ', 'Kiến thức'])
            ->map(fn ($label) => ['label' => $label, 'href' => '#', 'active' => $label === $active])
            ->all();
    }

    /** @return list<array{label: string, icon: string, href: string, active?: bool}> */
    private function appMenu(): array
    {
        return [
            ['label' => 'Tổng quan', 'icon' => 'home', 'href' => url('/_ui/ung-dung'), 'active' => true],
            ['label' => 'Giao dịch', 'icon' => 'arrows-right-left', 'href' => '#'],
            ['label' => 'Ví và tài khoản', 'icon' => 'wallet', 'href' => '#'],
            ['label' => 'Ngân sách', 'icon' => 'chart-pie', 'href' => '#'],
            ['label' => 'Mục tiêu', 'icon' => 'flag', 'href' => '#'],
            ['label' => 'Tài sản và nợ', 'icon' => 'scale', 'href' => '#'],
            ['label' => 'Báo cáo', 'icon' => 'chart-bar', 'href' => '#'],
            ['label' => 'Thông báo', 'icon' => 'bell', 'href' => '#'],
            ['label' => 'Cài đặt', 'icon' => 'cog-6-tooth', 'href' => '#'],
        ];
    }
}
