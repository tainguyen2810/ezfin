<?php

namespace App\Modules\Content;

use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Modules\Content\Policies\ArticlePolicy;
use App\Modules\Content\Policies\ContentTaxonomyPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;
use App\Support\Seo\SitemapUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ContentServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'content';

    protected function publicNavigation(): array
    {
        return [
            new NavItem('Kiến thức', 'content.articles.index', order: 50),
        ];
    }

    protected function sitemap(): iterable
    {
        $articles = Article::query()->published()->indexable()
            ->with('category:id,slug')
            ->latest('published_at')
            ->get(['id', 'category_id', 'slug', 'published_at', 'content_updated_at']);

        if ($articles->isEmpty()) {
            return;
        }

        yield new SitemapUrl(route('content.articles.index'), $articles->map->lastModified()->max());

        foreach ($articles->groupBy('category_id') as $inCategory) {
            yield new SitemapUrl(route('content.categories.show', $inCategory->first()->category->slug), $inCategory->map->lastModified()->max());
        }

        foreach ($articles as $article) {
            yield new SitemapUrl($article->url(), $article->lastModified());
        }

        $authors = Author::query()->active()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('slug')
            ->get(['id', 'slug']);

        foreach ($authors as $author) {
            yield new SitemapUrl(route('content.authors.show', $author->slug));
        }
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(ArticleCategory::class, ContentTaxonomyPolicy::class);
        Gate::policy(Author::class, ContentTaxonomyPolicy::class);
    }
}
