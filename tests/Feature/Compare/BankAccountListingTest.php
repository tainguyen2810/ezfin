<?php

use App\Modules\Compare\Actions\SearchBankAccounts;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\BankAccountFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const BANK_ACCOUNTS_URL = 'http://ezfin.test/tai-khoan-ngan-hang';

/** Tạo tài khoản đã xuất bản kèm biểu phí. */
function publishedAccount(string $name, array $account = [], ?FinancialInstitution $institution = null, array $product = []): FinancialProduct
{
    $model = FinancialProduct::factory()->bankAccount()->published()->create([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'institution_id' => ($institution ?? FinancialInstitution::factory()->create())->id,
        ...$product,
    ]);
    $model->bankAccount()->create($account);

    return $model;
}

/** Tên tài khoản theo đúng thứ tự trả về. */
function searchAccounts(array $query): array
{
    $filters = BankAccountFilters::fromRequest(Request::create('/', 'GET', $query));

    return app(SearchBankAccounts::class)->handle($filters)->pluck('name')->all();
}

beforeEach(function () {
    $this->alpha = FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Alpha', 'slug' => 'alpha']);
    $this->beta = FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Beta', 'slug' => 'beta']);

    publishedAccount('TK Số Miễn Phí', ['monthly_fee' => 0, 'min_balance' => 0, 'transfer_fee' => 0, 'features' => ['online_opening', 'free_transfer']], $this->alpha);
    publishedAccount('TK Truyền Thống', ['monthly_fee' => 11_000, 'min_balance' => 50_000, 'transfer_fee' => 3_300, 'features' => ['international_debit_card']], $this->alpha);
    publishedAccount('TK Ngoại Tệ', ['monthly_fee' => 20_000, 'min_balance' => null, 'transfer_fee' => null, 'features' => ['foreign_currency']], $this->beta);
    publishedAccount('TK Sinh Lời', ['monthly_fee' => 5_500, 'min_balance' => 100_000, 'transfer_fee' => 0, 'features' => ['auto_savings', 'online_opening']], $this->beta);
});

it('lists only published accounts of active banks, lowest monthly fee first', function () {
    FinancialProduct::factory()->bankAccount()->create(['name' => 'TK Nháp'])->bankAccount()->create(['monthly_fee' => 0]);
    publishedAccount('TK Ngân Hàng Ngừng', ['monthly_fee' => 0], FinancialInstitution::factory()->create(['is_active' => false]));
    // Thẻ tín dụng không lẫn vào danh sách tài khoản.
    FinancialProduct::factory()->published()->create(['name' => 'Thẻ Tín Dụng'])->creditCard()->create(['annual_fee' => 0]);

    expect(searchAccounts([]))->toBe(['TK Số Miễn Phí', 'TK Sinh Lời', 'TK Truyền Thống', 'TK Ngoại Tệ']);
});

it('filters by bank, monthly fee and feature', function (array $query, array $expected) {
    expect(searchAccounts($query))->toBe($expected);
})->with([
    'bank' => [['ngan_hang' => 'beta'], ['TK Sinh Lời', 'TK Ngoại Tệ']],
    'free' => [['phi' => 'mien-phi'], ['TK Số Miễn Phí']],
    'up to 10k' => [['phi' => 'den-10k'], ['TK Số Miễn Phí', 'TK Sinh Lời']],
    'online opening' => [['tinh_nang' => 'mo-online'], ['TK Số Miễn Phí', 'TK Sinh Lời']],
    'foreign currency' => [['tinh_nang' => 'ngoai-te'], ['TK Ngoại Tệ']],
    'combined' => [['ngan_hang' => 'beta', 'tinh_nang' => 'mo-online', 'phi' => 'den-10k'], ['TK Sinh Lời']],
]);

it('sorts with unknown values last', function (string $sort, array $expected) {
    expect(searchAccounts(['sap_xep' => $sort]))->toBe($expected);
})->with([
    'min balance' => ['so-du-thap', ['TK Số Miễn Phí', 'TK Truyền Thống', 'TK Sinh Lời', 'TK Ngoại Tệ']],
    'transfer fee' => ['chuyen-khoan-re', ['TK Sinh Lời', 'TK Số Miễn Phí', 'TK Truyền Thống', 'TK Ngoại Tệ']],
]);

