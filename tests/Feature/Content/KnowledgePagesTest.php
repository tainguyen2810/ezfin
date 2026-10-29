<?php

use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const KNOWLEDGE_URL = 'http://ezfin.test/kien-thuc';

function knowledgeJsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $match);

    return json_decode($match[1], true);
}

it('lists only published articles that are due', function () {
    $live = Article::factory()->published()->create(['title' => 'Bài đang hiển thị']);
    Article::factory()->create(['title' => 'Bài nháp']);
    Article::factory()->inReview()->create(['title' => 'Bài chờ duyệt']);
    Article::factory()->published()->create(['title' => 'Bài hẹn giờ', 'published_at' => now()->addDay()]);
    Article::factory()->published()->create(['title' => 'Bài đã xóa'])->delete();

    $this->get(KNOWLEDGE_URL)
        ->assertOk()
        ->assertSee('Bài đang hiển thị')
        ->assertSee($live->url(), false)
        ->assertSee($live->category->name)
        ->assertDontSee('Bài nháp')
        ->assertDontSee('Bài chờ duyệt')
        ->assertDontSee('Bài hẹn giờ')
        ->assertDontSee('Bài đã xóa');
});

it('shows an honest empty state and is noindex when there is nothing yet', function () {
    $this->get(KNOWLEDGE_URL)
        ->assertOk()
        ->assertSee('Chưa có bài viết')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('filters by category and only links categories that have articles', function () {
    $savings = ArticleCategory::factory()->create(['name' => 'Tiết kiệm', 'slug' => 'tiet-kiem']);
    $cards = ArticleCategory::factory()->create(['name' => 'Thẻ tín dụng', 'slug' => 'the-tin-dung']);
    ArticleCategory::factory()->create(['name' => 'Bảo hiểm', 'slug' => 'bao-hiem']);
    Article::factory()->published()->create(['title' => 'Gửi tiết kiệm online', 'category_id' => $savings->id]);
    Article::factory()->published()->create(['title' => 'Phí thường niên', 'category_id' => $cards->id]);

    $this->get(KNOWLEDGE_URL.'/tiet-kiem')
        ->assertOk()
        ->assertSee('Gửi tiết kiệm online')
        ->assertDontSee('Phí thường niên')
        ->assertSee('<link rel="canonical" href="'.KNOWLEDGE_URL.'/tiet-kiem">', false)
        ->assertSee('href="'.KNOWLEDGE_URL.'/the-tin-dung"', false)
        ->assertDontSee('href="'.KNOWLEDGE_URL.'/bao-hiem"', false);

    $this->get(KNOWLEDGE_URL.'/khong-co')->assertNotFound();
});

it('shows an article with author, dates, sources, disclaimer and schema', function () {
    $this->travelTo('2026-10-01 08:00:00');
    $author = Author::factory()->create(['name' => 'Nguyễn Văn An', 'slug' => 'nguyen-van-an', 'job_title' => 'Chuyên viên tín dụng']);
    $article = Article::factory()->published()->create([
        'title' => 'Lãi kép là gì',
        'slug' => 'lai-kep-la-gi',
        'author_id' => $author->id,
        'published_at' => '2026-10-01 08:00:00',
        'body' => '<h2>Định nghĩa</h2><p>Lãi được nhập vào gốc.</p>',
        'references' => [['title' => 'Ngân hàng Nhà nước', 'url' => 'https://sbv.gov.vn'], ['title' => 'Sách giáo khoa', 'url' => null]],
    ]);
    $this->travelBack();

    $response = $this->get($article->url())
        ->assertOk()
        ->assertSee('<h1 class="mt-2 text-2xl font-bold leading-tight sm:text-4xl">Lãi kép là gì</h1>', false)
        ->assertSee('<h2>Định nghĩa</h2>', false)
        ->assertSee('href="http://ezfin.test/tac-gia/nguyen-van-an"', false)
        ->assertSee('Chuyên viên tín dụng')
        ->assertSee('Xuất bản <time datetime="2026-10-01">01/10/2026</time>', false)
        ->assertDontSee('Cập nhật <time', false)
        ->assertSee('Nguồn tham khảo')
        ->assertSee('<a href="https://sbv.gov.vn"', false)
        ->assertSee('Sách giáo khoa')
        ->assertSee('không phải khuyến nghị tài chính hay đầu tư')
        ->assertSee('<link rel="canonical" href="'.KNOWLEDGE_URL.'/'.$article->category->slug.'/lai-kep-la-gi">', false);

    $schema = knowledgeJsonLd($response->getContent());

    expect($schema['@type'])->toBe('Article')
        ->and($schema['headline'])->toBe('Lãi kép là gì')
        ->and($schema['author']['name'])->toBe('Nguyễn Văn An')
        ->and($schema['datePublished'])->toStartWith('2026-10-01');
});

it('shows the update date only when content changed after publishing', function () {
    $this->travelTo('2026-10-01 09:00:00');
    $article = Article::factory()->published()->create(['published_at' => now()]);
    $article->update(['title' => 'Sửa cùng ngày']);

    $this->get($article->url())->assertDontSee('Cập nhật <time', false);

    $this->travelTo('2026-10-20 09:00:00');
    $article->update(['body' => '<p>Cập nhật lãi suất mới.</p>']);

    $this->get($article->url())->assertSee('Cập nhật <time datetime="2026-10-20">20/10/2026</time>', false);
    expect(knowledgeJsonLd($this->get($article->url())->getContent())['dateModified'])->toStartWith('2026-10-20');
});

it('redirects permanently when the article moved category and hides unpublished ones', function () {
    $article = Article::factory()->published()->create();

    $this->get(KNOWLEDGE_URL.'/chuyen-muc-cu/'.$article->slug)->assertStatus(301)->assertRedirect($article->url());
    $this->get(KNOWLEDGE_URL.'/'.$article->category->slug.'/khong-co')->assertNotFound();

    $draft = Article::factory()->create();
    $this->get(KNOWLEDGE_URL.'/'.$draft->category->slug.'/'.$draft->slug)->assertNotFound();
});

it('escapes plain text fields and never prints unsafe body html', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Tiêu đề <script>alert(1)</script>',
        'excerpt' => '<img src=x onerror=alert(1)>',
        'body' => '<p onclick="alert(1)">Nội dung</p><script>alert(2)</script>',
    ]);

    $html = $this->get($article->url())->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert')
        ->not->toContain('<img src=x')
        ->not->toContain('onclick=')
        ->toContain('Tiêu đề &lt;script&gt;');
});

