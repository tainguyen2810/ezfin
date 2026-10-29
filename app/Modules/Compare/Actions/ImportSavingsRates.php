<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsRateImportException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Nhập lãi suất tiết kiệm từ CSV (UTF-8, phân cách bằng dấu phẩy hoặc chấm phẩy như Excel tiếng Việt).
 * Kiểm tra toàn bộ file trước; có lỗi thì không ghi dòng nào. Import lại cùng file không tạo trùng:
 * dòng cùng sản phẩm, kỳ hạn, kênh, hình thức lĩnh lãi, mức tiền từ, ngày hiệu lực được cập nhật.
 */
class ImportSavingsRates
{
    public const COLUMNS = ['san_pham', 'ky_han', 'kenh', 'lai_suat', 'so_tien_tu', 'so_tien_den', 'linh_lai', 'hieu_luc_tu'];

    public const REQUIRED_COLUMNS = ['san_pham', 'ky_han', 'kenh', 'lai_suat', 'hieu_luc_tu'];

    public const MAX_ROWS = 5000;

    /** File mẫu cho người nhập liệu. */
    public static function template(): string
    {
        return implode("\n", [
            implode(',', self::COLUMNS),
            'tiet-kiem-online-ngan-hang-a,0,online,"0,5",,,cuoi-ky,01/11/2026',
            'tiet-kiem-online-ngan-hang-a,6,online,"4,8",,,cuoi-ky,01/11/2026',
            'tiet-kiem-online-ngan-hang-a,12,online,"5,2",,,hang-thang,01/11/2026',
            'tiet-kiem-online-ngan-hang-a,12,tai-quay,"5,0",1000000000,,cuoi-ky,01/11/2026',
        ])."\n";
    }

    /** @return array{created: int, updated: int, unchanged: int} */
    public function handle(string $path): array
    {
        $rows = $this->validate($this->read($path));

        return DB::transaction(function () use ($rows) {
            $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

            foreach ($rows as $row) {
                // whereDate: SQLite lưu cột date kèm giờ, so sánh chuỗi ngày trực tiếp sẽ không khớp.
                $rate = SavingsRate::query()
                    ->where(collect($row['key'])->except('effective_from')->all())
                    ->whereDate('effective_from', $row['key']['effective_from'])
                    ->first() ?? (new SavingsRate)->forceFill($row['key']);
                $rate->forceFill($row['values']);
                $status = ! $rate->exists ? 'created' : ($rate->isDirty() ? 'updated' : 'unchanged');
                $rate->save();
                $result[$status]++;
            }

            return $result;
        });
    }

