<?php

namespace App\Modules\Core\Notifications;

/**
 * Thay biến dạng {ten_bien} trong mẫu. Cố ý không dùng Blade: mẫu do quản trị viên sửa nên không được chạy code.
 * Biến không có giá trị thì giữ nguyên chữ để dễ phát hiện khi xem trước.
 */
class TemplateRenderer
{
    public const PATTERN = '/\{([a-z0-9_]+)\}/';

    /**
     * @param  array<string, scalar|null>  $variables
     * @param  callable(string): string|null  $escape  Cách thoát giá trị biến cho từng định dạng (Markdown, HTML).
     */
    public static function render(string $template, array $variables, ?callable $escape = null): string
    {
        return preg_replace_callback(self::PATTERN, function (array $match) use ($variables, $escape) {
            if (! array_key_exists($match[1], $variables)) {
                return $match[0];
            }

            $value = (string) $variables[$match[1]];

            return $escape ? $escape($value) : $value;
        }, $template) ?? $template;
    }

    /** @return list<string> Tên biến xuất hiện trong mẫu. */
    public static function placeholders(string $template): array
    {
        preg_match_all(self::PATTERN, $template, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string> Biến dùng trong mẫu nhưng loại thông báo không có.
     */
    public static function unknown(string $template, array $allowed): array
    {
        return array_values(array_diff(self::placeholders($template), $allowed));
    }

    /** Thoát ký tự Markdown trong giá trị biến (lỗi nguồn có thể chứa * _ [ ]...). HTML được thoát khi dựng Markdown. */
    public static function escapeMarkdown(string $value): string
    {
        return addcslashes($value, '\\`*_[]#|~');
    }
}
