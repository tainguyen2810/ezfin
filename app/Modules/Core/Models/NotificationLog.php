<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Một lần gửi thông báo trên một kênh: số người nhận, thành công hay lỗi. Không lưu thông tin người nhận.
 *
 * @property int $id
 * @property string $key
 * @property string $channel
 * @property int $recipients
 * @property string $status
 * @property ?string $error
 * @property bool $test
 * @property Carbon $created_at
 */
class NotificationLog extends Model
{
    use MassPrunable;

    public const RETENTION_DAYS = 90;

    public const UPDATED_AT = null;

    protected $fillable = ['key', 'channel', 'recipients', 'status', 'error', 'test'];

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    protected function casts(): array
    {
        return ['recipients' => 'integer', 'test' => 'boolean', 'created_at' => 'datetime'];
    }
}
