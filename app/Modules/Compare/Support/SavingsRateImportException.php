<?php

namespace App\Modules\Compare\Support;

use RuntimeException;

/** File import có lỗi: không dòng nào được ghi. errors: số dòng trong file => danh sách lỗi (dòng 0 = lỗi cả file). */
final class SavingsRateImportException extends RuntimeException
{
    /** @param  array<int, list<string>>  $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('File lãi suất có lỗi.');
    }

    /** @return list<string> */
    public function messages(): array
    {
        return collect($this->errors)
            ->flatMap(fn (array $messages, int $line) => array_map(fn ($message) => ($line > 0 ? "Dòng {$line}: " : '').$message, $messages))
            ->values()
            ->all();
    }
}
