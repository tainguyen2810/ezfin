<?php

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Modules\Content\Support\StarterContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('public'));

it('publishes the starter articles with sources, FAQ and the editorial author', function () {
    $this->artisan('ezfin:starter-content')->assertSuccessful();

    $count = count(StarterContent::articles());
    expect(Article::count())->toBe($count)
        ->and(Article::where('status', ArticleStatus::Published)->count())->toBe($count)
        ->and(ArticleCategory::count())->toBe(count(StarterContent::CATEGORIES))
        ->and(Author::sole()->name)->toBe('Ban biên tập EzFin');

    Article::all()->each(function (Article $article) {
        expect($article->references)->not->toBeEmpty()
            ->and($article->faq)->not->toBeEmpty()
            ->and($article->publishingErrors())->toBe([]);
        Storage::disk('public')->assertExists($article->cover_path);
    });

    $article = Article::where('slug', 'bao-hiem-tien-gui-350-trieu-dong')->sole();
    $this->get($article->url())->assertOk()->assertSee('350 triệu đồng');
});

it('is safe to run again and keeps articles the editor already has', function () {
    $category = ArticleCategory::create(['name' => 'Tiết kiệm của tôi', 'slug' => 'tiet-kiem']);
    $this->artisan('ezfin:starter-content --draft')->assertSuccessful();
    $edited = Article::where('slug', 'cach-so-sanh-lai-suat-tiet-kiem')->sole();
    $edited->update(['title' => 'Tiêu đề đã sửa']);

    $this->artisan('ezfin:starter-content')->expectsOutputToContain('Đã tạo 0 bài viết')->assertSuccessful();

    expect(Article::count())->toBe(count(StarterContent::articles()))
        ->and(Article::where('status', ArticleStatus::Draft)->count())->toBe(Article::count())
        ->and($edited->fresh()->title)->toBe('Tiêu đề đã sửa')
        ->and($category->fresh()->name)->toBe('Tiết kiệm của tôi');
});
