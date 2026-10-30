<?php

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Content\Models\Article;
use App\Modules\Core\CoreServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const HOME = 'http://ezfin.test/';

/** Sản phẩm tiết kiệm đã xuất bản, kỳ hạn 12 tháng trực tuyến lĩnh cuối kỳ. */
function homeSavings(string $bank, float $rate): FinancialProduct
{
    $product = FinancialProduct::factory()->savings()->published()->create([
        'institution_id' => FinancialInstitution::factory()->create(['short_name' => $bank])->id,
    ]);
    $product->savingsRates()->forceCreate([
        'term_months' => 12, 'channel' => SavingsChannel::Online->value, 'interest_payment' => InterestPayment::EndOfTerm->value,
        'rate' => $rate, 'min_amount' => 0, 'max_amount' => null, 'effective_from' => today()->subDays(5),
    ]);

    return $product;
}

/** Các đường dẫn nội bộ trong trang, bỏ trùng. */
function internalLinks(string $html): array
{
    preg_match_all('/<a [^>]*href="(http:\/\/ezfin\.test[^"#]*)"/', $html, $matches);

    return array_values(array_unique($matches[1]));
}

it('shows the top savings rates ranked by interest with real data only', function () {
    homeSavings('Ngân hàng Thấp', 4.5);
    homeSavings('Ngân hàng Cao', 5.6);

    $this->get(HOME)->assertOk()
        ->assertSee('Lãi suất tiết kiệm cao nhất kỳ hạn 12 tháng')
        ->assertSeeInOrder(['Ngân hàng Cao', '5,6%/năm', '+5.600.000 đ', 'Ngân hàng Thấp', '4,5%/năm', '+4.500.000 đ'])
        ->assertSee('action="'.route('compare.savings.index').'"', false);
});

it('lets search engines index the home page only on production', function () {
    $this->get(HOME)->assertOk()->assertDontSee('noindex', false)->assertSee('<link rel="canonical" href="'.route('home').'">', false);

    config(['ezfin.indexable' => false]);
    $this->get(HOME)->assertSee('noindex, nofollow', false);
});

it('hides sections that have no data yet', function () {
    $this->get(HOME)->assertOk()
        ->assertDontSee('Lãi suất tiết kiệm cao nhất')
        ->assertDontSee('Kiến thức mới')
        ->assertSee('Tính trước khi quyết định');
});

it('lists the latest published articles only', function () {
    Article::factory()->published()->create(['title' => 'Bài đã đăng']);
    Article::factory()->create(['title' => 'Bài nháp']);

    $this->get(HOME)->assertOk()->assertSee('Kiến thức mới')->assertSee('Bài đã đăng')->assertDontSee('Bài nháp');
});

it('hides sections of modules that are switched off', function () {
    homeSavings('Ngân hàng Cao', 5.6);
    Article::factory()->published()->create(['title' => 'Bài đã đăng']);
    Feature::deactivate(['compare', 'planner', 'content']);

    $this->get(HOME)->assertOk()
        ->assertDontSee('Ngân hàng Cao')
        ->assertDontSee('Bài đã đăng')
        ->assertDontSee('Tính trước khi quyết định')
        ->assertDontSee(route('compare.savings.index', absolute: false))
        ->assertSee('EzFin làm việc thế nào');
});

it('renders every legal page with its title in the footer', function (string $route, string $label) {
    $this->get(route($route))->assertOk()
        ->assertSee('<h1 class="mt-4 text-2xl font-bold sm:text-3xl">'.$label, false);

    $this->get(HOME)->assertSee('href="'.route($route).'"', false);
})->with(collect(CoreServiceProvider::LEGAL_PAGES)->map(fn ($label, $route) => [$route, $label])->all());

it('only links to pages that exist', function (string $page) {
    homeSavings('Ngân hàng Cao', 5.6);
    Article::factory()->published()->create();

    $links = internalLinks($this->get($page)->assertOk()->getContent());

    expect($links)->not->toBeEmpty();
    foreach ($links as $link) {
        expect($this->get($link)->status())->toBe(200, $link);
    }
})->with([HOME, 'http://ezfin.test/phuong-phap-danh-gia', 'http://ezfin.test/gioi-thieu']);

it('shows company contact details only when configured', function () {
    $this->get(route('legal.contact'))->assertOk()->assertSee('đang được cập nhật')->assertDontSee('mailto:');

    config(['ezfin.company.name' => 'Công ty TNHH EzFin', 'ezfin.company.email' => 'lienhe@ezfin.vn', 'ezfin.company.phone' => '028 1234 5678']);

    $this->get(route('legal.contact'))->assertOk()->assertSee('Công ty TNHH EzFin')->assertSee('href="mailto:lienhe@ezfin.vn"', false)->assertSee('href="tel:02812345678"', false);
    $this->get(HOME)->assertSee('Công ty TNHH EzFin')->assertSee('mailto:lienhe@ezfin.vn');
});

it('describes the real ranking rules on the methodology page', function () {
    $this->get(route('legal.methodology'))->assertOk()
        ->assertSee('kiểm tra lại sau '.FinancialProduct::REVIEW_INTERVAL_MONTHS.' tháng')
        ->assertSee('hoàn tiền cao')
        ->assertSee('số dư tối thiểu thấp');
});

it('adds the home and legal pages to the sitemap', function () {
    $xml = simplexml_load_string($this->get('http://ezfin.test/sitemap.xml')->assertOk()->getContent());
    $locs = [];
    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
    }

    expect($locs)->toContain(route('home'))
        ->and($locs)->toContain(...collect(CoreServiceProvider::LEGAL_PAGES)->keys()->map(fn ($route) => route($route))->all());
});

it('promotes only features that are switched on', function () {
    $this->get(HOME)->assertOk()
        ->assertSee('Vay mua nhà')
        ->assertSee(route('planner.tools.show', 'khoan-vay'), false);

    Feature::deactivate('planner');

    $this->get(HOME)->assertOk()
        ->assertDontSee('Vay mua nhà')
        ->assertSee('So sánh thẻ tín dụng');
});
