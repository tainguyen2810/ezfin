<?php

namespace App\Modules\Partner\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Một lượt bấm sang đối tác. Không lưu IP gốc: chỉ lưu băm có khóa bí mật của ứng dụng.
 *
 * @property int $id
 * @property int $link_id
 * @property string $click_id
 * @property ?string $ip_hash
 * @property ?string $user_agent
 * @property ?string $referer_path
 * @property Carbon $created_at
 */
class OutboundClick extends Model
{
    use MassPrunable;

    public const RETENTION_MONTHS = 12;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function link(): BelongsTo
    {
        return $this->belongsTo(OutboundLink::class, 'link_id');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }
}
