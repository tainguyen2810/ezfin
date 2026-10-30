<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Models\Transaction;

/** Ghi hoặc sửa một khoản thu/chi. Dữ liệu đã được kiểm tra quyền sở hữu ví và danh mục. */
class RecordTransaction
{
    /** @param  array{type: string, wallet_id: int, category_id: int, amount: int, occurred_on: string, note: ?string}  $data */
    public function handle(array $data, ?Transaction $transaction = null): Transaction
    {
        $transaction ??= new Transaction;
        $transaction->fill([
            'type' => TransactionType::from($data['type']),
            'wallet_id' => $data['wallet_id'],
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'note' => $data['note'] ?? null,
            'transfer_group_id' => null,
        ])->save();

        return $transaction;
    }
}
