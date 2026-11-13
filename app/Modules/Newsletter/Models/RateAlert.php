<?php

namespace App\Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Báo khi lãi suất tiết kiệm cao nhất thị trường của một kỳ hạn tăng.
 * baseline_rate: mức đã biết gần nhất (lúc đăng ký, lần báo trước, hoặc mức thấp hơn khi lãi giảm).
 *
 * @property int $id
 * @property int $subscriber_id
 * @property int $term_months
 * @property ?string $baseline_rate
 * @property ?Carbon $last_notified_at
 */
class RateAlert extends Model
{
    protected $fillable = ['term_months', 'baseline_rate'];

    protected function casts(): array
    {
        return [
            'term_months' => 'integer',
            'baseline_rate' => 'decimal:4',
            'last_notified_at' => 'datetime',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