it('shows the author page with bio and published articles only', function () {
    $author = Author::factory()->create(['name' => 'Trần Thị Bình', 'slug' => 'tran-thi-binh', 'bio' => "Mười năm làm tín dụng.\n\nThạc sĩ tài chính.", 'links' => ['https://www.linkedin.com/in/binh']]);
    Article::factory()->published()->create(['author_id' => $author->id, 'title' => 'Bài của Bình']);
    Article::factory()->create(['author_id' => $author->id, 'title' => 'Nháp của Bình']);

    $this->get('http://ezfin.test/tac-gia/tran-thi-binh')
        ->assertOk()
        ->assertSee('Trần Thị Bình')
        ->assertSee('<p>Mười năm làm tín dụng.</p>', false)
        ->assertSee('www.linkedin.com')
        ->assertSee('Bài của Bình')
        ->assertDontSee('Nháp của Bình');

    $author->update(['is_active' => false]);
    $this->get('http://ezfin.test/tac-gia/tran-thi-binh')->assertNotFound();
});

it('hides knowledge pages and the menu item when the content module is off', function () {
    $article = Article::factory()->published()->create();
    $this->get('http://ezfin.test/the-tin-dung')->assertSee('href="'.KNOWLEDGE_URL.'"', false);

    Feature::deactivate('content');

    $this->get(KNOWLEDGE_URL)->assertNotFound();
    $this->get($article->url())->assertNotFound();
    $this->get('http://ezfin.test/tac-gia/'.$article->author->slug)->assertNotFound();
    $this->get('http://ezfin.test/the-tin-dung')->assertDontSee('href="'.KNOWLEDGE_URL.'"', false);
});
