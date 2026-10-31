<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Một dòng thu, chi hoặc một vế của chuyển tiền. Số tiền luôn dương, chiều tiền theo loại.
 * Tạo/sửa/xóa qua Actions\RecordTransaction và Actions\RecordTransfer.
 *
 * @property int $id
 * @property int $wallet_id
 * @property ?int $category_id
 * @property TransactionType $type
 * @property string $amount
 * @property Carbon $occurred_on
 * @property ?string $note
 * @property ?string $transfer_group_id
 * @property ?int $recurring_transaction_id
 */
class Transaction extends Model
{
    use BelongsToUser;

    protected $fillable = ['wallet_id', 'category_id', 'type', 'amount', 'occurred_on', 'note', 'transfer_group_id'];

    protected function casts(): array
    {
        return ['type' => TransactionType::class, 'amount' => 'decimal:2', 'occurred_on' => DateOnly::class];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Khoản định kỳ đã sinh ra giao dịch này (nếu có). */
    public function recurring(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_transaction_id');
    }

    /** Vế còn lại của chuyển tiền. */
    public function counterpart(): ?self
    {
        return $this->transfer_group_id
            ? self::where('transfer_group_id', $this->transfer_group_id)->whereKeyNot($this->id)->first()
            : null;
    }

    /** Chỉ thu và chi thật (bỏ chuyển tiền giữa các ví của chính mình). */
    public function scopeIncomeOrExpense(Builder $query): void
    {
        $query->whereIn('type', [TransactionType::Income, TransactionType::Expense]);
    }

    public function scopeInMonth(Builder $query, Carbon $month): void
    {
        $query->whereBetween('occurred_on', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]);
    }

    public function amountInt(): int
    {
        return (int) round((float) $this->amount);
    }

    /** Số tiền có dấu theo chiều tiền của ví: + vào, − ra. */
    public function signedAmount(): int
    {
        return $this->type->isInflow() ? $this->amountInt() : -$this->amountInt();
    }
}
