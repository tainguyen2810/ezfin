<?php

namespace App\Modules\DataSync\Support;

use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\LoanKind;

/** Đọc số tiền, thời hạn, tỷ lệ, loại thẻ/khoản vay từ chữ trên trang sản phẩm của ngân hàng. */
class CatalogParsing
{
    /** "800.000 VND" → 800000, "02 tỷ VND" → 2000000000, "1,5 tỷ" → 1500000000, "Miễn phí" → 0; không đọc được → null. */
    public static function money(?string $text): ?int
    {
        $text = mb_strtolower(trim((string) $text));
        if ($text === '') {
            return null;
        }
        if (preg_match('/^miễn phí/u', $text)) {
            return 0;
        }
        if (! preg_match('/(\d[\d.,]*)\s*(tỷ|triệu|tr(?!\p{L})|nghìn|ngàn|k(?!\p{L})|vnd|vnđ|đồng|đ)?/u', $text, $m)) {
            return null;
        }

        $unit = $m[2] ?? '';
        $multiplier = match (true) {
            $unit === 'tỷ' => 1_000_000_000,
            in_array($unit, ['triệu', 'tr'], true) => 1_000_000,
            in_array($unit, ['nghìn', 'ngàn', 'k'], true) => 1_000,
            default => 1,
        };

        $number = $m[1];
        if ($multiplier > 1) {
            // "1,5 tỷ" / "1.5 tỷ": dấu cuối là phần thập phân.
            $number = (float) str_replace(',', '.', preg_replace('/[.,](?=\d{3}(\D|$))/', '', $number));
        } else {
            $number = (float) preg_replace('/[.,]/', '', $number);
        }

        return (int) round($number * $multiplier);
    }

    /** "30 năm" → 360, "84 tháng" → 84; không có thời hạn cụ thể → null. */
    public static function months(?string $text): ?int
    {
        if (! preg_match('/(\d+)\s*(năm|tháng)/u', mb_strtolower((string) $text), $m)) {
            return null;
        }

        return (int) $m[1] * ($m[2] === 'năm' ? 12 : 1);
    }

