<?php

namespace App\Modules\Personal\Imports;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Cách đọc bảng sao kê: dòng tiêu đề, cột ngày, nội dung, số tiền (một cột có dấu, hoặc hai cột ghi nợ/ghi có),
 * kiểu ngày. Cột đánh số từ 0, dòng từ 0. Tự đoán từ tên cột thường gặp của sao kê ngân hàng Việt Nam.
 */
final class StatementMapping
{
    private const KEYWORDS = [
        'date' => ['ngày giao dịch', 'ngày gd', 'ngày hiệu lực', 'ngày', 'transaction date', 'posting date', 'value date', 'date', 'thời gian'],
        'description' => ['nội dung', 'diễn giải', 'mô tả', 'chi tiết', 'description', 'details', 'narrative', 'remark'],
        'debit' => ['ghi nợ', 'số tiền ghi nợ', 'phát sinh nợ', 'tiền ra', 'rút', 'debit', 'withdrawal', 'chi'],
        'credit' => ['ghi có', 'số tiền ghi có', 'phát sinh có', 'tiền vào', 'credit', 'deposit', 'thu'],
        'amount' => ['số tiền giao dịch', 'số tiền', 'amount', 'giá trị'],
    ];

    public function __construct(
        public int $headerRow,
        public ?int $date,
        public ?int $description,
        public ?int $amount,
        public ?int $debit,
        public ?int $credit,
        public string $dateFormat = 'dmy',
    ) {}

    /** Đọc lựa chọn của người dùng (query string hoặc form), cột nào chưa chọn thì đoán. */
    public static function fromRequest(Request $request, array $rows): self
    {
        $guess = self::guess($rows);
        $column = function (string $key) use ($request, $guess): ?int {
            if (! $request->has($key)) {
                return $guess->{$key};
            }
            $value = $request->input($key);

            return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
        };
        $headerRow = $request->has('dong_tieu_de') && is_numeric($request->input('dong_tieu_de'))
            ? max(0, min(count($rows) - 1, (int) $request->input('dong_tieu_de') - 1))
            : $guess->headerRow;

        return new self($headerRow, $column('date'), $column('description'), $column('amount'), $column('debit'), $column('credit'),
            $request->input('kieu_ngay') === 'mdy' ? 'mdy' : 'dmy');
    }

    /** @param  list<list<mixed>>  $rows */
    public static function guess(array $rows): self
    {
        // Dòng tiêu đề: dòng (trong 30 dòng đầu) khớp nhiều tên cột nhất; sao kê thường có vài dòng thông tin tài khoản ở trên.
        $best = [0, -1, []];
        foreach (array_slice($rows, 0, 30) as $index => $row) {
            $found = self::match($row);
            if (count($found) > $best[1]) {
                $best = [$index, count($found), $found];
            }
        }
        [$headerRow, , $found] = $best;

        $mapping = new self($headerRow, $found['date'] ?? null, $found['description'] ?? null, null,
            $found['debit'] ?? null, $found['credit'] ?? null);
        if ($mapping->debit === null || $mapping->credit === null) {
            $mapping->debit = $mapping->credit = null;
            $mapping->amount = $found['amount'] ?? $found['debit'] ?? $found['credit'] ?? null;
        }

        return $mapping;
    }

    /** @return array<string, int> loại cột => số thứ tự cột, mỗi cột dùng cho một loại */
    private static function match(array $row): array
    {
        $names = array_map(fn ($cell) => is_string($cell) ? Str::lower(trim($cell)) : '', $row);
        $found = [];
        foreach (self::KEYWORDS as $key => $keywords) {
            foreach ($keywords as $keyword) {
                foreach ($names as $index => $name) {
                    if ($name === '' || in_array($index, $found, true)) {
                        continue;
                    }
                    // Từ khóa ngắn (chi, thu) phải khớp nguyên tên cột để không bắt nhầm "chi tiết".
                    $hit = mb_strlen($keyword) <= 3 ? $name === $keyword : str_contains($name, $keyword);
                    if ($hit) {
                        $found[$key] = $index;
                        break 2;
                    }
                }
            }
        }

        return $found;
    }

    public function usesTwoColumns(): bool
    {
        return $this->amount === null && $this->debit !== null && $this->credit !== null;
    }

    /** Thiếu cột bắt buộc thì trả thông báo. */
    public function problem(): ?string
    {
        return match (true) {
            $this->date === null => 'Chọn cột ngày giao dịch.',
            $this->amount === null && ! $this->usesTwoColumns() => 'Chọn cột số tiền, hoặc cả hai cột ghi nợ và ghi có.',
            default => null,
        };
    }

    /** @return array<string, int|string|null> để giữ lựa chọn trên URL */
    public function query(): array
    {
        return ['dong_tieu_de' => $this->headerRow + 1, 'date' => $this->date, 'description' => $this->description,
            'amount' => $this->amount, 'debit' => $this->debit, 'credit' => $this->credit, 'kieu_ngay' => $this->dateFormat];
    }
}
