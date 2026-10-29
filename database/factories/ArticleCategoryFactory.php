<?php

namespace Database\Factories;

use App\Modules\Content\Models\ArticleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Chỉ dùng trong test.
 *
 * @extends Factory<ArticleCategory>
 */
class ArticleCategoryFactory extends Factory
{
    protected $model = ArticleCategory::class;

    public function definition(): array
    {
        $name = 'Chuyên mục thử '.Str::upper(Str::random(4));

        return ['name' => $name, 'slug' => Str::slug($name)];
    }
}
