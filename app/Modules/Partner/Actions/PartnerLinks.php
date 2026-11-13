<?php

namespace App\Modules\Partner\Actions;

use App\Modules\Partner\Models\OutboundLink;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

/**
 * Nút đối tác cho sản phẩm (dùng ở trang so sánh). Rỗng khi tắt module đối tác.
 * Một sản phẩm có nhiều link đang bật thì dùng link sửa gần nhất.
 */
class PartnerLinks
{
    /**
     * @param  iterable<int>  $productIds
     * @return Collection<int, OutboundLink> theo product_id
     */
    public function forProducts(iterable $productIds): Collection
    {
        $ids = collect($productIds)->filter()->unique()->values();

        if ($ids->isEmpty() || ! Feature::active('partner')) {
            return collect();
        }

        return OutboundLink::query()->active()
            ->whereIn('product_id', $ids)
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->get(['id', 'code', 'product_id', 'cta_label'])
            ->unique('product_id')
            ->keyBy('product_id');
    }

    public function forProduct(int $productId): ?OutboundLink
    {
        return $this->forProducts([$productId])->get($productId);
    }

    /**
     * Tên các tổ chức đang có liên kết đối tác bật (cho trang Công bố liên kết). Rỗng khi tắt module đối tác.
     *
     * @return list<string>
     */
    public function activeInstitutions(): array
    {
        if (! Feature::active('partner')) {
            return [];
        }

        return OutboundLink::query()->active()->whereNotNull('product_id')
            ->with('product.institution:id,name,short_name')
            ->get(['id', 'product_id'])
            ->map(fn (OutboundLink $link) => $link->product?->institution)
            ->filter()
            ->map(fn ($institution) => $institution->short_name ?: $institution->name)
            ->unique()->sort()->values()->all();
    }
}
