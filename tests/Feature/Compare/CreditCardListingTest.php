<?php

use App\Modules\Compare\Actions\SearchCreditCards;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\CardTier;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CreditCardFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const LISTING_URL = 'http://ezfin.test/the-tin-dung';

/** Tạo thẻ đã xuất bản kèm thông số. */
function publishedCard(string $name, array $card = [], ?FinancialInstitution $institution = null, array $product = []): FinancialProduct
{
    $model = FinancialProduct::factory()->published()->create([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'institution_id' => ($institution ?? FinancialInstitution::factory()->create())->id,
        ...$product,
    ]);
    $model->creditCard()->create($card);

    return $model;
}

/** Tên thẻ theo đúng thứ tự trả về. */
function search(array $query): array
{
    $filters = CreditCardFilters::fromRequest(Request::create('/', 'GET', $query));

    return app(SearchCreditCards::class)->handle($filters)->pluck('name')->all();
}

beforeEach(function () {
    $this->alpha = FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Alpha', 'slug' => 'alpha']);
    $this->beta = FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Beta', 'slug' => 'beta']);

    publishedCard('Thẻ Miễn Phí', ['annual_fee' => 0, 'min_monthly_income' => 5_000_000, 'cashback_rate' => 0.5, 'benefits' => ['shopping']], $this->alpha);
    publishedCard('Thẻ Hoàn Tiền', ['annual_fee' => 499_000, 'min_monthly_income' => 8_000_000, 'cashback_rate' => 5, 'benefits' => ['cashback', 'shopping']], $this->alpha);
    publishedCard('Thẻ Dặm Bay', ['annual_fee' => 1_500_000, 'min_monthly_income' => 20_000_000, 'cashback_rate' => null, 'benefits' => ['miles', 'travel']], $this->beta);
    publishedCard('Thẻ Chưa Rõ Thu Nhập', ['annual_fee' => 900_000, 'min_monthly_income' => null, 'cashback_rate' => 1, 'benefits' => ['cashback']], $this->beta);
});

it('lists only published cards of active banks, cheapest annual fee first', function () {
    FinancialProduct::factory()->create(['name' => 'Thẻ Nháp'])->creditCard()->create(['annual_fee' => 0]);
    $inactive = FinancialInstitution::factory()->create(['is_active' => false]);
    publishedCard('Thẻ Ngân Hàng Ngừng', ['annual_fee' => 0], $inactive);

    expect(search([]))->toBe(['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền', 'Thẻ Chưa Rõ Thu Nhập', 'Thẻ Dặm Bay']);
});

it('filters by bank', function () {
    expect(search(['ngan_hang' => 'beta']))->toBe(['Thẻ Chưa Rõ Thu Nhập', 'Thẻ Dặm Bay']);
});

it('filters by annual fee', function (string $fee, array $expected) {
    expect(search(['phi' => $fee]))->toBe($expected);
})->with([
    'free' => ['mien-phi', ['Thẻ Miễn Phí']],
    'up to 500k' => ['den-500k', ['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền']],
    'up to 1m' => ['den-1tr', ['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền', 'Thẻ Chưa Rõ Thu Nhập']],
]);

it('filters by need', function () {
    expect(search(['nhu_cau' => 'hoan-tien']))->toBe(['Thẻ Hoàn Tiền', 'Thẻ Chưa Rõ Thu Nhập'])
        ->and(search(['nhu_cau' => 'tich-dam']))->toBe(['Thẻ Dặm Bay']);
});

it('filters by income, keeping only cards whose minimum income is known and met', function () {
    expect(search(['thu_nhap' => '10.000.000']))->toBe(['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền'])
        ->and(search(['thu_nhap' => '8000000']))->toBe(['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền'])
        ->and(search(['thu_nhap' => '4.999.999']))->toBe([]);
});

it('combines filters', function () {
    expect(search(['ngan_hang' => 'alpha', 'nhu_cau' => 'mua-sam', 'phi' => 'mien-phi', 'thu_nhap' => '6000000']))
        ->toBe(['Thẻ Miễn Phí']);
});

it('sorts with unknown values last', function (string $sort, array $expected) {
    expect(search(['sap_xep' => $sort]))->toBe($expected);
})->with([
    'cashback' => ['hoan-tien-cao', ['Thẻ Hoàn Tiền', 'Thẻ Chưa Rõ Thu Nhập', 'Thẻ Miễn Phí', 'Thẻ Dặm Bay']],
    'income' => ['thu-nhap-thap', ['Thẻ Miễn Phí', 'Thẻ Hoàn Tiền', 'Thẻ Dặm Bay', 'Thẻ Chưa Rõ Thu Nhập']],
]);

