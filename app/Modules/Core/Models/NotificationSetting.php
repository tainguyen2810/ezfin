<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\Auditable;
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
