<?php

namespace App\Modules\Content\Http\Controllers;

use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ArticleController
{
    public const PER_PAGE = 12;

    public function index(): View
    {
        return view('content::articles.index', [
            'category' => null,
            'categories' => $this->categories(),
            'articles' => $this->listing(Article::query()),
        ]);
    }

    public function category(string $category): View
    {
        $category = ArticleCategory::where('slug', $category)->firstOrFail();

        return view('content::articles.index', [
            'category' => $category,
            'categories' => $this->categories(),
            'articles' => $this->listing($category->articles()->getQuery()),
        ]);
    }

    public function show(string $category, string $slug): View|RedirectResponse
    {
        $article = Article::query()->published()->where('slug', $slug)
            ->with(['category', 'author'])
            ->firstOrFail();

        // Bài đổi chuyên mục: chuyển hướng vĩnh viễn về URL đúng.
        if ($article->category->slug !== $category) {
            return redirect()->to($article->url(), 301);
        }

        return view('content::articles.show', [
            'article' => $article,
            'related' => Article::query()->published()
                ->where('category_id', $article->category_id)
                ->whereKeyNot($article->id)
                ->with(['category', 'author'])
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    /** Chuyên mục có ít nhất một bài đang hiển thị. */
    private function categories(): Collection
    {
        return ArticleCategory::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')->orderBy('name')
            ->get();
    }

    private function listing(Builder $query): LengthAwarePaginator
    {
        return $query->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
