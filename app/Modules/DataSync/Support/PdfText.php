<?php

namespace App\Modules\DataSync\Support;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Header;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\PDFObject;
use Smalot\PdfParser\XObject\Form;

/**
 * Lấy chữ từ file PDF (biểu lãi suất nhiều ngân hàng chỉ đăng dạng PDF). Dùng thư viện PHP thuần smalot/pdfparser,
 * không cần cài thêm gói hệ thống. Chỉ đọc được PDF có lớp chữ (xuất từ Word/Excel); PDF dạng ảnh quét thì báo lỗi.
 * Mỗi trang trả về danh sách dòng; mỗi mẩu chữ đặt riêng trong PDF (thường là một ô bảng) cách nhau bằng tab.
 */
final class PdfText
{
    public const MAX_BYTES = 15_000_000;

    /** Trang có ít mẩu chữ hơn chừng này mà có Form XObject thì đọc thêm chữ trong XObject. */
    private const MIN_PAGE_FRAGMENTS = 5;

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
        $config->setDataTmFontInfoHasToBeIncluded(true);

        try {
            $document = (new Parser([], $config))->parseContent($bytes);
            $pages = array_map(fn (Page $page) => self::rows(self::fragments($page, $document, $config)), $document->getPages());
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
     * Mẩu chữ có vị trí của trang. Một số ngân hàng (Techcombank) đặt cả bảng trong Form XObject: smalot gộp chữ của
     * XObject thành một mẩu duy nhất (ô cách bằng tab, dòng cách bằng xuống dòng) và mất vị trí. Khi gặp mẩu như vậy,
     * đọc lại từng XObject để có vị trí của từng mẩu.
     *
     * @return list<array>
     */
    private static function fragments(Page $page, Document $document, Config $config, int $depth = 0): array
    {
        $fragments = $page->getDataTm();
        $flattened = fn (array $fragment) => str_contains((string) $fragment[1], "\n");
        $forms = array_filter($page->getXObjects(), fn ($xobject) => $xobject instanceof Form);
        $sparse = count($fragments) < self::MIN_PAGE_FRAGMENTS;
        if ($forms === [] || $depth > 3 || (! $sparse && ! collect($fragments)->contains($flattened))) {
            return $fragments;
        }

        // Đọc lại chữ của chính trang khi bỏ XObject khỏi Resources (để chữ sau lệnh vẽ XObject không bị lệch), rồi
        // đọc từng XObject như một trang riêng (XObject lồng nhau thì đọc tiếp vài tầng).
        $resources = $page->get('Resources');
        $fonts = method_exists($resources, 'has') && $resources->has('Font') ? ['Font' => $resources->get('Font')] : [];
        $fragments = self::virtualPage($document, $config, $page->get('Contents'), new Header($fonts, $document))->getDataTm();
        $seen = [];
        foreach ($forms as $form) {
            if (isset($seen[$key = md5($form->getContent())])) {
                continue;
            }
            $seen[$key] = true;
            $formResources = $form->getHeader()->has('Resources') ? $form->get('Resources') : $resources;
            $contents = new PDFObject($document, new Header([], $document), $form->getContent(), $config);
            array_push($fragments, ...self::fragments(self::virtualPage($document, $config, $contents, $formResources), $document, $config, $depth + 1));
        }

        return $fragments;
    }

    private static function virtualPage(Document $document, Config $config, mixed $contents, mixed $resources): Page
    {
        return new Page($document, new Header(['Contents' => $contents, 'Resources' => $resources], $document), null, $config);
    }

    /**
     * @param  list<array{0: array<int, string>, 1: string, 2?: mixed, 3?: string}>  $fragments  [ma trận, chữ, font, cỡ chữ] từ getDataTm()
     * @return list<list<array{x: float, text: string}>>
     */
    private static function rows(array $fragments): array
    {
        $items = [];
        foreach ($fragments as $fragment) {
            [$matrix, $text] = $fragment;
            $text = preg_replace('/[ \x{00A0}\t]+/u', ' ', Fetcher::nfc((string) $text));
            if (trim($text) !== '') {
                $size = abs((float) ($fragment[3] ?? 1) * (float) $matrix[0]) ?: 1.0;
                $items[] = ['x' => (float) $matrix[4], 'y' => (float) $matrix[5], 'text' => $text, 'size' => $size];
            }
        }

        // Từ trên xuống (y giảm dần), rồi gom các mẩu gần cùng độ cao thành một dòng.
        usort($items, fn ($a, $b) => [$b['y'], $a['x']] <=> [$a['y'], $b['x']]);
        $rows = [];
        foreach ($items as $item) {
            $last = array_key_last($rows);
            $cell = ['x' => $item['x'], 'text' => $item['text'], 'size' => $item['size']];
            if ($last !== null && abs($rows[$last]['y'] - $item['y']) <= self::LINE_TOLERANCE) {
                $rows[$last]['cells'][] = $cell;
            } else {
                $rows[] = ['y' => $item['y'], 'cells' => [$cell]];
            }
        }

        return array_map(function (array $row) {
            usort($row['cells'], fn ($a, $b) => $a['x'] <=> $b['x']);

            // Ghép các mẩu nằm sát nhau thành một ô: nhiều PDF xuất từ Word tách chữ có dấu, số ("4", ",", "90")
            // thành từng mẩu riêng. Mẩu sau bắt đầu trước khi mẩu trước (ước theo cỡ chữ) kết thúc thì cùng một ô.
            $cells = [];
            foreach ($row['cells'] as $cell) {
                $last = array_key_last($cells);
                if ($last !== null && self::joins($cells[$last], $cell)) {
                    $cells[$last]['text'] .= $cell['text'];
                    $cells[$last]['end'] = max($cells[$last]['end'], $cell['x'] + self::width($cell));
                } else {
                    $cells[] = $cell + ['end' => $cell['x'] + self::width($cell)];
                }
            }

            return array_values(array_filter(array_map(
                fn (array $cell) => ['x' => $cell['x'], 'text' => trim(preg_replace('/ {2,}/u', ' ', $cell['text']))],
                $cells,
            ), fn (array $cell) => $cell['text'] !== ''));
        }, $rows);
    }

    /** Khoảng hở tối đa (theo cỡ chữ) giữa hai mẩu của cùng một ô. Các cột bảng thường cách xa hơn nhiều. */
    private const JOIN_GAP = 0.25;

    /** Khi chỗ nối nằm giữa một từ hoặc một số, cho phép lệch thêm chừng này (theo cỡ chữ) vì bề rộng chỉ là ước tính. */
    private const MID_WORD_GAP = 1.0;

    /** Mẩu sau có thuộc cùng ô với mẩu trước không. */
    private static function joins(array $previous, array $next): bool
    {
        if ($next['x'] <= $previous['end']) {
            return true;
        }

        // Giữa từ ("12 th" + "áng", "áp d" + "ụ") hoặc giữa số ("4" + "," + "75", "0," + "5" + "0").
        $midWord = preg_match('/\p{L}$/u', $previous['text']) && preg_match('/^\p{Ll}/u', $next['text'])
            || preg_match('/\d$/u', $previous['text']) && preg_match('/^[.,]\d*$/u', $next['text'])
            || preg_match('/\d[.,]\d?$/u', $previous['text']) && preg_match('/^\d/u', $next['text']);

        return $midWord && $next['x'] <= $previous['end'] + self::MID_WORD_GAP * $next['size'];
    }

    /**
     * Bề rộng ước tính của mẩu chữ (điểm PDF), theo độ rộng ký tự kiểu Helvetica/Arial. Không dùng bảng Widths của
     * font vì nhiều PDF tiếng Việt nhúng font con không có bảng đầy đủ.
     */
    private static function width(array $cell): float
    {
        $units = 0.0;
        foreach (mb_str_split(\Normalizer::normalize($cell['text'], \Normalizer::FORM_D) ?: $cell['text']) as $char) {
            $units += match (true) {
                preg_match('/\p{Mn}/u', $char) === 1 => 0.0,
                str_contains('ijlftrI', $char) => 0.28,
                str_contains('mwMW', $char) => 0.83,
                preg_match('/\p{Lu}/u', $char) === 1 => 0.68,
                preg_match('/[\p{Ll}\d]/u', $char) === 1 => 0.55,
                default => 0.3,
            };
        }

        return ($units + self::JOIN_GAP) * $cell['size'];
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
