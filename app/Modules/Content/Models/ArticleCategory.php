<?php

namespace App\Modules\Content\Models;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Core\Actions\RecordRedirect;
use App\Modules\Core\Concerns\Auditable;
use Database\Factories\ArticleCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chuyên mục kiến thức: /kien-thuc/{slug}.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property int $sort_order
 */
#[UseFactory(ArticleCategoryFactory::class)]
class ArticleCategory extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected static function booted(): void
    {
        // Đổi slug chuyên mục đổi URL của chuyên mục và mọi bài trong đó: giữ URL cũ bằng 301.
        static::updated(function (self $category) {
            if (! $category->wasChanged('slug')) {
                return;
            }

            $redirects = app(RecordRedirect::class);
            $old = $category->getOriginal('slug');

            $redirects->handle(route('content.categories.show', $old, false), route('content.categories.show', $category->slug, false));

            $category->articles()->where('status', ArticleStatus::Published)->pluck('slug')->each(
                fn (string $slug) => $redirects->handle(
                    route('content.articles.show', [$old, $slug], false),
                    route('content.articles.show', [$category->slug, $slug], false),
                ),
            );
        });
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chuyên mục';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}
