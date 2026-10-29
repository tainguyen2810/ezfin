<?php

namespace App\Modules\Content\Models;

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

    public static function auditSubjectLabel(): string
    {
        return 'Chuyên mục';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}
