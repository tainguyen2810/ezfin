<?php

namespace App\Modules\Content\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Lọc thuộc tính style do trình soạn thảo tạo (màu chữ, nền, căn lề, cỡ chữ, thụt lề, kích thước ảnh/bảng).
 * Chỉ giữ thuộc tính CSS trong danh sách và giá trị đơn giản; bỏ url(), expression(), position, và mọi thứ khác.
 */
final class StyleAttributeSanitizer implements AttributeSanitizerInterface
{
    private const PROPERTIES = [
        'color', 'background-color', 'text-align', 'font-size', 'font-weight', 'font-style', 'text-decoration',
        'padding-left', 'margin-left', 'margin-right', 'width', 'height', 'max-width', 'vertical-align',
        'float', 'display', 'border-collapse', 'border-width', 'border-style', 'border-color', 'list-style-type',
    ];

    /** Giá trị CSS an toàn: chữ, số, đơn vị, %, #màu, rgb()/rgba(), dấu phẩy, khoảng trắng. */
    private const VALUE = '/^(?:[a-z0-9#.%\-\s,]|rgba?\(\s*[0-9.,\s%]+\))+$/i';

    private const DISPLAY = ['block', 'inline-block', 'inline', 'table'];

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $kept = [];
        foreach (explode(';', html_entity_decode($value, ENT_QUOTES | ENT_HTML5)) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }
            [$property, $val] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $val = preg_replace('/\s*!important$/i', '', $val);

            if (! in_array($property, self::PROPERTIES, true) || $val === '' || strlen($val) > 60 || ! preg_match(self::VALUE, $val)) {
                continue;
            }
            if ($property === 'display' && ! in_array(strtolower($val), self::DISPLAY, true)) {
                continue;
            }
            $kept[] = $property.': '.$val;
        }

        return $kept === [] ? null : implode('; ', $kept);
    }
}
