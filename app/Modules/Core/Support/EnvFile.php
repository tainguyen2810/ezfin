<?php

namespace App\Modules\Core\Support;

/** Đọc/ghi file .env (dùng cho trang cài đặt). Giữ nguyên các dòng khác và chú thích. */
class EnvFile
{
    public function __construct(private string $path) {}

    public static function app(): self
    {
        return new self(app()->environmentFilePath());
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function writable(): bool
    {
        return $this->exists() ? is_writable($this->path) : is_writable(dirname($this->path));
    }

    /** Tạo .env từ .env.example nếu chưa có. */
    public function ensureExists(string $example): void
    {
        if (! $this->exists()) {
            copy($example, $this->path);
        }
    }

    public function get(string $key): ?string
    {
        if (! $this->exists() || ! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($this->path), $match)) {
            return null;
        }

        $value = trim($match[1]);

        return str_starts_with($value, '"') && str_ends_with($value, '"') ? stripcslashes(substr($value, 1, -1)) : $value;
    }

    /** @param  array<string, string|null>  $values */
    public function set(array $values): void
    {
        $content = $this->exists() ? (string) file_get_contents($this->path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.self::quote((string) $value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';

            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content, 1)
                : rtrim($content, "\n")."\n".$line."\n";
        }

        file_put_contents($this->path, $content, LOCK_EX);
    }

    private static function quote(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+=-]+$/', $value)) {
            return $value;
        }

        return '"'.addcslashes($value, '\\"$').'"';
    }
}
