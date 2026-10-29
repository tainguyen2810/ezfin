<?php

use App\Modules\Compare\Actions\FindComparableCreditCards;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CreditCardComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const COMPARE_URL = 'http://ezfin.test/the-tin-dung/so-sanh';

/** Thẻ đã xuất bản để so sánh; slug = $slug. */
function comparableCard(string $slug, array $card = [], array $product = [], ?FinancialInstitution $institution = null): FinancialProduct
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

function compared(array $slugs): array
{
    return app(FindComparableCreditCards::class)->handle($slugs)->pluck('slug')->all();
}

beforeEach(function () {
    comparableCard('a', ['annual_fee' => 0, 'cashback_rate' => 5, 'interest_free_days' => 45], ['pros' => ['Hoàn tiền cao', 'Miễn phí']]);
    comparableCard('b', ['annual_fee' => 499_000, 'cashback_rate' => null, 'interest_free_days' => 55]);
    comparableCard('c', ['annual_fee' => 0, 'cashback_rate' => 1, 'interest_free_days' => 45]);
    comparableCard('d', ['annual_fee' => 1_000_000]);
});

it('keeps the visitor order, removes duplicates and caps at 3 cards', function () {
    expect(compared(['c', 'a', 'c', 'b', 'd']))->toBe(['c', 'a', 'b']);
});

it('ignores invalid slugs and cards that are not public', function () {
    FinancialProduct::factory()->create(['slug' => 'nhap'])->creditCard()->create();
    comparableCard('ngan-hang-ngung', institution: FinancialInstitution::factory()->create(['is_active' => false]));

    expect(compared(['nhap', 'a', 'ngan-hang-ngung', 'KHONG HOP LE', '../x', 'khong-ton-tai', 'b']))->toBe(['a', 'b']);
});

it('marks the best value only when it is distinguishable', function () {
    $rows = collect(CreditCardComparison::rows(app(FindComparableCreditCards::class)->handle(['a', 'b', 'c'])))->keyBy('label');

    // Hai thẻ cùng miễn phí: cả hai được đánh dấu.
    expect($rows['Phí thường niên']['values'])->toBe(['Miễn phí', '499.000 đ', 'Miễn phí'])
        ->and($rows['Phí thường niên']['best'])->toBe([0, 2])
        ->and($rows['Hoàn tiền tối đa']['values'][1])->toBe('—')
        ->and($rows['Hoàn tiền tối đa']['best'])->toBe([0])
        ->and($rows['Miễn lãi tối đa']['best'])->toBe([1])
        // Không ai công bố: không có "tốt nhất".
        ->and($rows['Lãi suất']['best'])->toBe([])
        ->and($rows['Ưu điểm']['values'][0])->toBe("Hoàn tiền cao\nMiễn phí");

    $equal = collect(CreditCardComparison::rows(app(FindComparableCreditCards::class)->handle(['a', 'c'])))->keyBy('label');
    expect($equal['Phí thường niên']['best'])->toBe([])
        ->and($equal['Miễn lãi tối đa']['best'])->toBe([]);
});

it('renders the comparison table, sources and remove links', function () {
    $this->get(COMPARE_URL.'?ids=b,a')
        ->assertOk()
        ->assertSee('noindex', false)
        ->assertSeeInOrder(['Thẻ B', 'Thẻ A', 'Phí thường niên', '499.000 đ', 'Miễn phí', 'Thấp nhất'])
        ->assertSee('Nguồn dữ liệu')
        ->assertSee('Thêm thẻ để so sánh')
        ->assertSee('href="'.COMPARE_URL.'?ids=a"', false)
        ->assertSee('href="'.COMPARE_URL.'?ids=b"', false)
        ->assertSee('$store.compare.replace(', false);
});

it('asks for at least two cards', function () {
    $this->get(COMPARE_URL.'?ids=a')->assertOk()->assertSee('Chọn ít nhất 2 thẻ để so sánh')->assertDontSee('Nguồn dữ liệu');
    $this->get(COMPARE_URL)->assertOk()->assertSee('Chọn ít nhất 2 thẻ để so sánh');
});

it('cleans the ids in the URL and keeps other parameters', function () {
    $this->get(COMPARE_URL.'?ids=a,a,khong-co,b,c,d&utm_source=zalo')
        ->assertRedirect(COMPARE_URL.'?utm_source=zalo&ids=a%2Cb%2Cc');
    $this->get(COMPARE_URL.'?ids[]=a&ids[]=b')->assertRedirect(COMPARE_URL);
    $this->get(COMPARE_URL.'?ids=khong-co')->assertRedirect(COMPARE_URL);
    $this->get(COMPARE_URL.'?ids=')->assertOk();
});

it('is off together with the compare module', function () {
    Feature::deactivate('compare');

    $this->get(COMPARE_URL.'?ids=a,b')->assertNotFound();
});
