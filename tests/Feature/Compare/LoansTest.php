<?php

use App\Models\User;
use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Filament\Resources\Loans\Pages\CreateLoan;
use App\Modules\Compare\Filament\Resources\Loans\Pages\EditLoan;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\Partner\Models\OutboundLink;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const LOANS_ADMIN_URL = 'http://ezfin.test/quan-tri/khoan-vay';

/** Khoản vay đã xuất bản; $detail ghi đè thông số mặc định (vay mua nhà, ưu đãi 12 tháng). */
function loanProduct(string $name, array $detail = [], array $product = []): FinancialProduct
{
    $loan = FinancialProduct::factory()->loan()->published()->create([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'institution_id' => $product['institution_id'] ?? FinancialInstitution::factory()->create()->id,
        ...$product,
    ]);
    $loan->loan()->create([
        'kind' => LoanKind::Home, 'promo_rate' => 6.5, 'promo_months' => 12, 'floating_note' => 'Lãi tiết kiệm 24 tháng + 3,5%',
        'min_amount' => 100_000_000, 'max_amount' => 5_000_000_000, 'max_term_months' => 300, 'max_ltv' => 70, 'prepayment_fee' => 2,
        ...$detail,
    ]);

    return $loan;
}

beforeEach(function () {
    Feature::activate('compare');
});

it('lists published loans by promo rate and hides drafts, inactive banks and other products', function () {
    loanProduct('Vay Nhà Alpha', ['promo_rate' => 7.2]);
    loanProduct('Vay Nhà Beta', ['promo_rate' => 5.9]);
    loanProduct('Vay Chưa Công Bố Lãi', ['promo_rate' => null]);
    loanProduct('Vay Nháp', product: ['status' => ProductStatus::Draft]);
    loanProduct('Vay Ngân Hàng Đóng', product: ['institution_id' => FinancialInstitution::factory()->create(['is_active' => false])->id]);
    FinancialProduct::factory()->bankAccount()->published()->create(['name' => 'Tài khoản Không Phải Vay']);

    $this->get('http://ezfin.test/vay')->assertOk()
        ->assertSeeInOrder(['Vay Nhà Beta', 'Vay Nhà Alpha', 'Vay Chưa Công Bố Lãi'])
        ->assertSee('5,9%/năm')
        ->assertSee('Lãi tiết kiệm 24 tháng + 3,5%')
        ->assertSee('Lãi ưu đãi 12 tháng đầu')
        ->assertDontSee('Vay Nháp')
        ->assertDontSee('Vay Ngân Hàng Đóng')
        ->assertDontSee('Tài khoản Không Phải Vay')
        ->assertDontSee('noindex', false);
});

it('filters by kind with an indexable landing page per kind', function () {
    loanProduct('Vay Nhà Alpha');
    loanProduct('Vay Lương Gamma', ['kind' => LoanKind::Personal, 'promo_rate' => 14, 'promo_months' => null, 'floating_note' => null, 'max_ltv' => null]);

    $this->get('http://ezfin.test/vay?loai=tieu-dung')->assertOk()
        ->assertSee('So sánh lãi suất vay tiêu dùng tín chấp')
        ->assertSee('Vay Lương Gamma')
        ->assertSee('Cố định cả kỳ hạn')
        ->assertDontSee('Vay Nhà Alpha')
        ->assertSee('<link rel="canonical" href="http://ezfin.test/vay?loai=tieu-dung"', false)
        ->assertDontSee('noindex', false);
});

it('drops loans whose published limits do not fit the amount and term, keeps unknown limits', function () {
    loanProduct('Vay Hạn Mức Thấp', ['max_amount' => 500_000_000]);
    loanProduct('Vay Thời Hạn Ngắn', ['max_term_months' => 120]);
    loanProduct('Vay Chưa Công Bố Hạn Mức', ['max_amount' => null, 'max_term_months' => null]);
    loanProduct('Vay Phù Hợp', ['promo_rate' => 6]);

    $this->get('http://ezfin.test/vay?so_tien=1200000000&thoi_han=240')->assertOk()
        ->assertSee('Vay Phù Hợp')
        ->assertSee('Vay Chưa Công Bố Hạn Mức')
        ->assertDontSee('Vay Hạn Mức Thấp')
        ->assertDontSee('Vay Thời Hạn Ngắn')
        // Dư nợ giảm dần: gốc 1,2 tỷ / 240 = 5.000.000 đ, lãi tháng đầu 1,2 tỷ × 6% / 12 = 6.000.000 đ.
        ->assertSee('Trả tháng đầu (ước tính)')
        ->assertSee('11.000.000 đ')
        ->assertSee('Xem lịch trả nợ chi tiết')
        ->assertSee('noindex', false);
});

it('normalises the query string', function () {
    $this->get('http://ezfin.test/vay?loai=khong-co&thoi_han=7&so_tien=1.000.000.000&sap_xep=lai-thap&utm_source=x')
        ->assertRedirect('http://ezfin.test/vay?utm_source=x&so_tien=1000000000');
    $this->get('http://ezfin.test/vay?so_tien[]=1')->assertRedirect('http://ezfin.test/vay');
});

