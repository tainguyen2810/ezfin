<?php

namespace App\Support\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

/**
 * Menu chính của website công khai. Module đăng ký mục menu khi boot; chỉ mục
 * có route tồn tại và feature flag đang bật mới được hiển thị.
 */
class Navigation
{
    /** @var list<NavItem> */
    private array $items = [];

    public function add(NavItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return Collection<int, NavItem>
     */
    public function visible(): Collection
    {
        return collect($this->items)
            ->filter(fn (NavItem $item) => Route::has($item->route))
            ->filter(fn (NavItem $item) => $item->feature === null || Feature::active($item->feature))
            ->sortBy('order')
            ->values();
    }
}
