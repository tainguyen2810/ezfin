<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Models\RecurringTransaction;
use App\Modules\Personal\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ghi nhận hoặc bỏ qua kỳ đang đến hạn của một khoản định kỳ, rồi chuyển sang kỳ sau.
 * Khóa dòng khoản định kỳ trong transaction nên chạy lại (lệnh hằng ngày, bấm hai lần) không tạo giao dịch trùng.
 */
class RecordRecurringOccurrence
{
    /**
     * @param  ?Carbon  $expectedDue  kỳ người dùng nhìn thấy khi bấm; đã đổi (vd. bấm hai lần) thì không ghi.
     * @param  ?Carbon  $occurredOn  ngày ghi giao dịch; mặc định là ngày đến hạn.
     */
    public function record(RecurringTransaction $item, ?Carbon $expectedDue = null, ?Carbon $occurredOn = null): ?Transaction
    {
        return DB::transaction(function () use ($item, $expectedDue, $occurredOn) {
            $locked = $this->lock($item, $expectedDue);
            if (! $locked || ! $locked->mode->recordsTransaction()) {
                return null;
            }

            $transaction = new Transaction;
            $transaction->forceFill([
                'user_id' => $locked->user_id,
                'wallet_id' => $locked->wallet_id,
                'category_id' => $locked->category_id,
                'type' => $locked->type,
                'amount' => $locked->amount,
                'occurred_on' => ($occurredOn ?? $locked->next_due_on)->toDateString(),
                'note' => $locked->name,
                'recurring_transaction_id' => $locked->id,
            ])->save();

            $locked->advance();
            $locked->save();
            $this->sync($item, $locked);

            return $transaction;
        });
    }

    /** Bỏ qua kỳ đang đến hạn (tháng này không phải trả), hoặc đánh dấu đã trả với khoản chỉ nhắc hạn. */
    public function skip(RecurringTransaction $item, ?Carbon $expectedDue = null): bool
    {
        return DB::transaction(function () use ($item, $expectedDue) {
            $locked = $this->lock($item, $expectedDue);
            if (! $locked) {
                return false;
            }
            $locked->advance();
            $locked->save();
            $this->sync($item, $locked);

            return true;
        });
    }

    /** Cập nhật bản đang dùng (gán qua cast để không giữ giá trị ngày cũ trong bộ nhớ đệm cast). */
    private function sync(RecurringTransaction $item, RecurringTransaction $locked): void
    {
        $item->forceFill([
            'next_due_on' => $locked->next_due_on,
            'last_occurrence_on' => $locked->last_occurrence_on,
            'reminded_for' => $locked->reminded_for,
        ])->syncOriginal();
    }

    private function lock(RecurringTransaction $item, ?Carbon $expectedDue): ?RecurringTransaction
    {
        $locked = RecurringTransaction::withoutGlobalScope('owner')->lockForUpdate()->find($item->id);

        if (! $locked?->next_due_on || ($expectedDue && ! $locked->next_due_on->isSameDay($expectedDue))) {
            return null;
        }

        return $locked;
    }
}