it('ignores unknown or malformed filter values', function (array $query) {
    expect(search($query))->toHaveCount(4);
})->with([
    [['phi' => 're-nhat']],
    [['nhu_cau' => 'khong-co']],
    [['thu_nhap' => 'abc']],
    [['sap_xep' => 'hoa-hong']],
    [['ngan_hang' => '<script>']],
    [['phi' => ['mien-phi'], 'thu_nhap' => ['1'], 'nhu_cau' => ['hoan-tien']]],
]);

it('does not error on array query values', function () {
    $this->get(LISTING_URL.'?phi[]=mien-phi&sap_xep[x]=1&ngan_hang[]=alpha')->assertRedirect(LISTING_URL);
});

it('redirects submitted forms to one clean URL per filter set', function () {
    $this->get(LISTING_URL.'?ngan_hang=&phi=&nhu_cau=hoan-tien&thu_nhap=10.000.000&sap_xep=phi-thap')
        ->assertRedirect(LISTING_URL.'?nhu_cau=hoan-tien&thu_nhap=10000000');

    $this->get(LISTING_URL.'?nhu_cau=hoan-tien&thu_nhap=10000000')->assertOk();
    $this->get(LISTING_URL.'?page=1')->assertRedirect(LISTING_URL);
    $this->get(LISTING_URL.'?page=abc&utm_source=zalo')->assertRedirect(LISTING_URL.'?utm_source=zalo');
    $this->get(LISTING_URL.'?utm_source=zalo&phi=mien-phi')->assertOk();
});

it('normalizes the URL query and counts active filters', function () {
    $filters = CreditCardFilters::fromRequest(Request::create('/', 'GET', [
        'ngan_hang' => 'alpha', 'thu_nhap' => '10.000.000', 'sap_xep' => 'phi-thap', 'nhu_cau' => 'sai',
    ]));

    expect($filters->toQuery())->toBe(['ngan_hang' => 'alpha', 'thu_nhap' => 10_000_000])
        ->and($filters->activeCount())->toBe(2)
        ->and($filters->need)->toBeNull();
});

it('renders the page with filters kept in the form and pagination links', function () {
    $this->get(LISTING_URL.'?nhu_cau=hoan-tien&sap_xep=hoan-tien-cao')
        ->assertOk()
        ->assertSee('So sánh thẻ tín dụng')
        ->assertSeeInOrder(['Thẻ Hoàn Tiền', 'Thẻ Chưa Rõ Thu Nhập'])
        ->assertDontSee('Thẻ Dặm Bay')
        ->assertSee('<option value="hoan-tien" selected>', false)
        ->assertSee('<option value="hoan-tien-cao" selected>', false)
        ->assertSee('2</strong> thẻ phù hợp', false)
        ->assertSee('Cập nhật')
        ->assertSee('noindex', false);
});

it('paginates and keeps filters in page links', function () {
    foreach (range(1, SearchCreditCards::PER_PAGE) as $i) {
        publishedCard("Thẻ Thêm {$i}", ['annual_fee' => 2_000_000 + $i, 'benefits' => ['cashback']], $this->beta);
    }

    $this->get(LISTING_URL.'?nhu_cau=hoan-tien')
        ->assertOk()
        ->assertSee('the-tin-dung?nhu_cau=hoan-tien&amp;page=2', false);

    $this->get(LISTING_URL.'?nhu_cau=hoan-tien&page=2')->assertOk()->assertSee('Thẻ Thêm 12');
});

it('shows an empty state with a reset link when nothing matches', function () {
    $this->get(LISTING_URL.'?phi=mien-phi&nhu_cau=tich-dam')
        ->assertOk()
        ->assertSee('Chưa có thẻ phù hợp')
        ->assertSee('Xóa bộ lọc');
});

it('is indexable without filters, links to card details and shows no partner disclosure yet', function () {
    $this->get(LISTING_URL)
        ->assertOk()
        ->assertDontSee('noindex', false)
        ->assertDontSee('liên kết đối tác')
        ->assertSee('href="'.LISTING_URL.'/the-hoan-tien"', false);
});

it('lets visitors pick cards by slug and opens the compare page from the tray', function () {
    $this->get(LISTING_URL)
        ->assertOk()
        ->assertSee('<label x-data', false)
        ->assertSee('$store.compare.toggle({ id: \'the-hoan-tien\'', false)
        ->assertSee('http:\/\/ezfin.test\/the-tin-dung\/so-sanh', false);
});

it('hides the page and menu item when the compare module is off', function () {
    $this->get(LISTING_URL)->assertSee('href="'.LISTING_URL.'"', false);

    Feature::deactivate('compare');

    $this->get(LISTING_URL)->assertNotFound();
});

it('shows the card artwork instead of the bank logo when uploaded', function () {
    publishedCard('Thẻ Có Ảnh', ['annual_fee' => 0], $this->alpha, ['image_path' => 'products/the-co-anh.png']);

    $this->get(LISTING_URL)->assertOk()
        ->assertSee('src="/storage/products/the-co-anh.png" alt="Thẻ Có Ảnh"', false);
});

