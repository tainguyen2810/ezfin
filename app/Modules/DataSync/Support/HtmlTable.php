<?php

namespace App\Modules\DataSync\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Đọc bảng HTML thành lưới chữ: ô gộp (rowspan/colspan) được nhân ra mọi vị trí nó che,
 * nên mỗi dòng có cùng số cột và tiêu đề nhiều tầng tra được theo cột.
 */
class HtmlTable
{
    public static function document(string $html): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    /** @return list<DOMElement> */
    public static function tables(string $html): array
    {
        return iterator_to_array(self::document($html)->getElementsByTagName('table'), false);
    }

    /** @return list<list<string>> */
    public static function grid(DOMElement $table): array
    {
        $grid = [];
        $xpath = new DOMXPath($table->ownerDocument);
        $rowIndex = 0;

        // Chỉ dòng của chính bảng này, không lấy dòng của bảng lồng bên trong.
        foreach ($xpath->query('./tr | ./thead/tr | ./tbody/tr | ./tfoot/tr', $table) as $tr) {
            $col = 0;
            foreach ($xpath->query('./td | ./th', $tr) as $cell) {
                while (isset($grid[$rowIndex][$col])) {
                    $col++;
                }
                $text = self::text($cell);
                $rowspan = max(1, (int) $cell->getAttribute('rowspan'));
                $colspan = max(1, (int) $cell->getAttribute('colspan'));
                for ($r = 0; $r < $rowspan; $r++) {
                    for ($c = 0; $c < $colspan; $c++) {
                        $grid[$rowIndex + $r][$col + $c] = $text;
                    }
                }
                $col += $colspan;
            }
            $rowIndex++;
        }

        $width = $grid === [] ? 0 : max(array_map(fn ($row) => max(array_keys($row)) + 1, $grid));
        ksort($grid);

        return array_values(array_map(function (array $row) use ($width) {
            $filled = array_replace(array_fill(0, $width, ''), $row);
            ksort($filled);

            return array_values($filled);
        }, $grid));
    }

    public static function text(\DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', Fetcher::nfc($node->textContent))));
    }

    /** Bảng đầu tiên có chữ (không phân biệt hoa thường) chứa tất cả các cụm cho trước. */
    public static function find(string $html, string ...$needles): ?DOMElement
    {
        foreach (self::tables($html) as $table) {
            $text = mb_strtolower(self::text($table));
            if (collect($needles)->every(fn ($needle) => str_contains($text, mb_strtolower($needle)))) {
                return $table;
            }
        }

        return null;
    }

    /**
     * Các phần tử có class cho trước (so khớp cả từ, như CSS ".class"), trong cả trang hoặc trong $context.
     *
     * @return list<DOMElement>
     */
    public static function byClass(DOMXPath $xpath, string $class, ?\DOMNode $context = null): array
    {
        $query = ($context ? './/' : '//').'*[contains(concat(" ", normalize-space(@class), " "), " '.$class.' ")]';

        return array_values(array_filter(iterator_to_array($xpath->query($query, $context) ?: [], false), fn ($node) => $node instanceof DOMElement));
    }

    public static function firstByClass(DOMXPath $xpath, string $class, ?\DOMNode $context = null): ?DOMElement
    {
        return self::byClass($xpath, $class, $context)[0] ?? null;
    }
}
