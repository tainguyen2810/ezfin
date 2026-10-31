<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Imports\StatementMapping;
use App\Modules\Personal\Imports\StatementValues;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Nhập sao kê vào một ví. Mỗi dòng có mã `import_hash` (ví, ngày, số tiền, nội dung, thứ tự lặp trong file)
 * nên nhập lại cùng sao kê không tạo trùng. Dòng trùng ngày, số tiền với giao dịch nhập tay được đánh dấu "có thể trùng".
 */
class ImportStatement
{
    public const OK = 'ok';

    public const DUPLICATE = 'duplicate';

    public const POSSIBLE_DUPLICATE = 'possible_duplicate';

    public const ERROR = 'error';

    /**
     * @param  list<list<mixed>>  $rows
     * @return Collection<int, array{line: int, date: ?string, description: string, amount: ?int, type: ?TransactionType, hash: ?string, status: string, error: ?string}>
     */
    public function preview(array $rows, StatementMapping $mapping, Wallet $wallet): Collection
    {
        $seen = [];
        $lines = collect(array_slice($rows, $mapping->headerRow + 1, preserve_keys: true))
            ->map(function (array $row, int $index) use ($mapping, $wallet, &$seen) {
                $cell = fn (?int $column) => $column === null ? null : ($row[$column] ?? null);
                $description = Str::limit(preg_replace('/\s+/u', ' ', trim((string) $cell($mapping->description))), 250, '…');
                $line = ['line' => $index + 1, 'date' => null, 'description' => $description, 'amount' => null, 'type' => null, 'hash' => null, 'status' => self::ERROR, 'error' => null];

                $date = StatementValues::date($cell($mapping->date), $mapping->dateFormat);
                if ($mapping->usesTwoColumns()) {
                    $debit = StatementValues::amount($cell($mapping->debit));
                    $credit = StatementValues::amount($cell($mapping->credit));
                    $amount = $credit ? abs($credit) : ($debit ? -abs($debit) : null);
                } else {
                    $amount = StatementValues::amount($cell($mapping->amount));
                }

                if (! $date) {
                    // Dòng tổng cộng, số dư cuối kỳ... không có ô ngày: bỏ qua lặng lẽ. Có ô ngày mà sai thì báo lỗi.
                    return trim((string) $cell($mapping->date)) === '' ? null : [...$line, 'error' => 'Không đọc được ngày'];
                }
                if (! $amount) {
                    return [...$line, 'date' => $date->toDateString(), 'error' => 'Không có số tiền'];
                }

                $type = $amount > 0 ? TransactionType::Income : TransactionType::Expense;
                $key = implode('|', [$wallet->id, $date->toDateString(), $amount, Str::lower($description)]);
                $seen[$key] = ($seen[$key] ?? 0) + 1;

                return [...$line, 'date' => $date->toDateString(), 'amount' => abs($amount), 'type' => $type,
                    'hash' => sha1($key.'|'.$seen[$key]), 'status' => self::OK];
            })
            ->filter()
            ->values();

        return $this->markDuplicates($lines, $wallet);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines  kết quả preview()
     * @return array{created: int, duplicates: int, errors: int}
     */
    public function import(Collection $lines, Wallet $wallet, Category $expenseCategory, Category $incomeCategory, bool $includePossibleDuplicates): array
    {
        $importable = $lines->filter(fn (array $line) => $line['status'] === self::OK
            || ($includePossibleDuplicates && $line['status'] === self::POSSIBLE_DUPLICATE));

        DB::transaction(function () use ($importable, $wallet, $expenseCategory, $incomeCategory) {
            foreach ($importable as $line) {
                $transaction = new Transaction([
                    'wallet_id' => $wallet->id,
                    'category_id' => $line['type'] === TransactionType::Income ? $incomeCategory->id : $expenseCategory->id,
                    'type' => $line['type'],
                    'amount' => $line['amount'],
                    'occurred_on' => $line['date'],
                    'note' => $line['description'] !== '' ? $line['description'] : null,
                ]);
                $transaction->import_hash = $line['hash'];
                $transaction->save();
            }
        });

        return [
            'created' => $importable->count(),
            'duplicates' => $lines->count() - $importable->count() - $lines->where('status', self::ERROR)->count(),
            'errors' => $lines->where('status', self::ERROR)->count(),
        ];
    }

    /** @param  Collection<int, array<string, mixed>>  $lines */
    private function markDuplicates(Collection $lines, Wallet $wallet): Collection
    {
        $ok = $lines->where('status', self::OK);
        if ($ok->isEmpty()) {
            return $lines;
        }

        $existing = Transaction::query()->where('wallet_id', $wallet->id)
            ->whereBetween('occurred_on', [$ok->min('date'), $ok->max('date')])
            ->get(['import_hash', 'occurred_on', 'amount', 'type']);
        $hashes = $existing->pluck('import_hash')->filter()->flip();
        $manual = $existing->whereNull('import_hash')
            ->map(fn (Transaction $t) => $t->occurred_on->toDateString().'|'.$t->amountInt().'|'.$t->type->value)
            ->countBy()->all();

        return $lines->map(function (array $line) use ($hashes, &$manual) {
            if ($line['status'] !== self::OK) {
                return $line;
            }
            if ($hashes->has($line['hash'])) {
                return [...$line, 'status' => self::DUPLICATE];
            }
            $key = $line['date'].'|'.$line['amount'].'|'.$line['type']->value;
            if (($manual[$key] ?? 0) > 0) {
                $manual[$key]--;

                return [...$line, 'status' => self::POSSIBLE_DUPLICATE];
            }

            return $line;
        });
    }
}
