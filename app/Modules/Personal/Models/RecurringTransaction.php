<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\RecurringFrequency;
use App\Modules\Personal\Enums\RecurringMode;
use App\Modules\Personal\Enums\TransactionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Khoản thu/chi lặp lại, xử lý theo RecurringMode: tự ghi (lệnh `ezfin:recurring`), chờ người dùng xác nhận,
 * hoặc chỉ nhắc hạn. Ghi nhận/bỏ qua kỳ qua Actions\RecordRecurringOccurrence.
 *
 * @property int $id
 * @property int $user_id
 * @property ?int $wallet_id
 * @property ?int $category_id
 * @property string $name
 * @property TransactionType $type
 * @property string $amount
 * @property RecurringFrequency $frequency
 * @property Carbon $starts_on
 * @property ?Carbon $ends_on
 * @property ?Carbon $next_due_on
 * @property ?Carbon $last_occurrence_on
 * @property RecurringMode $mode
 * @property ?int $remind_days_before
 * @property ?Carbon $reminded_for
 */
class RecurringTransaction extends Model
{
    use BelongsToUser;

    /** Số ngày nhắc trước hạn được chọn trong form. */
    public const REMIND_OPTIONS = [0, 1, 2, 3, 5, 7];

    protected $fillable = ['wallet_id', 'category_id', 'name', 'type', 'amount', 'frequency', 'starts_on', 'ends_on',
        'mode', 'remind_days_before'];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'frequency' => RecurringFrequency::class,
            'starts_on' => DateOnly::class,
            'ends_on' => DateOnly::class,
            'next_due_on' => DateOnly::class,
            'last_occurrence_on' => DateOnly::class,
            'reminded_for' => DateOnly::class,
            'mode' => RecurringMode::class,
            'remind_days_before' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Còn kỳ phải trả/nhận. */
    public function scopeOngoing(Builder $query): void
    {
        $query->whereNotNull('next_due_on');
    }

    /** Kỳ đầu tiên vào hoặc sau ngày $date (không quá ngày kết thúc), null nếu đã hết kỳ. */
    public function occurrenceOnOrAfter(CarbonInterface $date): ?Carbon
    {
        $date = Carbon::instance($date)->startOfDay();
        if ($date->lte($this->starts_on)) {
            $next = $this->starts_on->copy();
        } else {
            // Ước lượng số kỳ đã qua rồi dò tiếp, tránh lặp từ đầu với khoản hằng tuần nhiều năm.
            $days = $this->starts_on->diffInDays($date);
            $n = max(0, (int) floor($days / match ($this->frequency) {
                RecurringFrequency::Weekly => 7, RecurringFrequency::Monthly => 31,
                RecurringFrequency::Quarterly => 92, RecurringFrequency::Yearly => 366,
            }) - 1);
            do {
                $next = $this->frequency->occurrence($this->starts_on, $n++);
            } while ($next->lt($date));
        }

        return $this->ends_on && $next->gt($this->ends_on) ? null : $next;
    }

    /**
     * Tính lại kỳ đến hạn sau khi tạo hoặc sửa lịch: kỳ đầu tiên từ hôm nay, không lặp lại kỳ đã ghi nhận.
     */
    public function scheduleFrom(CarbonInterface $today): void
    {
        $today = Carbon::instance($today)->startOfDay();
        $from = $this->last_occurrence_on && $this->last_occurrence_on->gte($today) ? $this->last_occurrence_on->copy()->addDay() : $today;
        $this->next_due_on = $this->occurrenceOnOrAfter($from);
        if ($this->reminded_for && ! $this->reminded_for->equalTo($this->next_due_on)) {
            $this->reminded_for = null;
        }
    }

    /** Chuyển sang kỳ sau kỳ đang đến hạn (sau khi ghi nhận hoặc bỏ qua). */
    public function advance(): void
    {
        $this->last_occurrence_on = $this->next_due_on;
        $this->next_due_on = $this->occurrenceOnOrAfter($this->next_due_on->copy()->addDay());
        $this->reminded_for = null;
    }

    public function amountInt(): int
    {
        return (int) round((float) $this->amount);
    }

    /** Số ngày đến hạn so với $today (âm = quá hạn), null nếu đã hết kỳ. */
    public function daysUntilDue(Carbon $today): ?int
    {
        return $this->next_due_on ? (int) $today->copy()->startOfDay()->diffInDays($this->next_due_on, false) : null;
    }

    /** Kỳ đã đến hoặc quá hạn, chờ người dùng bấm "Đã trả" (khoản không tự ghi). */
    public function awaitsConfirmation(Carbon $today): bool
    {
        return $this->mode !== RecurringMode::Auto && $this->next_due_on && $this->next_due_on->lte($today);
    }
}
