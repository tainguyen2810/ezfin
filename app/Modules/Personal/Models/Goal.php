<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Mục tiêu tài chính (mua nhà, du lịch...). Số đã có = tổng các lần góp/rút.
 *
 * @property string $name
 * @property string $target_amount
 * @property ?Carbon $deadline
 * @property string $icon
 * @property string $color
 */
class Goal extends Model
{
    use BelongsToUser;

    /** Icon gợi ý cho mục tiêu (có trong resources/svg/icons). */
    public const ICONS = ['home', 'truck', 'globe-asia-australia', 'academic-cap', 'heart', 'gift', 'device-phone-mobile',
        'shield-check', 'briefcase', 'users', 'sparkles', 'banknotes', 'trophy', 'flag'];

    protected $fillable = ['name', 'target_amount', 'deadline', 'icon', 'color'];

    protected function casts(): array
    {
        return ['target_amount' => 'decimal:2', 'deadline' => DateOnly::class];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function scopeWithSaved(Builder $query): void
    {
        $query->withSum('contributions as saved', 'amount');
    }

    public function target(): int
    {
        return (int) round((float) $this->target_amount);
    }

    public function savedAmount(): int
    {
        $this->attributes['saved'] ??= $this->contributions()->sum('amount');

        return (int) round((float) $this->attributes['saved']);
    }

    public function remaining(): int
    {
        return max(0, $this->target() - $this->savedAmount());
    }

    public function percent(): int
    {
        return $this->target() > 0 ? min(100, (int) floor($this->savedAmount() / $this->target() * 100)) : 0;
    }

    public function isCompleted(): bool
    {
        return $this->savedAmount() >= $this->target();
    }

    /** Số tháng còn để dành tới hạn, tính theo tháng lịch (hạn trong tháng này: 1); 0 nếu đã quá hạn, null nếu không đặt hạn. */
    public function monthsLeft(): ?int
    {
        if (! $this->deadline) {
            return null;
        }

        if ($this->deadline->lt(today())) {
            return 0;
        }

        return max(1, ($this->deadline->year - today()->year) * 12 + $this->deadline->month - today()->month);
    }

    /** Cần để dành mỗi tháng để kịp hạn; null nếu không có hạn hoặc đã xong. */
    public function monthlyNeeded(): ?int
    {
        $months = $this->monthsLeft();

        if ($months === null || $this->isCompleted()) {
            return null;
        }

        return (int) ceil($this->remaining() / max(1, $months));
    }

    public function colorClasses(): string
    {
        return Category::COLORS[$this->color] ?? Category::COLORS['slate'];
    }
}
