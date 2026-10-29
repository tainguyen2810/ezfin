<?php

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/** @return list<string> */
function sitemapLocs(): array
{
    $response = test()->get('http://ezfin.test/sitemap.xml')->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/xml');

    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();

    $locs = [];
    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
    }

    return $locs;
}

it('lists only published, indexable public pages of active modules', function () {
    $card = FinancialProduct::factory()->published()->create(['slug' => 'the-hien-thi']);
    $card->creditCard()->create([]);
    $hidden = FinancialProduct::factory()->published()->create(['slug' => 'the-noindex']);
    $hidden->creditCard()->create([]);
    $hidden->seoMeta()->create(['noindex' => true]);
    $draft = FinancialProduct::factory()->create(['slug' => 'the-nhap']);
    $draft->creditCard()->create([]);
    $closed = FinancialProduct::factory()->published()->create(['slug' => 'the-ngan-hang-dong', 'institution_id' => FinancialInstitution::factory()->create(['is_active' => false])->id]);
    $closed->creditCard()->create([]);
    $account = FinancialProduct::factory()->bankAccount()->published()->create(['slug' => 'tk-hien-thi']);
    $account->bankAccount()->create([]);

    $article = Article::factory()->published()->create(['slug' => 'bai-hien-thi']);
    Article::factory()->create(['slug' => 'bai-nhap']);
    Article::factory()->published()->create(['slug' => 'bai-hen-gio', 'published_at' => now()->addDay()]);
    Author::factory()->create(['slug' => 'tac-gia-chua-co-bai']);

    $locs = sitemapLocs();

    expect($locs)->toContain(
        'http://ezfin.test/lai-suat-tiet-kiem',
        'http://ezfin.test/the-tin-dung',
        'http://ezfin.test/the-tin-dung/the-hien-thi',
        'http://ezfin.test/tai-khoan-ngan-hang/tk-hien-thi',
        'http://ezfin.test/cong-cu',
        'http://ezfin.test/cong-cu/lai-kep',
        'http://ezfin.test/cong-cu/tra-gop',
        'http://ezfin.test/kien-thuc',
        'http://ezfin.test/kien-thuc/'.$article->category->slug,
        $article->url(),
        'http://ezfin.test/tac-gia/'.$article->author->slug,
    );

    foreach (['the-noindex', 'the-nhap', 'the-ngan-hang-dong', 'bai-nhap', 'bai-hen-gio', 'tac-gia-chua-co-bai', '/_ui', '/di/', 'app.ezfin.test'] as $excluded) {
        expect(collect($locs)->contains(fn (string $loc) => str_contains($loc, $excluded)))->toBeFalse();
    }

    expect($locs)->toHaveCount(count(array_unique($locs)));
});

it('drops the urls of a module that is switched off', function () {
    Article::factory()->published()->create();

    Feature::deactivate('content');
    Feature::deactivate('planner');

    $locs = sitemapLocs();

    expect(collect($locs)->contains(fn (string $loc) => str_contains($loc, '/kien-thuc') || str_contains($loc, '/cong-cu')))->toBeFalse()
        ->and($locs)->toContain('http://ezfin.test/the-tin-dung');
});

it('omits the knowledge section until an article is published', function () {
    expect(sitemapLocs())->not->toContain('http://ezfin.test/kien-thuc');
});

it('points crawlers to the sitemap on the public site in production', function () {
    $this->get('http://ezfin.test/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee("Disallow: /di/\n", false)
        ->assertSee('Sitemap: http://ezfin.test/sitemap.xml', false)
        ->assertDontSee("Disallow: /\n", false);
});

it('blocks crawlers on the app domain and outside production', function () {
    $this->get('http://app.ezfin.test/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);

    config(['ezfin.indexable' => false]);

    $this->get('http://ezfin.test/robots.txt')->assertSee("Disallow: /\n", false)->assertDontSee('Sitemap:', false);
    $this->get('http://ezfin.test/the-tin-dung')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