it('ignores unknown or malformed filter values', function (array $query) {
    expect(searchAccounts($query))->toHaveCount(4);
})->with([
    [['phi' => 're-nhat']],
    [['tinh_nang' => 'khong-co']],
    [['sap_xep' => 'hoa-hong']],
    [['ngan_hang' => '<script>']],
    [['phi' => ['mien-phi'], 'tinh_nang' => ['mo-online']]],
]);

it('redirects to one clean URL per filter set and keeps tracking parameters', function () {
    $this->get(BANK_ACCOUNTS_URL.'?ngan_hang=&phi=&tinh_nang=mo-online&sap_xep=phi-thap')
        ->assertRedirect(BANK_ACCOUNTS_URL.'?tinh_nang=mo-online');
    $this->get(BANK_ACCOUNTS_URL.'?phi[]=mien-phi&tinh_nang[x]=1')->assertRedirect(BANK_ACCOUNTS_URL);
    $this->get(BANK_ACCOUNTS_URL.'?page=1&utm_source=zalo')->assertRedirect(BANK_ACCOUNTS_URL.'?utm_source=zalo');
    $this->get(BANK_ACCOUNTS_URL.'?tinh_nang=mo-online')->assertOk();
});

it('normalizes the URL query and counts active filters', function () {
    $filters = BankAccountFilters::fromRequest(Request::create('/', 'GET', [
        'ngan_hang' => 'alpha', 'tinh_nang' => 'ngoai-te', 'sap_xep' => 'phi-thap', 'phi' => 'sai',
    ]));

    expect($filters->toQuery())->toBe(['ngan_hang' => 'alpha', 'tinh_nang' => 'ngoai-te'])
        ->and($filters->activeCount())->toBe(2)
        ->and($filters->fee)->toBeNull();
});

it('renders filtered results with fees, source and noindex', function () {
    $this->get(BANK_ACCOUNTS_URL.'?tinh_nang=mo-online&sap_xep=chuyen-khoan-re')
        ->assertOk()
        ->assertSee('So sánh tài khoản ngân hàng')
        ->assertSeeInOrder(['TK Sinh Lời', '5.500 đ/tháng', '100.000 đ', 'TK Số Miễn Phí', 'Miễn phí', 'Không yêu cầu'])
        ->assertDontSee('TK Ngoại Tệ')
        ->assertSee('<option value="mo-online" selected>', false)
        ->assertSee('2</strong> tài khoản phù hợp', false)
        ->assertSee('Cập nhật')
        ->assertSee('noindex', false);
});

it('paginates and keeps filters in page links', function () {
    foreach (range(1, SearchBankAccounts::PER_PAGE) as $i) {
        publishedAccount("TK Thêm {$i}", ['monthly_fee' => 30_000 + $i, 'features' => ['online_opening']], $this->beta);
    }

    $this->get(BANK_ACCOUNTS_URL.'?tinh_nang=mo-online')
        ->assertOk()
        ->assertSee('tai-khoan-ngan-hang?tinh_nang=mo-online&amp;page=2', false);

    $this->get(BANK_ACCOUNTS_URL.'?tinh_nang=mo-online&page=2')->assertOk()->assertSee('TK Thêm 12');
});

it('shows an empty state with a reset link when nothing matches', function () {
    $this->get(BANK_ACCOUNTS_URL.'?phi=mien-phi&tinh_nang=ngoai-te')
        ->assertOk()
        ->assertSee('Chưa có tài khoản phù hợp')
        ->assertSee('Xóa bộ lọc');
});

it('is indexable without filters, links to details and has no fake compare control', function () {
    $this->get(BANK_ACCOUNTS_URL)
        ->assertOk()
        ->assertDontSee('noindex', false)
        ->assertDontSee('liên kết đối tác')
        ->assertDontSee('$store.compare.toggle', false)
        ->assertSee('href="'.BANK_ACCOUNTS_URL.'/tk-so-mien-phi"', false);
});

it('hides the page and menu item when the compare module is off', function () {
    $this->get(BANK_ACCOUNTS_URL)->assertSee('href="'.BANK_ACCOUNTS_URL.'"', false);

    Feature::deactivate('compare');

    $this->get(BANK_ACCOUNTS_URL)->assertNotFound();
});
