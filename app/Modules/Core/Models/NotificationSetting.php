<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\Auditable;
use App\Modules\Core\Notifications\NotificationCatalog;
use App\Modules\Core\Notifications\NotificationType;
use Illuminate\Database\Eloquent\Model;

/**
 * Cài đặt quản trị viên đã đổi cho một loại thông báo. Ô để trống (null) = dùng mặc định trong code.
 *
 * @property int $id
 * @property string $key
 * @property bool $enabled
 * @property ?list<string> $channels
 * @property ?list<string> $recipient_roles
 * @property ?list<string> $extra_emails
 * @property ?int $repeat_hours
 */
class NotificationSetting extends Model
{
    use Auditable;

    protected $fillable = ['key', 'enabled', 'channels', 'recipient_roles', 'extra_emails', 'repeat_hours'];

    protected $attributes = ['enabled' => true];

    /**
     * Tạo dòng (giá trị trống = mặc định) cho mọi loại đã đăng ký để trang quản trị liệt kê đủ. Dùng insertOrIgnore nên
     * không ghi nhật ký và không đè cài đặt đã có.
     */
    public static function syncCatalog(): void
    {
        $now = now();
        $rows = array_map(fn (string $key) => ['key' => $key, 'enabled' => true, 'created_at' => $now, 'updated_at' => $now], array_keys(app(NotificationCatalog::class)->all()));

        if ($rows !== []) {
            static::query()->insertOrIgnore($rows);
        }
    }

    public function type(): ?NotificationType
    {
        $catalog = app(NotificationCatalog::class);

        return $catalog->has($this->key) ? $catalog->get($this->key) : null;
    }

    public static function auditSubjectLabel(): string
    {
        return 'Cài đặt thông báo';
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'channels' => 'array',
            'recipient_roles' => 'array',
            'extra_emails' => 'array',
            'repeat_hours' => 'integer',
        ];
    }
}
