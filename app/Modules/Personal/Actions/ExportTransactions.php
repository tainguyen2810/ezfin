<?php

namespace App\Modules\Personal\Actions;

use App\Models\User;
use App\Modules\Personal\Models\Transaction;

/** Xuất toàn bộ giao dịch của một người ra CSV (UTF-8 có BOM để Excel đọc đúng tiếng Việt). */
class ExportTransactions
{
    public const HEADER = ['Ngày', 'Loại', 'Danh mục', 'Ví', 'Số tiền', 'Ghi chú'];

    /** @param  resource  $stream */
    public function write(User $user, $stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADER, escape: '');

        Transaction::withoutGlobalScopes()->where('user_id', $user->id)
            ->with(['category' => fn ($q) => $q->withoutGlobalScopes(), 'wallet' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderBy('occurred_on')->orderBy('id')
            ->lazy(500)
            ->each(fn (Transaction $t) => fputcsv($stream, [
                $t->occurred_on->format('d/m/Y'),
                $t->type->label(),
                $this->safe($t->category?->name ?? ''),
                $this->safe($t->wallet?->name ?? ''),
                $t->signedAmount(),
                $this->safe((string) $t->note),
            ], escape: ''));
    }

    /** Chặn công thức khi mở bằng Excel (chữ do người dùng nhập bắt đầu bằng =, +, -, @). */
    private function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
