<?php

use App\Modules\Compare\Actions\FindSimilarBankAccounts;
use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const BANK_ACCOUNT_DETAIL_URL = 'http://ezfin.test/tai-khoan-ngan-hang/';

/** Tài khoản đã xuất bản cho trang chi tiết. */
function detailAccount(string $slug, array $account = [], array $product = [], ?FinancialInstitution $institution = null): FinancialProduct
{
    $model = FinancialProduct::factory()->bankAccount()->published()->create([
        'name' => 'TK '.str($slug)->headline(),
        'slug' => $slug,
        'institution_id' => ($institution ?? FinancialInstitution::factory()->create())->id,
        ...$product,
    ]);
    $model->bankAccount()->create($account);

    return $model;
}

function accountJsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    return json_decode($match[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR);
}

beforeEach(function () {
    $this->bank = FinancialInstitution::factory()->create([
        'type' => InstitutionType::Bank, 'name' => 'Ngân hàng TMCP Alpha', 'short_name' => 'Alpha', 'website' => 'https://alpha.example',
    ]);
    $this->account = detailAccount('so-dep', [
        'monthly_fee' => 11_000, 'monthly_fee_note' => 'Miễn phí khi số dư bình quân từ 2.000.000 đ',
        'min_balance' => 50_000, 'transfer_fee' => 0, 'atm_withdrawal_fee' => 1_100,
        'features' => ['online_opening', 'vanity_number'],
    ], [
        'summary' => 'Tài khoản mở online, chọn số đẹp.',
        'pros' => ['Mở online trong 5 phút'], 'cons' => ['Phí số đẹp cao'],
        'source_url' => 'https://alpha.example/bieu-phi', 'source_note' => 'Biểu phí áp dụng từ 01/10/2026',
    ], $this->bank);
});

it('shows the account with fees, features, pros and cons, source and verification date', function () {
    $this->get(BANK_ACCOUNT_DETAIL_URL.'so-dep')
        ->assertOk()
        ->assertSee('<h1 class="text-2xl font-bold sm:text-3xl">TK So Dep</h1>', false)
        ->assertSee('<link rel="canonical" href="'.BANK_ACCOUNT_DETAIL_URL.'so-dep">', false)
        ->assertDontSee('noindex', false)
        ->assertSee('Mở online (eKYC)')
        ->assertSeeInOrder(['Phí quản lý tài khoản', '11.000 đ/tháng', 'Số dư tối thiểu', '50.000 đ', 'Phí chuyển khoản', 'Miễn phí', 'Phí rút tiền ATM', '1.100 đ/giao dịch'])
        ->assertSee('Miễn phí khi số dư bình quân từ 2.000.000 đ')
        ->assertSee('Mở online trong 5 phút')
        ->assertSee('Phí số đẹp cao')
        ->assertSee('Cập nhật '.$this->account->verified_at->format('d/m/Y'))
        ->assertSee('href="https://alpha.example/bieu-phi"', false)
        ->assertSee('Biểu phí áp dụng từ 01/10/2026')
        ->assertDontSee('$store.compare.toggle', false)
        ->assertDontSee('liên kết đối tác')
        ->assertDontSee('rel="sponsored', false);
});

it('outputs valid schema.org JSON-LD without ratings', function () {
    $html = $this->get(BANK_ACCOUNT_DETAIL_URL.'so-dep')->getContent();
    $schema = accountJsonLd($html);

    expect($schema['@type'])->toBe('WebPage')
        ->and($schema['dateModified'])->toBe($this->account->verified_at->toDateString())
        ->and($schema['mainEntity'])->toMatchArray([
            '@type' => 'BankAccount',
            'name' => 'TK So Dep',
            'url' => BANK_ACCOUNT_DETAIL_URL.'so-dep',
            'accountMinimumInflow' => ['@type' => 'MonetaryAmount', 'currency' => 'VND', 'value' => 50000],
            'feesAndCommissionsSpecification' => 'Phí quản lý tài khoản/tháng: 11.000 đ; Phí chuyển khoản liên ngân hàng online: Miễn phí; Phí rút tiền ATM nội mạng: 1.100 đ',
            'provider' => ['@type' => 'BankOrCreditUnion', 'name' => 'Ngân hàng TMCP Alpha', 'url' => 'https://alpha.example'],
        ])
        ->and($schema['breadcrumb']['itemListElement'][1]['item'])->toBe('http://ezfin.test/tai-khoan-ngan-hang')
        ->and($html)->not->toContain('aggregateRating');
});

it('leaves unknown fees out of the schema and shows them as a dash', function () {
    $this->account->bankAccount->update(['min_balance' => null, 'transfer_fee' => null, 'atm_withdrawal_fee' => null]);

    $response = $this->get(BANK_ACCOUNT_DETAIL_URL.'so-dep')->assertSeeInOrder(['Số dư tối thiểu', '—']);
    $entity = accountJsonLd($response->getContent())['mainEntity'];

    expect($entity)->not->toHaveKey('accountMinimumInflow')
        ->and($entity['feesAndCommissionsSpecification'])->toBe('Phí quản lý tài khoản/tháng: 11.000 đ');
});

it('suggests similar accounts by shared features, then closest monthly fee', function () {
    detailAccount('online', ['monthly_fee' => 50_000, 'features' => ['online_opening']]);
    detailAccount('online-so-dep', ['monthly_fee' => 99_000, 'features' => ['online_opening', 'vanity_number']]);
    detailAccount('ngoai-te-re', ['monthly_fee' => 10_000, 'features' => ['foreign_currency']]);
    detailAccount('ngoai-te-dat', ['monthly_fee' => 80_000, 'features' => ['foreign_currency']]);
    detailAccount('nhap', ['features' => ['online_opening']], ['status' => ProductStatus::Draft, 'verified_at' => null, 'source_url' => null]);

    expect(app(FindSimilarBankAccounts::class)->handle($this->account)->pluck('slug')->all())
        ->toBe(['online-so-dep', 'online', 'ngoai-te-re']);

    $this->get(BANK_ACCOUNT_DETAIL_URL.'so-dep')
        ->assertSee('Tài khoản tương tự')
        ->assertSeeInOrder(['TK Online So Dep', 'TK Online', 'TK Ngoai Te Re'])
        ->assertSee('href="'.BANK_ACCOUNT_DETAIL_URL.'online"', false);
});

it('returns 404 for accounts that are not public or not bank accounts', function () {
    detailAccount('nhap', product: ['status' => ProductStatus::Draft, 'verified_at' => null, 'source_url' => null]);
    detailAccount('ngan-hang-ngung', institution: FinancialInstitution::factory()->create(['is_active' => false]));
    FinancialProduct::factory()->published()->create(['slug' => 'the-tin-dung'])->creditCard()->create();

    $this->get(BANK_ACCOUNT_DETAIL_URL.'nhap')->assertNotFound();
    $this->get(BANK_ACCOUNT_DETAIL_URL.'ngan-hang-ngung')->assertNotFound();
    $this->get(BANK_ACCOUNT_DETAIL_URL.'the-tin-dung')->assertNotFound();
    $this->get(BANK_ACCOUNT_DETAIL_URL.'So-Dep')->assertNotFound();
});

it('is off together with the compare module', function () {
    Feature::deactivate('compare');

    $this->get(BANK_ACCOUNT_DETAIL_URL.'so-dep')->assertNotFound();
});
