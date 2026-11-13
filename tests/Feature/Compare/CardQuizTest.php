<?php

use App\Modules\Compare\Actions\RecommendCreditCards;
use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CardQuizAnswers;
use App\Modules\Partner\Models\OutboundLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

function quizCard(string $name, array $detail, array $product = []): FinancialProduct
{
    $card = FinancialProduct::factory()->published()->create([
        'name' => $name, 'slug' => str($name)->slug()->toString(),
        'institution_id' => FinancialInstitution::factory()->create()->id, ...$product,
    ]);
    $card->creditCard()->create($detail);

    return $card;
}

function quizUrl(array $query): string
{
    return 'http://ezfin.test/the-tin-dung/goi-y?'.http_build_query($query);
}

beforeEach(function () {
    Feature::activate('compare');

    quizCard('Thẻ Hoàn Tiền', ['annual_fee' => 500_000, 'min_monthly_income' => 5_000_000, 'interest_rate' => 30, 'cashback_rate' => 5, 'cashback_monthly_cap' => 600_000, 'foreign_transaction_fee' => 3, 'benefits' => ['cashback', 'shopping']]);
    quizCard('Thẻ Miễn Phí', ['annual_fee' => 0, 'min_monthly_income' => 4_000_000, 'interest_rate' => 28, 'cashback_rate' => 0.5, 'cashback_monthly_cap' => 300_000, 'foreign_transaction_fee' => 3, 'benefits' => ['cashback']]);
    quizCard('Thẻ Lãi Thấp', ['annual_fee' => 300_000, 'min_monthly_income' => 5_000_000, 'interest_rate' => 22, 'foreign_transaction_fee' => 2.5, 'benefits' => ['installment']]);
    quizCard('Thẻ Du Lịch', ['annual_fee' => 1_500_000, 'min_monthly_income' => 20_000_000, 'interest_rate' => 26, 'foreign_transaction_fee' => 1.5, 'benefits' => ['miles', 'travel']]);
    quizCard('Thẻ Chưa Công Bố', ['annual_fee' => 0, 'benefits' => []]);
    quizCard('Thẻ Nháp', ['annual_fee' => 0, 'cashback_rate' => 10, 'benefits' => ['cashback', 'shopping']], ['status' => ProductStatus::Draft]);
});

it('ranks eligible cards by matched priorities then net cashback after fees', function () {
    $result = app(RecommendCreditCards::class)->handle(new CardQuizAnswers(
        income: 10_000_000, spend: 10_000_000, priorities: [CardBenefit::Cashback, CardBenefit::Shopping], repayment: 'het',
    ));

    expect($result['cards']->pluck('card.name')->all())->toBe(['Thẻ Hoàn Tiền', 'Thẻ Miễn Phí', 'Thẻ Chưa Công Bố', 'Thẻ Lãi Thấp'])
        ->and($result['eligible'])->toBe(4)
        ->and($result['excluded'])->toBe(1)
        // 10.000.000 đ × 5% = 500.000 đ/tháng (dưới trần 600.000 đ) × 12; trừ phí năm 500.000 đ.
        ->and($result['cards'][0]['cashback'])->toBe(6_000_000)
        ->and($result['cards'][0]['net'])->toBe(5_500_000)
        ->and($result['cards'][1]['cashback'])->toBe(600_000);
});

it('puts the lowest interest rate first for people who carry a balance', function () {
    $this->get(quizUrl(['thu_nhap' => '10.000.000', 'chi_tieu' => '10000000', 'uu_tien' => ['hoan-tien'], 'tra_no' => 'mot-phan']))
        ->assertOk()
        ->assertSeeInOrder(['1. Thẻ Lãi Thấp', '2. Thẻ Miễn Phí', '3. Thẻ Hoàn Tiền', '4. Thẻ Chưa Công Bố'])
        ->assertSee('thấp nhất trong các thẻ bạn đủ điều kiện')
        ->assertSee('1 thẻ khác yêu cầu thu nhập cao hơn')
        ->assertDontSee('Thẻ Du Lịch')
        ->assertDontSee('Thẻ Nháp')
        ->assertSee('noindex', false);
});

it('explains each suggestion and weighs foreign fees for travellers', function () {
    $this->get(quizUrl(['thu_nhap' => '30000000', 'chi_tieu' => '20000000', 'uu_tien' => ['du-lich', 'khong-co'], 'tra_no' => 'het', 'nuoc_ngoai' => 'co']))
        ->assertOk()
        ->assertSeeInOrder(['1. Thẻ Du Lịch', 'Hợp ưu tiên của bạn: du lịch.', 'Phí giao dịch ngoại tệ 1,5%', 'Thu nhập tối thiểu 20.000.000 đ/tháng: bạn đáp ứng.'])
        ->assertSee('Hoàn tiền ước tính 7.200.000 đ/năm');
});

it('shows the empty quiz as an indexable page and asks for missing answers', function () {
    $this->get('http://ezfin.test/the-tin-dung/goi-y')->assertOk()
        ->assertSee('Thẻ tín dụng nào hợp với bạn?')
        ->assertDontSee('Thẻ phù hợp nhất với bạn')
        ->assertDontSee('noindex', false);

    $this->get(quizUrl(['thu_nhap' => '10000000', 'chi_tieu' => 'abc']))->assertOk()
        ->assertSee('Cần nhập thu nhập, mức chi tiêu và chọn cách trả nợ')
        ->assertDontSee('Thẻ phù hợp nhất với bạn');
    $this->get(quizUrl(['thu_nhap' => ['x'], 'uu_tien' => 'hoan-tien', 'tra_no' => ['het']]))->assertOk();
});

it('shows partner buttons with the disclosure and links from the card list', function () {
    $link = OutboundLink::factory()->create(['product_id' => FinancialProduct::where('name', 'Thẻ Hoàn Tiền')->value('id')]);

    $this->get(quizUrl(['thu_nhap' => '10000000', 'chi_tieu' => '10000000', 'tra_no' => 'het']))
        ->assertSee('href="'.$link->url().'"', false)
        ->assertSee('rel="sponsored nofollow noopener"', false)
        ->assertSee('liên kết đối tác');

    $this->get('http://ezfin.test/the-tin-dung')->assertSee(route('compare.credit-cards.quiz'), false);
    $this->get('http://ezfin.test/sitemap.xml')->assertSee('http://ezfin.test/the-tin-dung/goi-y', false);
});

it('suggests nothing when every card needs a higher income', function () {
    $this->get(quizUrl(['thu_nhap' => '1000000', 'chi_tieu' => '1000000', 'tra_no' => 'het']))->assertOk()
        ->assertSee('1. Thẻ Chưa Công Bố');

    FinancialProduct::where('name', 'Thẻ Chưa Công Bố')->update(['status' => ProductStatus::Draft]);
    $this->get(quizUrl(['thu_nhap' => '1000000', 'chi_tieu' => '1000000', 'tra_no' => 'het']))->assertOk()
        ->assertSee('Chưa có thẻ phù hợp với thu nhập này');
});
