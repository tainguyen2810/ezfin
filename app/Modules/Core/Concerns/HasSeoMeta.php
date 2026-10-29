<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Models\SeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Model có trang công khai và cho phép biên tập SEO (form quản trị dùng SeoFields::section()).
 *
 * @mixin Model
 */
trait HasSeoMeta
{
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /** Bỏ trang được đánh dấu noindex, dùng khi dựng sitemap. */
    public function scopeIndexable(Builder $query): void
    {
        $query->whereDoesntHave('seoMeta', fn (Builder $seo) => $seo->where('noindex', true));
    }
}
