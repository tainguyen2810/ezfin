<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Thông số khoản vay. Tiền: VND, DECIMAL(18,2). Lãi, tỷ lệ: phần trăm, DECIMAL(7,4).
 * Trường để trống nghĩa là chưa có thông tin, khác với 0.
 *
 * @property int $product_id
 * @property LoanKind $kind
 * @property ?string $promo_rate
 * @property ?int $promo_months
 * @property ?string $floating_margin
 * @property ?string $floating_note
 * @property ?string $min_amount
 * @property ?string $max_amount
 * @property ?int $max_term_months
 * @property ?string $max_ltv
 * @property ?string $min_monthly_income
 * @property ?string $processing_fee
 * @property ?string $prepayment_fee
 * @property ?string $prepayment_note
 */
class LoanDetail extends Model
{
    use Auditable;

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $fillable = [
        'kind', 'promo_rate', 'promo_months', 'floating_margin', 'floating_note', 'min_amount', 'max_amount',
        'max_term_months', 'max_ltv', 'min_monthly_income', 'processing_fee', 'prepayment_fee', 'prepayment_note',
    ];

    protected function casts(): array
    {
        return [
            'kind' => LoanKind::class,
            'promo_rate' => 'decimal:4',
            'promo_months' => 'integer',
            'floating_margin' => 'decimal:4',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'max_term_months' => 'integer',
            'max_ltv' => 'decimal:4',
            'min_monthly_income' => 'decimal:2',
            'processing_fee' => 'decimal:4',
            'prepayment_fee' => 'decimal:4',
        ];
    }

    /** Lãi ưu đãi chỉ áp dụng một thời gian rồi thả nổi. */
    public function isFloating(): bool
    {
        return $this->promo_months !== null;
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chi tiết khoản vay';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id');
    }
}
