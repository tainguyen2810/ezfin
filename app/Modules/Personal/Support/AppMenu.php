<?php

namespace App\Modules\Personal\Support;

use Illuminate\Support\Facades\Route;

/**
 * Menu ứng dụng quản lý tiền (sidebar desktop, thanh dưới mobile). Mục nào chưa có route thì tự ẩn,
 * nên màn hình mới chỉ cần đăng ký route là hiện trong menu.
 */
final class AppMenu
{
    /** [route, nhãn, icon, nhãn ngắn trên thanh dưới] */
    private const ITEMS = [
        ['personal.dashboard', 'Tổng quan', 'home', 'Tổng quan'],
        ['personal.transactions.index', 'Giao dịch', 'arrows-right-left', 'Giao dịch'],
        ['personal.wallets.index', 'Ví & tài khoản', 'wallet', 'Ví'],
        ['personal.budgets.index', 'Ngân sách', 'chart-pie', 'Ngân sách'],
        ['personal.goals.index', 'Mục tiêu', 'flag', 'Mục tiêu'],
        ['personal.net-worth.index', 'Tài sản & nợ', 'scale', 'Tài sản'],
        ['personal.reports.index', 'Báo cáo', 'chart-bar', 'Báo cáo'],
        ['planner.tools.index', 'Công cụ tính', 'calculator', 'Công cụ'],
        ['personal.settings.edit', 'Cài đặt', 'cog-6-tooth', 'Cài đặt'],
    ];

    /** Thứ tự ưu tiên trên thanh dưới (4 mục, nút + ở giữa). */
    private const BOTTOM = ['personal.dashboard', 'personal.transactions.index', 'personal.reports.index', 'personal.settings.edit',
        'personal.wallets.index', 'personal.budgets.index'];

    /** @return list<array{label: string, short: string, icon: string, href: string, active: bool, route: string}> */
    public static function items(): array
    {
        return collect(self::ITEMS)
            ->filter(fn (array $item) => Route::has($item[0]))
            ->map(fn (array $item) => [
                'route' => $item[0],
                'label' => $item[1],
                'icon' => $item[2],
                'short' => $item[3],
                'href' => route($item[0]),
                // Mục danh sách (….index) sáng cả khi đang ở trang thêm, sửa của nó.
                'active' => str_ends_with($item[0], '.index')
                    ? request()->routeIs(substr($item[0], 0, -6).'.*')
                    : request()->routeIs($item[0]),
            ])
            ->values()->all();
    }

    /** @return list<array{label: string, icon: string, href: string, active: bool}> */
    public static function bottom(): array
    {
        $items = collect(self::items())->keyBy('route');

        return collect(self::BOTTOM)
            ->filter(fn (string $route) => $items->has($route))
            ->take(4)
            ->map(fn (string $route) => [...$items[$route], 'label' => $items[$route]['short']])
            ->values()->all();
    }

    /**
     * Nút + : thêm giao dịch, hoặc thêm ví khi chưa có màn hình giao dịch.
     *
     * @return array{0: ?string, 1: string} [URL, nhãn cho trình đọc màn hình]
     */
    public static function add(): array
    {
        foreach (['personal.transactions.create' => 'Thêm giao dịch', 'personal.wallets.create' => 'Thêm ví'] as $route => $label) {
            if (Route::has($route)) {
                return [route($route), $label];
            }
        }

        return [null, ''];
    }
}
