<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Enums\WalletType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ví hoặc tài khoản chứa tiền của người dùng (tiền mặt, ngân hàng, ví điện tử, sổ tiết kiệm).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property WalletType $type
 * @property ?string $institution
 * @property string $opening_balance
 * @property bool $include_in_total
 */
#[UseFactory(WalletFactory::class)]
class Wallet extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['name', 'type', 'institution', 'opening_balance', 'include_in_total'];

    protected function casts(): array
    {
        return [
            'type' => WalletType::class,
            'opening_balance' => 'decimal:2',
            'include_in_total' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** Nạp sẵn tổng tiền vào/ra để tính số dư danh sách ví bằng một truy vấn. */
    public function scopeWithBalance(Builder $query): void
    {
        $query->withSum(['transactions as inflow' => fn (Builder $q) => $q->whereIn('type', TransactionType::inflows())], 'amount')
            ->withSum(['transactions as outflow' => fn (Builder $q) => $q->whereIn('type', TransactionType::outflows())], 'amount');
    }

    /** Số dư hiện tại (đồng) = số dư ban đầu + tiền vào − tiền ra. */
    public function balance(): int
    {
        if (! array_key_exists('inflow', $this->attributes)) {
            $this->attributes['inflow'] = $this->transactions()->whereIn('type', TransactionType::inflows())->sum('amount');
            $this->attributes['outflow'] = $this->transactions()->whereIn('type', TransactionType::outflows())->sum('amount');
        }

        return (int) round((float) $this->opening_balance + (float) $this->attributes['inflow'] - (float) $this->attributes['outflow']);
    }
}
