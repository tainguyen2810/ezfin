<?php

use App\Modules\Compare\Actions\FindSimilarCreditCards;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const DETAIL_URL = 'http://ezfin.test/the-tin-dung/';

/** Thẻ đã xuất bản cho trang chi tiết. */
function detailCard(string $slug, array $card = [], array $product = [], ?FinancialInstitution $institution = null): FinancialProduct
{
    $model = FinancialProduct::factory()->published()->create([
        'name' => 'Thẻ '.str($slug)->headline(),
        'slug' => $slug,
        'institution_id' => ($institution ?? FinancialInstitution::factory()->create())->id,
        ...$product,
    ]);
    $model->creditCard()->create($card);

    return $model;
}

/** Đọc khối JSON-LD trong trang. */
function jsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    return json_decode($match[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->bank = FinancialInstitution::factory()->create([
        'type' => InstitutionType::Bank, 'name' => 'Ngân hàng TMCP Alpha', 'short_name' => 'Alpha', 'website' => 'https://alpha.example',
    ]);
    $this->card = detailCard('hoan-tien', [
        'network' => CardNetwork::Visa, 'annual_fee' => 499_000, 'issuance_fee' => 0, 'interest_rate' => 28.5,
        'cashback_rate' => 5, 'benefits' => ['cashback', 'shopping'],
    ], [
        'summary' => 'Hoàn tiền khi mua sắm trực tuyến.',
        'pros' => ['Hoàn tiền 5% mua sắm online'], 'cons' => ['Giới hạn hoàn tiền mỗi tháng'],
        'source_url' => 'https://alpha.example/the-hoan-tien', 'source_note' => 'Biểu phí áp dụng từ 01/10/2026',
    ], $this->bank);
});

it('shows the card with facts, pros and cons, source and verification date', function () {
    $this->get(DETAIL_URL.'hoan-tien')
        ->assertOk()
        ->assertSee('<h1 class="text-2xl font-bold sm:text-3xl">Thẻ Hoan Tien</h1>', false)
        ->assertSee('<link rel="canonical" href="'.DETAIL_URL.'hoan-tien">', false)
        ->assertDontSee('noindex', false)
        ->assertSeeInOrder(['Phí thường niên', '499.000 đ', 'Phí phát hành', 'Miễn phí', 'Lãi suất', '28,5%/năm'])
        ->assertSee('Hoàn tiền 5% mua sắm online')
        ->assertSee('Giới hạn hoàn tiền mỗi tháng')
        ->assertSee('Cập nhật '.$this->card->verified_at->format('d/m/Y'))
        ->assertSee('href="https://alpha.example/the-hoan-tien"', false)
        ->assertSee('Biểu phí áp dụng từ 01/10/2026')
        ->assertSee('Thêm vào so sánh')
        // Chưa có link đối tác: không hiện nút đăng ký và công bố liên kết.
        ->assertDontSee('liên kết đối tác')
        ->assertDontSee('rel="sponsored', false);
});

it('outputs valid schema.org JSON-LD without ratings', function () {
    $html = $this->get(DETAIL_URL.'hoan-tien')->getContent();
    $schema = jsonLd($html);

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@type'])->toBe('WebPage')
        ->and($schema['dateModified'])->toBe($this->card->verified_at->toDateString())
        ->and($schema['mainEntity'])->toMatchArray([
            '@type' => 'CreditCard',
            'name' => 'Thẻ Hoan Tien',
            'url' => DETAIL_URL.'hoan-tien',
            'annualPercentageRate' => 28.5,
            'brand' => 'Visa',
            'feesAndCommissionsSpecification' => 'Phí thường niên: 499.000 đ; Phí phát hành: Miễn phí',
            'provider' => ['@type' => 'BankOrCreditUnion', 'name' => 'Ngân hàng TMCP Alpha', 'url' => 'https://alpha.example'],
        ])
        ->and($schema['breadcrumb']['itemListElement'])->toHaveCount(3)
        ->and($schema['breadcrumb']['itemListElement'][2])->toBe(['@type' => 'ListItem', 'position' => 3, 'name' => 'Thẻ Hoan Tien', 'item' => DETAIL_URL.'hoan-tien'])
        ->and($html)->not->toContain('aggregateRating')
        ->and($html)->not->toContain('ratingValue');
});

it('escapes script tags inside JSON-LD', function () {
    $this->card->update(['summary' => '</script><script>alert(1)</script>']);

    $html = $this->get(DETAIL_URL.'hoan-tien')->getContent();

    expect($html)->not->toContain('</script><script>alert(1)')
        ->and(jsonLd($html)['mainEntity']['description'])->toBe('</script><script>alert(1)</script>');
});

it('suggests similar cards by shared benefits, then closest annual fee', function () {
    detailCard('mua-sam', ['annual_fee' => 2_000_000, 'benefits' => ['shopping']]);
    detailCard('hoan-tien-mua-sam', ['annual_fee' => 3_000_000, 'benefits' => ['cashback', 'shopping']]);
    detailCard('du-lich-re', ['annual_fee' => 500_000, 'benefits' => ['travel']]);
    detailCard('du-lich-dat', ['annual_fee' => 5_000_000, 'benefits' => ['travel']]);
    detailCard('nhap', ['benefits' => ['cashback']], ['status' => ProductStatus::Draft, 'verified_at' => null, 'source_url' => null]);

    $similar = app(FindSimilarCreditCards::class)->handle($this->card)->pluck('slug')->all();

    expect($similar)->toBe(['hoan-tien-mua-sam', 'mua-sam', 'du-lich-re']);

    $this->get(DETAIL_URL.'hoan-tien')
        ->assertSee('Thẻ tương tự')
        ->assertSeeInOrder(['Thẻ Hoan Tien Mua Sam', 'Thẻ Mua Sam', 'Thẻ Du Lich Re'])
        ->assertSee('href="'.DETAIL_URL.'mua-sam"', false);
});

it('returns 404 for cards that are not public', function () {
    detailCard('nhap', product: ['status' => ProductStatus::Draft, 'verified_at' => null, 'source_url' => null]);
    detailCard('ngan-hang-ngung', institution: FinancialInstitution::factory()->create(['is_active' => false]));

    $this->get(DETAIL_URL.'nhap')->assertNotFound();
    $this->get(DETAIL_URL.'ngan-hang-ngung')->assertNotFound();
    $this->get(DETAIL_URL.'khong-ton-tai')->assertNotFound();
    $this->get(DETAIL_URL.'Hoan-Tien')->assertNotFound();
});

it('keeps the compare page reachable and links compared cards to their details', function () {
    detailCard('mua-sam', ['annual_fee' => 0]);

    $this->get(DETAIL_URL.'so-sanh?ids=hoan-tien,mua-sam')
        ->assertOk()
        ->assertSee('href="'.DETAIL_URL.'hoan-tien"', false);
});

it('is off together with the compare module', function () {
    Feature::deactivate('compare');

    $this->get(DETAIL_URL.'hoan-tien')->assertNotFound();
});
