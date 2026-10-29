<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Core\Concerns\Auditable;
use Database\Factories\SavingsRateFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lãi suất tiết kiệm (%/năm) của một sản phẩm. Tiền: VND, DECIMAL(18,2). Lãi suất: DECIMAL(7,4).
 *
 * @property int $id
 * @property int $product_id
 * @property int $term_months
 * @property SavingsChannel $channel
 * @property InterestPayment $interest_payment
 * @property string $min_amount
 * @property ?string $max_amount
 * @property string $rate
 * @property Carbon $effective_from
 */
#[UseFactory(SavingsRateFactory::class)]
class SavingsRate extends Model
{
    use Auditable, HasFactory;

    /** Kỳ hạn phổ biến (tháng) để chọn nhanh trong form. */
    public const COMMON_TERMS = [0, 1, 2, 3, 6, 9, 12, 13, 15, 18, 24, 36];

    public const MAX_TERM_MONTHS = 120;

    /** Giới hạn hợp lý để bắt lỗi gõ nhầm (ví dụ 52 thay vì 5,2). */
    public const MAX_RATE = 20;

    protected $fillable = ['term_months', 'channel', 'interest_payment', 'min_amount', 'max_amount', 'rate', 'effective_from'];

    protected $attributes = [
        'interest_payment' => 'end_of_term',
        'min_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'term_months' => 'integer',
            'channel' => SavingsChannel::class,
            'interest_payment' => InterestPayment::class,
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'effective_from' => 'date',
        ];
    }

    public static function auditSubjectLabel(): string
    {
        return 'Lãi suất tiết kiệm';
    }

    public static function termLabel(int $months): string
    {
        return $months === 0 ? 'Không kỳ hạn' : $months.' tháng';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id');
    }
}
