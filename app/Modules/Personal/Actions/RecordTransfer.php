<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Chuyển tiền giữa hai ví của cùng người dùng: hai dòng (chuyển đi, chuyển đến) chung transfer_group_id,
 * ghi trong một DB transaction. Không tính vào thu/chi.
 */
class RecordTransfer
{
    /**
     * @param  array{from_wallet_id: int, to_wallet_id: int, amount: int, occurred_on: string, note: ?string}  $data
     * @return Transaction vế chuyển đi
     */
    public function handle(array $data, ?string $groupId = null): Transaction
    {
        return DB::transaction(function () use ($data, $groupId) {
            $groupId ??= (string) Str::uuid();
            $legs = Transaction::where('transfer_group_id', $groupId)->get()->keyBy(fn (Transaction $t) => $t->type->value);

            $shared = ['amount' => $data['amount'], 'occurred_on' => $data['occurred_on'], 'note' => $data['note'] ?? null,
                'category_id' => null, 'transfer_group_id' => $groupId];

            $out = ($legs[TransactionType::TransferOut->value] ?? new Transaction)
                ->fill([...$shared, 'type' => TransactionType::TransferOut, 'wallet_id' => $data['from_wallet_id']]);
            $out->save();

            ($legs[TransactionType::TransferIn->value] ?? new Transaction)
                ->fill([...$shared, 'type' => TransactionType::TransferIn, 'wallet_id' => $data['to_wallet_id']])
                ->save();

            return $out;
        });
    }
}
