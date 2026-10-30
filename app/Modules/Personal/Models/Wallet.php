<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\WalletType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** Số dư hiện tại (đồng). Khi có giao dịch: số dư ban đầu cộng thu, trừ chi. */
    public function balance(): int
    {
        return (int) round((float) $this->opening_balance);
    }
}
