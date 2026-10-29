<?php

use App\Models\User;
use App\Modules\Content\Filament\Resources\Articles\Pages\EditArticle;
use App\Modules\Content\Models\Article;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\SeoMeta;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** @return list<array<string, mixed>> */
function pageJsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $matches);

    return array_map(fn (string $json) => json_decode($json, true), $matches[1]);
}

it('prints description, canonical and open graph tags with a default share image', function () {
    $this->get('http://ezfin.test/cong-cu?utm_source=zalo')
        ->assertOk()
        ->assertSee('<meta name="description" content="', false)
        ->assertSee('<link rel="canonical" href="http://ezfin.test/cong-cu">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:url" content="http://ezfin.test/cong-cu">', false)
        ->assertSee('<meta property="og:locale" content="vi_VN">', false)
        ->assertSee('<meta property="og:image" content="http://ezfin.test/images/og-default.png">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertDontSee('noindex', false);

    expect(file_exists(public_path('images/og-default.png')))->toBeTrue();
});

it('uses the article cover and article type when sharing', function () {
    $article = Article::factory()->published()->create(['title' => 'Lãi kép', 'cover_path' => 'articles/bia.png']);

    $this->get($article->url())
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:title" content="Lãi kép">', false)
        ->assertSee('<meta property="og:image" content="http://ezfin.test/storage/articles/bia.png">', false);
});

it('lets editors override title, description, share image and indexing', function () {
    $article = Article::factory()->published()->create(['title' => 'Lãi kép', 'cover_path' => 'articles/bia.png']);
    $article->seoMeta()->create([
        'title' => 'Lãi kép là gì? Công thức và ví dụ',
        'description' => 'Mô tả viết riêng cho Google.',
        'og_image_path' => 'seo/chia-se.png',
        'noindex' => true,
    ]);

    $this->get($article->url())
        ->assertSee('<title>Lãi kép là gì? Công thức và ví dụ</title>', false)
        ->assertSee('<meta name="description" content="Mô tả viết riêng cho Google.">', false)
        ->assertSee('<meta property="og:image" content="http://ezfin.test/storage/seo/chia-se.png">', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('describes the breadcrumb trail for search engines', function () {
    $article = Article::factory()->published()->create(['title' => 'Lãi kép']);

    $breadcrumb = collect(pageJsonLd($this->get($article->url())->getContent()))->firstWhere('@type', 'BreadcrumbList');

    expect($breadcrumb['itemListElement'])->toHaveCount(4)
        ->and($breadcrumb['itemListElement'][0])->toMatchArray(['position' => 1, 'name' => 'Trang chủ', 'item' => 'http://ezfin.test'])
        ->and($breadcrumb['itemListElement'][2]['item'])->toBe('http://ezfin.test/kien-thuc/'.$article->category->slug)
        ->and($breadcrumb['itemListElement'][3])->toMatchArray(['position' => 4, 'name' => 'Lãi kép', 'item' => $article->url()]);
});

it('saves SEO fields from the admin form only when something is filled in', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin(Role::SuperAdmin)->create());
    $article = Article::factory()->create();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SeoMeta::count())->toBe(0);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['seoMeta.title' => 'Tiêu đề SEO riêng', 'seoMeta.noindex' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->seoMeta()->sole())
        ->title->toBe('Tiêu đề SEO riêng')
        ->noindex->toBeTrue();
});