it('shows a loan page with rates, fees, after-promo terms and JSON-LD', function () {
    $loan = loanProduct('Vay Nhà Alpha', ['processing_fee' => 0, 'prepayment_note' => '2% trong 3 năm đầu']);

    $response = $this->get('http://ezfin.test/vay/vay-nha-alpha')->assertOk()
        ->assertSee('Vay mua nhà')
        ->assertSee('6,5%/năm')
        ->assertSee('5.000.000.000 đ')
        ->assertSee('25 năm')
        ->assertSee('70% giá trị tài sản')
        ->assertSee('Miễn phí')
        ->assertSee('2% trong 3 năm đầu')
        ->assertSee('Lãi ưu đãi chỉ áp dụng 12 tháng đầu');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $match);
    $schema = json_decode($match[1], true);
    expect($schema['mainEntity']['@type'])->toBe('LoanOrCredit')
        ->and($schema['mainEntity']['annualPercentageRate'])->toEqual(6.5)
        ->and($schema['mainEntity']['loanTerm']['maxValue'])->toBe(300)
        ->and($schema['mainEntity']['amount']['maxValue'])->toEqual(5_000_000_000);

    $this->get('http://ezfin.test/vay/khong-ton-tai')->assertNotFound();
    $loan->update(['status' => ProductStatus::Draft]);
    $this->get('http://ezfin.test/vay/vay-nha-alpha')->assertNotFound();
});

it('shows the partner button with disclosure only for loans with an active link', function () {
    $loan = loanProduct('Vay Nhà Alpha');
    loanProduct('Vay Nhà Beta');
    $link = OutboundLink::factory()->create(['product_id' => $loan->id, 'cta_label' => 'Đăng ký vay']);

    $this->get('http://ezfin.test/vay')->assertOk()
        ->assertSee('href="'.$link->url().'"', false)
        ->assertSee('rel="sponsored nofollow noopener"', false)
        ->assertSee('liên kết đối tác');
    $this->get('http://ezfin.test/vay/vay-nha-alpha')->assertSee('href="'.$link->url().'"', false)->assertSee('liên kết đối tác');
});

it('lists loan pages in the sitemap and the bank page, and hides them when the module is off', function () {
    $loan = loanProduct('Vay Nhà Alpha');

    $this->get('http://ezfin.test/sitemap.xml')->assertOk()
        ->assertSee('http://ezfin.test/vay</loc>', false)
        ->assertSee('http://ezfin.test/vay?loai=mua-nha', false)
        ->assertDontSee('loai=tieu-dung', false)
        ->assertSee('http://ezfin.test/vay/vay-nha-alpha', false);
    $this->get(route('compare.institutions.show', $loan->institution->slug))->assertOk()->assertSee('Khoản vay')->assertSee('Vay Nhà Alpha');

    Feature::deactivate('compare');
    $this->get('http://ezfin.test/vay')->assertNotFound();
    $this->get('http://ezfin.test/vay/vay-nha-alpha')->assertNotFound();
});

it('redirects the old URL when a published loan changes slug', function () {
    $loan = loanProduct('Vay Nhà Alpha');
    $loan->update(['slug' => 'vay-nha-alpha-moi']);

    $this->get('http://ezfin.test/vay/vay-nha-alpha')->assertStatus(301)->assertRedirect('http://ezfin.test/vay/vay-nha-alpha-moi');
});

it('lets data operators manage loans in the admin', function (Role $role, int $status) {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin($role)->create());

    $this->get(LOANS_ADMIN_URL.'/create')->assertStatus($status);
})->with([
    'super admin' => [Role::SuperAdmin, 200],
    'data operator' => [Role::DataOperator, 200],
    'editor' => [Role::Editor, 403],
]);

it('creates and edits a loan with exact values', function () {
    Filament::setCurrentPanel('admin');
    $this->actingAs(User::factory()->admin(Role::DataOperator)->create());

    Livewire::test(CreateLoan::class)
        ->fillForm([
            'institution_id' => FinancialInstitution::factory()->create()->id,
            'name' => 'Vay Mua Nhà Thử', 'slug' => 'vay-mua-nha-thu', 'status' => ProductStatus::Draft->value,
            'loan' => [
                'kind' => LoanKind::Home->value, 'promo_rate' => '6.25', 'promo_months' => '24', 'floating_margin' => '3.5',
                'min_amount' => '100.000.000', 'max_amount' => '10.000.000.000', 'max_term_months' => '360', 'max_ltv' => '75',
                'prepayment_fee' => '0',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = FinancialProduct::with('loan')->sole();
    expect($product->type)->toBe(ProductType::Loan)
        ->and($product->loan->kind)->toBe(LoanKind::Home)
        ->and($product->loan->promo_rate)->toBe('6.2500')
        ->and($product->loan->promo_months)->toBe(24)
        ->and($product->loan->max_amount)->toBe('10000000000.00')
        ->and($product->loan->prepayment_fee)->toBe('0.0000');

    Livewire::test(EditLoan::class, ['record' => $product->getRouteKey()])
        ->fillForm(['loan' => ['promo_rate' => '5.9', 'kind' => LoanKind::Home->value]])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($product->loan()->first()->promo_rate)->toBe('5.9000');

    Livewire::test(CreateLoan::class)
        ->fillForm(['institution_id' => FinancialInstitution::factory()->create()->id, 'name' => 'Thiếu Lãi', 'slug' => 'thieu-lai',
            'status' => ProductStatus::Draft->value, 'loan' => ['kind' => LoanKind::Home->value, 'promo_rate' => null]])
        ->call('create')
        ->assertHasFormErrors(['loan.promo_rate' => 'required']);
});
