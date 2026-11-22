<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Notifications\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

    public static function record(string $key, Channel $channel, int $recipients, ?string $error = null, bool $test = false): self
    {
        return static::create([
            'key' => $key,
            'channel' => $channel->value,
            'recipients' => $recipients,
            'status' => $error === null ? 'sent' : 'failed',
            'error' => $error === null ? null : Str::limit($error, 490),
            'test' => $test,
        ]);
    }

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
