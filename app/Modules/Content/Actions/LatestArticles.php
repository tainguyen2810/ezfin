<?php

namespace App\Modules\Content\Actions;

use App\Modules\Content\Models\Article;
use Illuminate\Support\Collection;

/** Bài mới xuất bản, dùng cho trang chủ và các module khác. */
class LatestArticles
{
    /** @return Collection<int, Article> */
    public function handle(int $limit = 3): Collection
    {
        return Article::query()->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}
