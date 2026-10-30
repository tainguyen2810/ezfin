<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\NetWorthKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tài sản hoặc khoản nợ nhập tay, dùng để tính tài sản ròng.
 *
 * @property NetWorthKind $kind
 * @property string $name
 * @property string $amount
 * @property ?string $interest_rate
 * @property ?string $monthly_payment
 * @property Carbon $valued_on
 */
class NetWorthItem extends Model
{
    use BelongsToUser;

    protected $fillable = ['kind', 'name', 'amount', 'interest_rate', 'monthly_payment', 'valued_on', 'note'];

    protected function casts(): array
    {
        return [
            'kind' => NetWorthKind::class,
            'amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'monthly_payment' => 'decimal:2',
            'valued_on' => DateOnly::class,
        ];
    }

    public function scopeDebts(Builder $query, bool $debt = true): void
    {
        $kinds = collect(NetWorthKind::cases())->filter(fn (NetWorthKind $kind) => $kind->isDebt() === $debt)->map->value;
        $query->whereIn('kind', $kinds->all());
    }

    public function isDebt(): bool
    {
        return $this->kind->isDebt();
    }

    public function amountInt(): int
    {
        return (int) round((float) $this->amount);
    }
}
