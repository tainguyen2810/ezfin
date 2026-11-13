<?php

use App\Modules\Content\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('describes the site as Organization and WebSite on the home page without placeholder contact data', function () {
    config(['ezfin.company.email' => null, 'ezfin.company.phone' => null]);

    $html = $this->get('http://ezfin.test/')->assertOk()->getContent();

    expect($html)->toContain('"@type":"WebSite"')->toContain('"@type":"Organization"')->not->toContain('"telephone"');
});

it('stores article FAQ as plain text, drops empty pairs and renders FAQPage data', function () {
    $article = Article::factory()->published()->create();
    $article->forceFill(['faq' => [
        ['question' => 'Lãi kép là gì?', 'answer' => '<script>x</script>Lãi được cộng vào gốc.'],
        ['question' => 'Câu thiếu trả lời', 'answer' => ' '],
    ]])->saveQuietly();

    expect($article->fresh()->faq)->toEqual([['question' => 'Lãi kép là gì?', 'answer' => 'xLãi được cộng vào gốc.']]);

    $this->get(route('content.articles.show', [$article->category->slug, $article->slug]))
        ->assertOk()
        ->assertSee('Lãi kép là gì?')
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('<script>x</script>', false);
});
