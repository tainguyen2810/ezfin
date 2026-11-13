<?php

namespace App\Modules\Partner\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Nhập file đối soát chuyển đổi (CSV UTF-8, phân cách "," hoặc ";") của một mạng affiliate.
 * Kiểm tra cả file: có lỗi thì không ghi dòng nào. Nhập lại cùng file chỉ cập nhật, không tạo trùng.
 */
class ImportConversions
{
    public const COLUMNS = ['click_id', 'transaction_id', 'status', 'commission', 'occurred_at'];

    public const MAX_ROWS = 20_000;

    public function __construct(private readonly RecordConversion $record) {}

    /**
     * @return array{created: int, updated: int}
     *
     * @throws InvalidArgumentException danh sách lỗi theo dòng, mỗi lỗi một dòng trong thông báo
     */
    public function handle(string $network, string $path): array
    {
        $handle = fopen($path, 'r') ?: throw new RuntimeException('Không đọc được file.');
        $first = (string) fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($cell) => strtolower(trim((string) $cell)), str_getcsv($first, $delimiter, '"', ''));

        $missing = array_diff(['status'], $header);
        if ($missing !== [] || (! in_array('click_id', $header, true) && ! in_array('transaction_id', $header, true))) {
            fclose($handle);
            throw new InvalidArgumentException('Dòng tiêu đề cần cột status và click_id hoặc transaction_id. Các cột: '.implode(', ', self::COLUMNS).'.');
        }

        $result = ['created' => 0, 'updated' => 0];
        $errors = [];

        DB::beginTransaction();
        try {
            $line = 1;
            while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;
                if ($cells === [null] || implode('', array_map('trim', array_map('strval', $cells))) === '') {
                    continue;
                }
                if ($line - 1 > self::MAX_ROWS) {
                    $errors[] = 'File quá '.number_format(self::MAX_ROWS, 0, ',', '.').' dòng, hãy chia nhỏ.';
                    break;
                }

                $row = [];
                foreach ($header as $index => $column) {
                    $row[$column] = isset($cells[$index]) ? trim((string) $cells[$index]) : null;
                }

                try {
                    $created = $this->record->handle($network, [
                        'click_id' => $row['click_id'] ?? null,
                        'transaction_id' => $row['transaction_id'] ?? null,
                        'status' => $row['status'] ?? null,
                        'commission' => $row['commission'] ?? null,
                        'occurred_at' => $row['occurred_at'] ?? null,
                    ])['created'];
                    $result[$created ? 'created' : 'updated']++;
                } catch (InvalidArgumentException $exception) {
                    $errors[] = "Dòng {$line}: ".$exception->getMessage();
                }
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        } finally {
            fclose($handle);
        }

        if ($errors !== []) {
            DB::rollBack();
            throw new InvalidArgumentException(implode("\n", $errors));
        }
        DB::commit();

        return $result;
    }

    public static function template(): string
    {
        return "\xEF\xBB\xBF".implode(',', self::COLUMNS)."\n"
            ."AbCdEfGhIjKlMnOpQrStUvWx,DH-1001,approved,150000,05/11/2026\n";
    }
}