    /** "70% giá trị" → "70.0000". */
    public static function percent(?string $text): ?string
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*%/', (string) $text, $m)) {
            return null;
        }

        return number_format((float) str_replace(',', '.', $m[1]), 4, '.', '');
    }

    /** Thương hiệu thẻ theo tên sản phẩm. */
    public static function network(string $name): ?string
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'american express'), str_contains($name, 'amex') => CardNetwork::Amex->value,
            str_contains($name, 'mastercard') => CardNetwork::Mastercard->value,
            str_contains($name, 'jcb') => CardNetwork::Jcb->value,
            str_contains($name, 'unionpay') => CardNetwork::UnionPay->value,
            str_contains($name, 'napas') => CardNetwork::Napas->value,
            str_contains($name, 'visa') => CardNetwork::Visa->value,
            default => null,
        };
    }

    /**
     * Nhóm ưu đãi thẻ theo từ khóa trong tên và phần lợi ích.
     *
     * @return list<string>
     */
    public static function benefits(string $text): array
    {
        $text = mb_strtolower($text);
        $rules = [
            CardBenefit::Cashback->value => ['hoàn tiền', 'cashback', 'cash back'],
            CardBenefit::Miles->value => ['dặm', 'bông sen', 'airlines', 'lotusmiles'],
            CardBenefit::Installment->value => ['trả góp 0', 'trả góp lãi suất 0'],
            CardBenefit::Travel->value => ['phòng chờ', 'sân bay', 'du lịch', 'bảo hiểm chuyến đi'],
            CardBenefit::Shopping->value => ['mua sắm', 'trung tâm thương mại', 'siêu thị'],
            CardBenefit::Dining->value => ['ăn uống', 'nhà hàng', 'ẩm thực'],
        ];

        return array_values(array_keys(array_filter($rules, fn (array $words) => collect($words)->contains(fn ($w) => str_contains($text, $w)))));
    }

    /** Loại khoản vay EzFin so sánh (cá nhân tiêu dùng, mua nhà, mua ô tô); khoản vay kinh doanh, cầm cố... → null. */
    public static function loanKind(string $name): ?string
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'ô tô'), str_contains($name, 'oto'), (bool) preg_match('/(^|\s)xe(\s|$)/u', $name) => LoanKind::Auto->value,
            str_contains($name, 'kinh doanh'), str_contains($name, 'cầm cố'), str_contains($name, 'lưu trú'), str_contains($name, 'du học') => null,
            (bool) preg_match('/nhà|bất động sản|căn hộ|đất ở/u', $name) => LoanKind::Home->value,
            (bool) preg_match('/tín chấp|tiêu dùng|theo lương|thấu chi/u', $name) => LoanKind::Personal->value,
            default => null,
        };
    }

    /**
     * URL tuyệt đối của ảnh sản phẩm (PNG/JPG/WebP, không nhận SVG). $original: bỏ tham số thu nhỏ ảnh (?h=32&w=32...)
     * để lấy ảnh gốc, chỉ dùng khi website trả ảnh gốc ở đường dẫn không có tham số.
     */
    public static function imageUrl(?string $src, string $base, bool $original = false): ?string
    {
        $src = trim(html_entity_decode((string) $src));
        $path = (string) parse_url($src, PHP_URL_PATH);
        $extension = str_contains(basename($path), '.') ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : null;
        // Ảnh không có đuôi (kho ảnh của CMS) vẫn nhận, định dạng được kiểm tra khi tải về lúc duyệt.
        if ($src === '' || ($extension !== null && ! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true))) {
            return null;
        }
        if ($original) {
            $src = preg_replace('/[?#].*$/', '', $src);
        }
        $src = str_replace(' ', '%20', $src);
        if (str_starts_with($src, '//')) {
            return 'https:'.$src;
        }

        return preg_match('#^https?://#', $src) ? $src : rtrim($base, '/').'/'.ltrim($src, '/');
    }

    /** Chữ hiển thị của trang (bỏ script, style, đầu trang, chân trang, menu), gộp khoảng trắng. */
    public static function pageText(string $html): string
    {
        $html = preg_replace('#<(script|style|noscript|header|footer|nav)\b.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags(preg_replace('#<(br|p|li|div|td|h\d)\b#i', ' <$1', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    /** Bỏ chữ "Thẻ tín dụng"/"Thẻ" ở đầu tên: "Thẻ BIDV Visa Infinite" → "BIDV Visa Infinite". */
    public static function cardName(string $name): string
    {
        return trim(preg_replace('/^(thẻ\s+tín\s+dụng(\s+quốc\s+tế)?|thẻ)\s+/iu', '', trim(preg_replace('/\s+/u', ' ', $name))));
    }

    /**
     * Thông số thẻ tín dụng đọc từ chữ trên trang chi tiết (cách viết phổ biến của các ngân hàng Việt Nam). Trường không
     * thấy để null; ghi chú phí là câu gốc trên trang để người duyệt đối chiếu.
     *
     * @return array{annual_fee: ?int, annual_fee_note: ?string, issuance_fee: ?int, interest_free_days: ?int, interest_rate: ?string, min_monthly_income: ?int}
     */
    public static function cardTerms(string $text): array
    {
        $amount = '(\d{1,3}(?:[.,]\d{3})+|\d+(?:[.,]\d+)?\s*(?:triệu|tr)(?!\p{L}))\s*(?:VND|VNĐ|đồng|đ)?';
        // Khoảng chữ giữa "phí thường niên" và số tiền, không vượt qua điều kiện miễn phí ("chi tiêu từ 2 triệu...").
        $gap = '(?:(?!chi tiêu|doanh số|hạn mức|từ )[^.])';
        $annual = $note = null;
        foreach ([
            '/phí thường niên[^.]{0,80}?thẻ chính\s*:?'.$gap.'{0,80}?'.$amount.'/iu',
            '/phí thường niên(?: tiêu chuẩn)?(?: là| :|:)?\s*'.$amount.'/iu',
            '/phí thường niên'.$gap.'{0,60}?'.$amount.'/iu',
        ] as $pattern) {
            if (preg_match($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                $annual = self::money($m[1][0].(preg_match('/triệu|tr/u', $m[1][0]) ? '' : ' đ'));
                $note = mb_substr(trim(substr($text, $m[0][1], 240)), 0, 200);
                break;
            }
        }
        if ($annual === null && preg_match('/miễn phí thường niên trọn đời/iu', $text)) {
            $annual = 0;
            $note = 'Miễn phí thường niên trọn đời';
        }

        $issuance = null;
        if (preg_match('/phí phát hành[^.]{0,40}?(miễn phí|'.$amount.')/iu', $text, $m)) {
            $issuance = self::money($m[1]);
        }

        $free = null;
        foreach (['/miễn lãi[^.]{0,40}?(\d{2})\s*ngày/iu', '/(\d{2})\s*ngày[^.]{0,20}miễn lãi/iu'] as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $free = (int) $m[1];
                break;
            }
        }

        $rate = null;
        if (preg_match('/lãi suất[^.]{0,50}?(\d{1,2}(?:[.,]\d{1,2})?)\s*%\s*\/\s*năm/iu', $text, $m)) {
            $value = (float) str_replace(',', '.', $m[1]);
            $rate = $value >= 10 && $value <= 45 ? number_format($value, 4, '.', '') : null;
        }

        $income = preg_match('/thu nhập[^.]{0,60}?(?:từ|tối thiểu|trên)\s*(\d+(?:[.,]\d+)?\s*(?:triệu|tr)(?!\p{L}))/iu', $text, $m) ? self::money($m[1]) : null;

        return [
            'annual_fee' => $annual,
            'annual_fee_note' => $note,
            'issuance_fee' => $issuance,
            'interest_free_days' => $free !== null && $free >= 20 && $free <= 70 ? $free : null,
            'interest_rate' => $rate,
            'min_monthly_income' => $income,
        ];
    }
}
