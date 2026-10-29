<?php

namespace App\Support;

use InvalidArgumentException;

/** Icon SVG (Heroicons, MIT) lưu trong resources/svg/icons, nhúng trực tiếp vào HTML. */
final class Icons
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function svg(string $name, string $class = 'size-5'): string
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new InvalidArgumentException("Tên icon không hợp lệ: [$name]");
        }

        $svg = self::$cache[$name] ??= file_get_contents(resource_path("svg/icons/{$name}.svg"))
            ?: throw new InvalidArgumentException("Không có icon [$name]");

        return str_replace('<svg ', '<svg class="'.e($class).'" ', $svg);
    }
}
