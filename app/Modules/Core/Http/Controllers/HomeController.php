<?php

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Compare\Actions\CompareSavingsRates;
use App\Modules\Compare\Support\SavingsFilters;
use App\Modules\Content\Actions\LatestArticles;
use App\Modules\Planner\Tools\ToolRegistry;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

/**
 * Trang chủ ghép từ các module đang bật, chỉ dùng dữ liệu thật: mục nào chưa có dữ liệu thì ẩn.
 */
class HomeController
{
    public const TOP_SAVINGS = 5;

    public function __invoke(): View
    {
        $compare = Feature::active('compare');
        $savingsFilters = new SavingsFilters;

        return view('core::home', [
            'compare' => $compare,
            'savingsFilters' => $savingsFilters,
            'savings' => $compare ? app(CompareSavingsRates::class)->handle($savingsFilters)->take(self::TOP_SAVINGS) : collect(),
            'tools' => Feature::active('planner') ? app(ToolRegistry::class)->all() : [],
            'articles' => Feature::active('content') ? app(LatestArticles::class)->handle(3) : collect(),
        ]);
    }
}
