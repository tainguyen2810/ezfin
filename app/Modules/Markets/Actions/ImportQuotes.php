<?php

namespace App\Modules\Markets\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Nhập bảng tỷ giá hoặc giá vàng từ file CSV (UTF-8, phân cách "," hoặc ";").
 * Kiểm tra cả file: có lỗi thì không ghi dòng nào. Nhập lại cùng file chỉ cập nhật, không tạo trùng.
 */
class ImportQuotes
{
    /** Cột file => khóa dữ liệu của RecordQuote. */
    public const COLUMNS = [
        'fx' => ['thoi_diem' => 'quoted_at', 'nguon' => 'source', 'ma' => 'currency', 'mua_tien_mat' => 'buy_cash', 'mua_chuyen_khoan' => 'buy_transfer', 'ban' => 'sell', 'link_nguon' => 'source_url'],
        'gold' => ['thoi_diem' => 'quoted_at', 'nguon' => 'source', 'loai_vang' => 'product', 'mua' => 'buy', 'ban' => 'sell', 'link_nguon' => 'source_url'],
    ];

    public const REQUIRED = [
        'fx' => ['thoi_diem', 'nguon', 'ma', 'ban'],
        'gold' => ['thoi_diem', 'nguon', 'loai_vang', 'ban'],
    ];

    public const MAX_ROWS = 5_000;

    public function __construct(private readonly RecordQuote $record) {}

    /**
     * @param  'fx'|'gold'  $kind
     * @return array{created: int, updated: int}
     *
     * @throws InvalidArgumentException danh sách lỗi theo dòng, mỗi lỗi một dòng trong thông báo
     */
    public function handle(string $kind, string $path): array
    {
        $columns = self::COLUMNS[$kind] ?? throw new InvalidArgumentException('Loại dữ liệu không hợp lệ.');

        $handle = fopen($path, 'r') ?: throw new RuntimeException('Không đọc được file.');
        $first = preg_replace('/^\xEF\xBB\xBF/', '', (string) fgets($handle));
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($cell) => strtolower(trim((string) $cell)), str_getcsv($first, $delimiter, '"', ''));

        if ($missing = array_diff(self::REQUIRED[$kind], $header)) {
            fclose($handle);
            throw new InvalidArgumentException('Thiếu cột: '.implode(', ', $missing).'. Các cột: '.implode(', ', array_keys($columns)).'.');
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

                $data = [];
                foreach ($header as $index => $column) {
                    if (isset($columns[$column])) {
                        $data[$columns[$column]] = isset($cells[$index]) ? trim((string) $cells[$index]) : null;
                    }
                }

                try {
                    $created = ($kind === 'fx' ? $this->record->fx($data) : $this->record->gold($data))['created'];
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

    /** @param  'fx'|'gold'  $kind */
    public static function template(string $kind): string
    {
        $rows = $kind === 'fx'
            ? "13/11/2026 08:30,Vietcombank,USD,25.050,25.080,25.440,https://www.vietcombank.com.vn\n13/11/2026 08:30,Vietcombank,JPY,\"160,12\",\"161,74\",\"170,30\",\n"
            : "13/11/2026 08:30,SJC,Vàng miếng SJC 1 lượng,84.000.000,86.000.000,https://sjc.com.vn\n13/11/2026 08:30,SJC,Nhẫn tròn trơn 99.99,82.500.000,84.300.000,\n";

        return "\xEF\xBB\xBF".implode(',', array_keys(self::COLUMNS[$kind]))."\n".$rows;
    }
}