it('fills tier and network from the card name but keeps what an admin chose', function () {
    $guessed = publishedCard('Alpha Visa Platinum', [], $this->alpha)->creditCard;
    $chosen = publishedCard('Alpha JCB Gold', ['tier' => 'premium', 'network' => 'visa'], $this->alpha)->creditCard;
    $unknown = publishedCard('Alpha Express', [], $this->alpha)->creditCard;

    expect($guessed->tier)->toBe(CardTier::Platinum)
        ->and($guessed->network)->toBe(CardNetwork::Visa)
        ->and($chosen->tier)->toBe(CardTier::Premium)
        ->and($chosen->network)->toBe(CardNetwork::Visa)
        ->and($unknown->tier)->toBeNull()
        ->and(CardTier::fromName('Agribank JCB Ultimate'))->toBe(CardTier::Premium)
        ->and(CardTier::fromName('Mastercard Titanium'))->toBe(CardTier::Platinum)
        ->and(CardTier::fromName('Visa Classic'))->toBe(CardTier::Classic);
});

it('filters by card network and tier', function () {
    publishedCard('Alpha Visa Gold', [], $this->alpha);
    publishedCard('Alpha Mastercard Gold', [], $this->alpha);
    publishedCard('Beta Visa Signature', [], $this->beta);

    expect(search(['loai_the' => 'visa']))->toBe(['Alpha Visa Gold', 'Beta Visa Signature'])
        ->and(search(['hang' => 'gold']))->toBe(['Alpha Mastercard Gold', 'Alpha Visa Gold'])
        ->and(search(['loai_the' => 'visa', 'hang' => 'cao-cap']))->toBe(['Beta Visa Signature'])
        ->and(search(['loai_the' => 'khong-co', 'hang' => 'abc']))->toHaveCount(7);
});

it('finds cards by bank abbreviation or stock code', function () {
    $this->beta->update(['search_aliases' => 'VCB, Ngoại thương']);

    expect(search(['tu_khoa' => 'vcb']))->toBe(['Thẻ Chưa Rõ Thu Nhập', 'Thẻ Dặm Bay'])
        ->and(search(['tu_khoa' => 'VCB dặm']))->toBe(['Thẻ Dặm Bay'])
        ->and(search(['tu_khoa' => 'hoàn tiền']))->toBe(['Thẻ Hoàn Tiền'])
        ->and(search(['tu_khoa' => '100%_']))->toBe([]);

    $this->get(LISTING_URL.'?tu_khoa=vcb&loai_the=visa&hang=gold')->assertOk()
        ->assertSee('Loại thẻ')->assertSee('Hạng thẻ')->assertSee('value="vcb"', false);
});

it('shows cards as grid tiles that open a quick view with every fact, keeping long fee notes off the tile', function () {
    $note = 'Miễn phí năm đầu khi chi tiêu đủ điều kiện trong 30 ngày';
    FinancialProduct::where('name', 'Thẻ Hoàn Tiền')->first()->creditCard->update(['annual_fee_note' => $note, 'interest_free_days' => 55]);

    $html = $this->get(LISTING_URL)->assertOk()
        ->assertSee('sm:grid-cols-2 xl:grid-cols-3', false)
        ->assertSee('Xem nhanh')
        ->assertSee("open-modal', 'tile-the-hoan-tien'", false)
        ->assertSee('href="'.route('compare.credit-cards.show', 'the-hoan-tien').'"', false)
        ->assertSee('Xem trang chi tiết')
        ->getContent();

    // Ô chỉ có nhãn ngắn; ghi chú phí nằm trong hộp xem nhanh, hộp có đủ thông số.
    $modal = str($html)->after('id="tile-the-hoan-tien-title"')->before('role="dialog"')->toString();
    expect(substr_count($html, $note))->toBe(1)
        ->and($modal)->toContain($note)->toContain('Lãi suất')->toContain('Tổ chức thẻ')->toContain('55 ngày');
});

it('shows only facts with data on the tile, and marks unpublished facts in the quick view', function () {
    FinancialProduct::where('name', 'Thẻ Hoàn Tiền')->first()->creditCard->update([
        'annual_fee' => 499000, 'interest_rate' => 28.5, 'cashback_rate' => null, 'interest_free_days' => null, 'min_monthly_income' => null, 'issuance_fee' => 0,
    ]);

    $html = $this->get(LISTING_URL)->assertOk()->getContent();
    $tile = str($html)->after("open-modal', 'tile-the-hoan-tien'")->before('Xem nhanh')->toString();
    $modal = str($html)->after('id="tile-the-hoan-tien-title"')->before('role="dialog"')->toString();

    expect($tile)->toContain('499.000 đ')->toContain('28,5%/năm')->toContain('Phí phát hành')
        ->not->toContain('Hoàn tiền tối đa')->not->toContain('—')
        ->and($modal)->toContain('Hoàn tiền tối đa')->toContain('Chưa công bố')->toContain('28,5%/năm');
});
