<?php

namespace App\Modules\DataSync\Support;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

/**
 * Lấy chữ từ file PDF (biểu lãi suất nhiều ngân hàng chỉ đăng dạng PDF). Dùng thư viện PHP thuần smalot/pdfparser,
 * không cần cài thêm gói hệ thống. Chỉ đọc được PDF có lớp chữ (xuất từ Word/Excel); PDF dạng ảnh quét thì báo lỗi.
 * Mỗi trang trả về danh sách dòng; mỗi mẩu chữ đặt riêng trong PDF (thường là một ô bảng) cách nhau bằng tab.
 */
final class PdfText
{
    public const MAX_BYTES = 15_000_000;

    /** Hai mẩu chữ lệch nhau theo chiều dọc không quá chừng này (điểm PDF) coi như cùng một dòng. */
    private const LINE_TOLERANCE = 2.5;

    /** @return list<list<string>> trang => các dòng (ô cách nhau bằng tab, đã chuẩn hóa NFC) */
    public static function pages(string $bytes): array
    {
        return array_map(
            fn (array $rows) => array_map(fn (array $row) => implode("\t", array_column($row, 'text')), $rows),
            self::cellPages($bytes),
        );
    }

    /**
     * Chữ theo vị trí: trang => dòng (từ trên xuống) => ô (từ trái sang) {x, text}. Dùng x để gióng cột khi bảng có
     * tiêu đề gộp ô.
     *
     * @return list<list<list<array{x: float, text: string}>>>
     */
    public static function cellPages(string $bytes): array
    {
        if (! str_starts_with(ltrim(substr($bytes, 0, 1024)), '%PDF')) {
            throw ScrapeException::layoutChanged('tệp tải về không phải PDF.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw ScrapeException::layoutChanged('tệp PDF quá lớn.');
        }

        $config = new Config;
        $config->setRetainImageContent(false);

        try {
            $document = (new Parser([], $config))->parseContent($bytes);
            $pages = array_map(fn ($page) => self::rows($page->getDataTm()), $document->getPages());
        } catch (ScrapeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ScrapeException::layoutChanged('không đọc được tệp PDF ('.$e->getMessage().').');
        }

        if (collect($pages)->flatten(1)->isEmpty()) {
            throw ScrapeException::layoutChanged('PDF không có lớp chữ (có thể là ảnh quét).');
        }

        return $pages;
    }

    /**
     * @param  list<array{0: array<int, string>, 1: string}>  $fragments  [ma trận vị trí, chữ] từ getDataTm()
     * @return list<list<array{x: float, text: string}>>
     */
    private static function rows(array $fragments): array
    {
        $items = [];
        foreach ($fragments as [$matrix, $text]) {
            $text = trim(preg_replace('/[ \x{00A0}\t]+/u', ' ', Fetcher::nfc((string) $text)));
            if ($text !== '') {
                $items[] = ['x' => (float) $matrix[4], 'y' => (float) $matrix[5], 'text' => $text];
            }
        }

        // Từ trên xuống (y giảm dần), rồi gom các mẩu gần cùng độ cao thành một dòng.
        usort($items, fn ($a, $b) => [$b['y'], $a['x']] <=> [$a['y'], $b['x']]);
        $rows = [];
        foreach ($items as $item) {
            $last = array_key_last($rows);
            if ($last !== null && abs($rows[$last]['y'] - $item['y']) <= self::LINE_TOLERANCE) {
                $rows[$last]['cells'][] = ['x' => $item['x'], 'text' => $item['text']];
            } else {
                $rows[] = ['y' => $item['y'], 'cells' => [['x' => $item['x'], 'text' => $item['text']]]];
            }
        }

        return array_map(function (array $row) {
            usort($row['cells'], fn ($a, $b) => $a['x'] <=> $b['x']);

            return $row['cells'];
        }, $rows);
    }

    /** @return list<string> mọi dòng của mọi trang */
    public static function lines(string $bytes): array
    {
        return array_merge(...self::pages($bytes) ?: [[]]);
    }

    /** Các ô của một dòng (tách bởi tab hoặc từ 2 khoảng trắng trở lên). @return list<string> */
    public static function cells(string $line): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\t+| {2,}/u', $line) ?: []), fn ($cell) => $cell !== ''));
    }
}
