<?php

namespace App\Support\Filament;

use Closure;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;
use Throwable;

/**
 * Chạy một đoạn dựng view như thể không ở trong component Livewire. Khi đang dựng component, Livewire chèn chú thích
 * <!--[if BLOCK]--> quanh @if; với view Markdown (email) các chú thích này làm hỏng tiêu đề "# ...". Dùng cho xem trước
 * email trong trang quản trị. Livewire đổi cấu trúc nội bộ thì chạy bình thường (chỉ bản xem trước kém đẹp hơn).
 */
final class OutsideLivewireRender
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function run(Closure $callback): mixed
    {
        if (! class_exists(ExtendBlade::class) || ! property_exists(ExtendBlade::class, 'livewireComponents')) {
            return $callback();
        }

        try {
            $swap = Closure::bind(function (array $value): array {
                $old = static::$livewireComponents;
                static::$livewireComponents = $value;

                return $old;
            }, null, ExtendBlade::class);
        } catch (Throwable) {
            return $callback();
        }

        $saved = $swap([]);

        try {
            return $callback();
        } finally {
            $swap($saved);
        }
    }
}
