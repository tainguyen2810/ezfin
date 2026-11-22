<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mẫu thông báo quản trị viên đã sửa cho một kênh. Ô để trống = dùng chữ mặc định trong code.
 *
 * @property int $id
 * @property string $key
 * @property string $channel
 * @property ?string $subject
 * @property ?string $greeting
 * @property ?string $body
 * @property ?string $action_label
 * @property ?int $updated_by
 */
class NotificationTemplate extends Model
{
    use Auditable;

    protected $fillable = ['key', 'channel', 'subject', 'greeting', 'body', 'action_label', 'updated_by'];

    public static function auditSubjectLabel(): string
    {
        return 'Mẫu thông báo';
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
