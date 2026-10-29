<?php

namespace App\Modules\Content\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Lọc XSS cho nội dung bài viết: chỉ giữ thẻ định dạng của trình soạn thảo, bỏ script, style,
 * thuộc tính sự kiện (onclick, onerror...), iframe, form và liên kết javascript:/data:.
 * Ảnh chỉ nhận đường dẫn tương đối (file tải lên) hoặc https.
 */
final class ArticleHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = trim(self::sanitizer()->sanitize($html));

        return $clean === '' || $clean === '<p></p>' ? null : $clean;
    }

    /** URL http(s) hợp lệ, hoặc null. Dùng cho liên kết in vào href ngoài phần nội dung. */
    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    /** Văn bản thuần, dùng để kiểm tra bài có nội dung và ước tính thời gian đọc. */
    public static function text(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', (string) $html)), ENT_QUOTES | ENT_HTML5)));
    }

    /** Số phút đọc, tính 200 từ một phút, tối thiểu 1. */
    public static function readingMinutes(?string $html): int
    {
        $words = self::text($html) === '' ? 0 : count(preg_split('/\s+/u', self::text($html)));

        return max(1, (int) ceil($words / 200));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias()
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'data-id'])
            ->allowElement('ol', ['start'])
            ->allowElement('th', ['colspan', 'rowspan'])
            ->allowElement('td', ['colspan', 'rowspan'])
            ->withMaxInputLength(2_000_000);

        foreach ([
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'h2', 'h3', 'h4', 'ul', 'li',
            'blockquote', 'code', 'pre', 'hr', 'figure', 'figcaption',
            'table', 'colgroup', 'col', 'thead', 'tbody', 'tr',
        ] as $element) {
            $config = $config->allowElement($element);
        }

        return self::$sanitizer = new HtmlSanitizer($config);
    }
}