    /** @return array<int, array<string, string>> số dòng => giá trị theo tên cột */
    private function read(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new SavingsRateImportException([0 => ['File trống hoặc không đọc được.']]);
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new SavingsRateImportException([0 => ['File phải lưu dạng "CSV UTF-8". Trong Excel: Lưu thành → CSV UTF-8 (phân cách bằng dấu phẩy).']]);
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(fn ($name) => mb_strtolower(trim((string) $name)), str_getcsv($lines[0], $delimiter, '"', ''));

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        $unknown = array_diff(array_filter($header, fn ($name) => $name !== ''), self::COLUMNS);
        if ($missing || $unknown) {
            throw new SavingsRateImportException([1 => array_values(array_filter([
                $missing ? 'Thiếu cột: '.implode(', ', $missing).'.' : null,
                $unknown ? 'Cột không hỗ trợ: '.implode(', ', $unknown).'. Các cột hợp lệ: '.implode(', ', self::COLUMNS).'.' : null,
            ]))]);
        }

        $rows = [];
        foreach (array_slice($lines, 1, preserve_keys: true) as $index => $line) {
            if (trim(str_replace($delimiter, '', $line)) === '') {
                continue;
            }

            $values = array_map('trim', str_getcsv($line, $delimiter, '"', ''));
            $rows[$index + 1] = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
        }

        if ($rows === []) {
            throw new SavingsRateImportException([0 => ['File không có dòng dữ liệu nào.']]);
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new SavingsRateImportException([0 => ['File có quá '.self::MAX_ROWS.' dòng, vui lòng chia nhỏ.']]);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return list<array{key: array<string, mixed>, values: array<string, mixed>}>
     */
    private function validate(array $rows): array
    {
        $products = FinancialProduct::query()
            ->ofType(ProductType::Savings)
            ->whereIn('slug', array_unique(array_column($rows, 'san_pham')))
            ->pluck('id', 'slug');

        $errors = [];
        $valid = [];
        $seen = [];

        foreach ($rows as $line => $row) {
            $lineErrors = [];

            $productId = $products[$row['san_pham']] ?? null;
            if ($row['san_pham'] === '') {
                $lineErrors[] = 'Thiếu mã sản phẩm (san_pham).';
            } elseif ($productId === null) {
                $lineErrors[] = "Không có sản phẩm tiết kiệm với mã \"{$row['san_pham']}\".";
            }

            $term = filter_var($row['ky_han'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => SavingsRate::MAX_TERM_MONTHS]]);
            if ($term === false) {
                $lineErrors[] = 'Kỳ hạn (ky_han) phải là số tháng từ 0 đến '.SavingsRate::MAX_TERM_MONTHS.' (0 = không kỳ hạn).';
            }

            $channel = SavingsChannel::fromSlug(mb_strtolower($row['kenh']));
            if ($channel === null) {
                $lineErrors[] = 'Kênh (kenh) phải là "online" hoặc "tai-quay".';
            }

            $rate = $this->decimal($row['lai_suat']);
            if ($rate === null || $rate < 0 || $rate > SavingsRate::MAX_RATE) {
                $lineErrors[] = 'Lãi suất (lai_suat) phải là số %/năm từ 0 đến '.SavingsRate::MAX_RATE.', ví dụ 5,2.';
            }

            $min = ($row['so_tien_tu'] ?? '') === '' ? 0 : $this->money($row['so_tien_tu']);
            $max = ($row['so_tien_den'] ?? '') === '' ? null : $this->money($row['so_tien_den']);
            if ($min === false) {
                $lineErrors[] = 'Số tiền từ (so_tien_tu) phải là số nguyên VND, ví dụ 1000000.';
            }
            if ($max === false) {
                $lineErrors[] = 'Số tiền đến (so_tien_den) phải là số nguyên VND, ví dụ 1000000000.';
            } elseif ($max !== null && is_int($min) && $max <= $min) {
                $lineErrors[] = 'Số tiền đến (so_tien_den) phải lớn hơn số tiền từ.';
            }

            $payment = ($row['linh_lai'] ?? '') === '' ? InterestPayment::EndOfTerm : InterestPayment::fromSlug(mb_strtolower($row['linh_lai']));
            if ($payment === null) {
                $lineErrors[] = 'Hình thức lĩnh lãi (linh_lai) phải là cuoi-ky, hang-thang, hang-quy hoặc dau-ky.';
            }

            $effective = $this->date($row['hieu_luc_tu']);
            if ($effective === null) {
                $lineErrors[] = 'Ngày hiệu lực (hieu_luc_tu) phải có dạng dd/mm/yyyy.';
            }

            if ($lineErrors === []) {
                $key = [
                    'product_id' => $productId,
                    'term_months' => $term,
                    'channel' => $channel->value,
                    'interest_payment' => $payment->value,
                    'min_amount' => number_format($min, 2, '.', ''),
                    'effective_from' => $effective->toDateString(),
                ];
                $hash = implode('|', $key);

                if (isset($seen[$hash])) {
                    $lineErrors[] = "Trùng với dòng {$seen[$hash]} (cùng sản phẩm, kỳ hạn, kênh, lĩnh lãi, số tiền từ, ngày hiệu lực).";
                } else {
                    $seen[$hash] = $line;
                    $valid[] = ['key' => $key, 'values' => [
                        'rate' => number_format($rate, 4, '.', ''),
                        'max_amount' => $max === null ? null : number_format($max, 2, '.', ''),
                    ]];
                }
            }

            if ($lineErrors !== []) {
                $errors[$line] = $lineErrors;
            }
        }

        if ($errors !== []) {
            throw new SavingsRateImportException($errors);
        }

        return $valid;
    }

    /** "5,2" hoặc "5.2" → 5.2 (tối đa 4 chữ số thập phân). */
    private function decimal(string $value): ?float
    {
        $value = str_replace(['%', ' '], '', $value);

        return preg_match('/^\d{1,3}([.,]\d{1,4})?$/', $value) ? (float) str_replace(',', '.', $value) : null;
    }

    /** "1.000.000", "1 000 000" hoặc "1000000" → 1000000; sai định dạng → false. */
    private function money(string $value): int|false
    {
        $digits = preg_replace('/[\s.]/', '', $value);

        return preg_match('/^\d{1,16}$/', $digits) ? (int) $digits : false;
    }

    /** "01/11/2026", "1/11/2026" hoặc "2026-11-01"; ngày không tồn tại (31/02) → null. */
    private function date(string $value): ?Carbon
    {
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        return checkdate($month, $day, $year) && $year >= 2000 ? Carbon::create($year, $month, $day)->startOfDay() : null;
    }
}
