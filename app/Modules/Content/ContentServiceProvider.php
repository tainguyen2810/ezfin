<?php

namespace App\Modules\Content;

use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Modules\Content\Policies\ArticlePolicy;
use App\Modules\Content\Policies\ContentTaxonomyPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;
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

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(ArticleCategory::class, ContentTaxonomyPolicy::class);
        Gate::policy(Author::class, ContentTaxonomyPolicy::class);
    }
}
