<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Models\Transaction;
use Illuminate\Support\Facades\DB;

/** Xóa một giao dịch; với chuyển tiền thì xóa cả hai vế. */
class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->transfer_group_id
                ? Transaction::where('transfer_group_id', $transaction->transfer_group_id)->delete()
                : $transaction->delete();
        });
    }
}
