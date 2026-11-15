<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;

/**
 * Khoản vay cá nhân Vietcombank từ trang danh sách: tên, ảnh, mức vay tối đa (% giá trị tài sản hoặc số tiền), thời hạn
 * tối đa. Vietcombank không công bố lãi vay trên website nên lãi để trống (trang EzFin ghi "chưa công bố").
 * Chỉ lấy loại EzFin so sánh (tiêu dùng, mua nhà, mua ô tô); vay kinh doanh, cầm cố bỏ qua.
 */
class VietcombankLoans extends VietcombankCatalog
{
    public function kind(): SyncKind
    {
        return SyncKind::Loan;
    }

    public function url(): string
    {
        return self::BASE.'/vi-VN/KHCN/SPDV/Vay';
    }

    protected function searchQuery(): array
    {
        return [
            's' => '{BD096303-44C1-4253-8C7C-D79CC04E9C1F}',
            'itemid' => '{69441009-8E89-47B1-AB22-A1BD74B17518}',
            'v' => '{E1DB4CC4-AE84-4BA5-A0F2-2D5EA68E82D4}',
        ];
    }

    protected function read(Fetcher $http): array
    {
        $items = [];
        foreach ($this->listing($http) as $loan) {
            $kind = CatalogParsing::loanKind($loan['name']);
            if ($kind === null) {
                continue;
            }
            $amount = collect($loan['features'])->first(fn ($value, $label) => str_contains($label, 'mức') || str_contains($label, 'số tiền'));
            $term = collect($loan['features'])->first(fn ($value, $label) => str_contains($label, 'thời hạn') || str_contains($label, 'thời gian'));
            $ltv = $amount !== null && preg_match('/%\s*giá trị/u', $amount) ? CatalogParsing::percent($amount) : null;

            $items[] = $this->item(
                $this->slugFromUrl($loan['url']),
                $loan['name'],
                $loan['url'],
                CatalogParsing::imageUrl($loan['image'], self::BASE, original: true),
                collect(['Mức vay tối đa' => $amount, 'Thời hạn tối đa' => $term])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ') ?: null,
                [
                    'kind' => $kind,
                    'max_ltv' => $ltv,
                    'max_amount' => $ltv === null && $amount !== null && preg_match('/tỷ|triệu|vnd/iu', $amount) ? CatalogParsing::money($amount) : null,
                    'max_term_months' => CatalogParsing::months($term),
                ],
            );
        }

        return $items;
    }
}
