<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Chuyển hướng URL cũ trên website công khai. Chỉ áp dụng khi URL cũ không còn trang (404),
 * nên không thể dùng để che một trang đang hoạt động.
 *
 * @property int $id
 * @property string $from_path
 * @property string $to_url
 * @property int $status_code
 * @property int $hits
 * @property ?Carbon $last_hit_at
 */
class Redirect extends Model
{
    use Auditable;

    public const STATUS_CODES = [301 => '301 – Vĩnh viễn', 302 => '302 – Tạm thời'];

    protected $fillable = ['from_path', 'to_url', 'status_code'];

    protected $attributes = ['status_code' => 301, 'hits' => 0];

    /** Không ghi số lượt truy cập vào audit log. */
    protected array $auditExclude = ['hits', 'last_hit_at'];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $redirect) {
            $redirect->from_path = self::normalizePath($redirect->from_path);
            $redirect->to_url = trim($redirect->to_url);
            $errors = [];
            $validTarget = self::isValidTarget($redirect->to_url);

            if ($validTarget && str_starts_with($redirect->to_url, '/')) {
                $redirect->to_url = self::normalizePath($redirect->to_url);
            }

            if ($redirect->from_path === '/') {
                $errors['from_path'] = 'Không chuyển hướng trang chủ.';
            }

            if (! $validTarget) {
                $errors['to_url'] = 'Đích phải là đường dẫn bắt đầu bằng / hoặc URL https://.';
            } elseif ($redirect->to_url === $redirect->from_path) {
                $errors['to_url'] = 'Đích trùng với URL cũ.';
            }

            if (! array_key_exists($redirect->status_code, self::STATUS_CODES)) {
                $errors['status_code'] = 'Chỉ dùng mã 301 hoặc 302.';
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    /**
     * Dạng chuẩn để so khớp: chỉ phần đường dẫn, bắt đầu bằng /, không có / cuối, chữ thường.
     * Nhận cả URL đầy đủ (dán từ trình duyệt).
     */
    public static function normalizePath(string $value): string
    {
        $path = (string) (parse_url(trim($value), PHP_URL_PATH) ?? '');
        $path = '/'.trim(rawurldecode($path), '/');

        return mb_strtolower($path);
    }

    public static function isValidTarget(string $target): bool
    {
        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return true;
        }

        return str_starts_with($target, 'https://') && filter_var($target, FILTER_VALIDATE_URL) !== false;
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chuyển hướng';
    }
}
