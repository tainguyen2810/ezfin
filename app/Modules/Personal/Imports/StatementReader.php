<?php

namespace App\Modules\Personal\Imports;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Đọc file sao kê (CSV hoặc Excel .xlsx) thành bảng ô. Ô ngày của Excel đổi thành chuỗi Y-m-d,
 * số giữ nguyên kiểu số, còn lại là chuỗi đã bỏ khoảng trắng hai đầu.
 */
class StatementReader
{
    public const MAX_ROWS = 2000;

    /**
     * @return list<list<string|int|float|null>>
     *
     * @throws StatementImportException
     */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->csv((string) file_get_contents($file->getRealPath())),
            'xlsx' => $this->xlsx($file->getRealPath()),
            'xls' => throw new StatementImportException('File .xls (Excel 97–2003) chưa hỗ trợ. Mở file trong Excel, chọn Lưu thành → Sổ làm việc Excel (.xlsx) hoặc CSV UTF-8.'),
            default => throw new StatementImportException('Chỉ nhận file .csv hoặc .xlsx.'),
        };

        $rows = array_values(array_filter($rows, fn (array $row) => array_filter($row, fn ($cell) => $cell !== null && $cell !== '') !== []));
        if ($rows === []) {
            throw new StatementImportException('File không có dữ liệu.');
        }
        if (count($rows) > self::MAX_ROWS + 30) {
            throw new StatementImportException('File có quá '.self::MAX_ROWS.' dòng. Hãy xuất sao kê theo từng tháng.');
        }

        return $rows;
    }

    /** @return list<list<string|null>> */
    public function csv(string $content): array
    {
        if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', str_starts_with($content, "\xFF\xFE") ? 'UTF-16LE' : 'UTF-16BE');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new StatementImportException('Không đọc được bảng mã của file. Trong Excel chọn Lưu thành → CSV UTF-8, hoặc tải sao kê dạng .xlsx.');
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn (string $d) => substr_count($firstLine, $d))->first();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($cell) => $cell === null ? null : trim($cell), $row);
            if (count($rows) > self::MAX_ROWS + 30) {
                break;
            }
        }
        fclose($stream);

        return $rows;
    }

    /** @return list<list<string|int|float|null>> */
    private function xlsx(string $path): array
    {
        $reader = new XlsxReader;
        try {
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map(fn ($value) => match (true) {
                        $value instanceof DateTimeInterface => $value->format('Y-m-d'),
                        is_string($value) => trim($value),
                        is_int($value), is_float($value) => $value,
                        is_bool($value) => $value ? '1' : '0',
                        default => null,
                    }, $row->toArray());
                    if (count($rows) > self::MAX_ROWS + 30) {
                        break 2;
                    }
                }
                break; // chỉ đọc trang tính đầu tiên
            }
        } catch (Throwable) {
            throw new StatementImportException('Không mở được file Excel. File có thể bị hỏng hoặc có mật khẩu.');
        } finally {
            $reader->close();
        }

        return $rows;
    }
}
