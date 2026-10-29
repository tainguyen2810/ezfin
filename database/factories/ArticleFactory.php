<?php

namespace Database\Factories;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Chỉ dùng trong test. Nội dung là văn bản mẫu.
 *
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = 'Bài viết thử '.Str::upper(Str::random(5));

        return [
            'category_id' => ArticleCategory::factory(),
            'author_id' => Author::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => 'Tóm tắt bài viết thử nghiệm.',
            'body' => '<p>Nội dung bài viết thử nghiệm.</p>',
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn () => ['status' => ArticleStatus::InReview, 'submitted_at' => now()]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    }
}
