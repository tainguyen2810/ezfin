<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;

/** Thẻ tín dụng Vietcombank: danh sách + trang chi tiết từng thẻ (phí phát hành, phí thường niên, số ngày miễn lãi). */
class VietcombankCards extends VietcombankCatalog
{
    public function kind(): SyncKind
    {
        return SyncKind::CreditCard;
    }

    public function url(): string
    {
        return self::BASE.'/vi-VN/KHCN/SPDV/The';
    }

    protected function searchQuery(): array
    {
        return [
            's' => '{5BFF185D-F650-4A6C-A2A1-D26C58B43C15}',
            'itemid' => '{967D60F8-65BA-4CEB-AEB4-E74845673377}',
            'v' => '{755FF168-E210-47C4-9122-97730FC37054}',
            'type' => 'Thẻ tín dụng',
        ];
    }

    protected function read(Fetcher $http): array
    {
        $items = [];
        foreach ($this->listing($http) as $card) {
            $sections = $this->sections($http, $card['url']);
            $fee = $sections['phí thường niên'] ?? '';
            $annual = preg_match('/thẻ chính\s*:?\s*([^\n]+)/iu', $fee, $m) ? CatalogParsing::money($m[1]) : CatalogParsing::money(strtok($fee, "\n") ?: null);
            $benefitText = $card['name'].' '.implode(' ', array_keys($card['features'])).' '.implode(' ', array_intersect_key($sections, array_flip(['đặc quyền', 'tối ưu tài chính', 'tích điểm vcb loyalty'])));
            $freeDays = preg_match('/(\d{2})\s*ngày/u', $sections['tối ưu tài chính'] ?? '', $d) ? (int) $d[1] : null;

            $items[] = $this->item(
                $this->slugFromUrl($card['url']),
                $card['name'],
                $card['url'],
                CatalogParsing::imageUrl($card['image'], self::BASE, original: true),
                collect($card['features'])->map(fn ($value, $label) => mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1).': '.$value)->implode('; ') ?: null,
                [
                    'network' => CatalogParsing::network($card['name']),
                    'annual_fee' => $annual,
                    'annual_fee_note' => $fee === '' ? null : mb_substr(str_replace("\n", '; ', $fee), 0, 250),
                    'issuance_fee' => CatalogParsing::money($sections['phí phát hành'] ?? null),
                    'interest_free_days' => $freeDays,
                    'benefits' => CatalogParsing::benefits($benefitText),
                ],
            );
        }

        return $items;
    }
}
