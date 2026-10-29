<?php

namespace App\Modules\Compare\Models;

use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thông số tài khoản thanh toán. Tiền: VND, DECIMAL(18,2). Trường để trống nghĩa là chưa có thông tin, khác với 0.
 *
 * @property int $product_id
 * @property ?string $monthly_fee
 * @property ?string $monthly_fee_note
 * @property ?string $min_balance
 * @property ?string $transfer_fee
 * @property ?string $atm_withdrawal_fee
 * @property ?list<string> $features
 */
class BankAccountDetail extends Model
{
    use Auditable;

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $fillable = ['monthly_fee', 'monthly_fee_note', 'min_balance', 'transfer_fee', 'atm_withdrawal_fee', 'features'];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'min_balance' => 'decimal:2',
            'transfer_fee' => 'decimal:2',
            'atm_withdrawal_fee' => 'decimal:2',
            'features' => 'array',
        ];
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chi tiết tài khoản thanh toán';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id');
    }
}
