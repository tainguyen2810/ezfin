<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thông số thẻ tín dụng. Tiền: VND, DECIMAL(18,2). Tỷ lệ: phần trăm, DECIMAL(7,4).
 * Trường để trống nghĩa là chưa có thông tin, khác với 0.
 *
 * @property int $product_id
 * @property ?CardNetwork $network
 * @property ?string $annual_fee
 * @property ?string $min_monthly_income
 * @property ?string $interest_rate
 * @property ?string $cashback_rate
 * @property ?list<string> $benefits
 */
class CreditCardDetail extends Model
{
    use Auditable;

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $fillable = [
        'network', 'annual_fee', 'annual_fee_note', 'issuance_fee', 'min_monthly_income', 'interest_rate',
        'interest_free_days', 'cashback_rate', 'cashback_monthly_cap', 'foreign_transaction_fee', 'benefits',
    ];

    protected function casts(): array
    {
        return [
            'network' => CardNetwork::class,
            'annual_fee' => 'decimal:2',
            'issuance_fee' => 'decimal:2',
            'min_monthly_income' => 'decimal:2',
            'cashback_monthly_cap' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'cashback_rate' => 'decimal:4',
            'foreign_transaction_fee' => 'decimal:4',
            'interest_free_days' => 'integer',
            'benefits' => 'array',
        ];
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chi tiết thẻ tín dụng';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id');
    }
}
