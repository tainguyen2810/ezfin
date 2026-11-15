<?php

use App\Models\User;
use App\Modules\Compare\Enums\CardNetwork;
use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Enums\Role;
use App\Modules\DataSync\Actions\ApproveDraft;
use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\SourceRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

const DRAFTS_ADMIN_URL = 'http://ezfin.test/quan-tri/cho-duyet';

function cardPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
}

beforeEach(function () {
    Carbon::setTestNow('2026-11-17 05:30:00');
    config(['ezfin.data_sync.delay_ms' => 0]);
    Filament::setCurrentPanel('admin');
    Feature::activate('data_sync');
    Storage::fake('public');
    Notification::fake();
    fakeBankSites(['www.vietcombank.com.vn/-/media/*' => Http::response(cardPng(), 200, ['Content-Type' => 'image/png'])]);
    app(SourceRegistry::class)->sync();
});

afterEach(fn () => Carbon::setTestNow());

it('reads money, terms and kinds the way Vietnamese bank pages write them', function () {
    expect(CatalogParsing::money('800.000 VND/thẻ/năm'))->toBe(800000)
        ->and(CatalogParsing::money('02 tỷ VND'))->toBe(2000000000)
        ->and(CatalogParsing::money('1,5 tỷ đồng'))->toBe(1500000000)
        ->and(CatalogParsing::money('500 triệu'))->toBe(500000000)
        ->and(CatalogParsing::money('Miễn phí'))->toBe(0)
        ->and(CatalogParsing::money('Linh hoạt'))->toBeNull()
        ->and(CatalogParsing::months('30 năm'))->toBe(360)
        ->and(CatalogParsing::months('06 năm'))->toBe(72)
        ->and(CatalogParsing::months('84 tháng'))->toBe(84)
        ->and(CatalogParsing::percent('70% giá trị căn nhà'))->toBe('70.0000')
        ->and(CatalogParsing::loanKind('Vay mua ô tô'))->toBe('auto')
        ->and(CatalogParsing::loanKind('Vay xây sửa nhà ở'))->toBe('home')
        ->and(CatalogParsing::loanKind('Vay tín chấp theo lương'))->toBe('personal')
        ->and(CatalogParsing::loanKind('An tâm kinh doanh'))->toBeNull()
        ->and(CatalogParsing::network('Vietcombank Cashplus Platinum American Express®'))->toBe('amex')
        ->and(CatalogParsing::imageUrl('/-/media/a b.png?h=32&amp;w=32', 'https://x.vn', original: true))->toBe('https://x.vn/-/media/a%20b.png')
        ->and(CatalogParsing::imageUrl('/icon.svg', 'https://x.vn'))->toBeNull()
        ->and(CatalogParsing::cardTerms('Ưu đãi phí thường niên (miễn phí năm đầu khi chi tiêu từ 2 triệu đồng)')['annual_fee'])->toBeNull()
        ->and(CatalogParsing::cardTerms('Phí thường niên: 1.200.000 VND')['annual_fee'])->toBe(1200000);
});

it('drafts Vietcombank credit cards with fees from each card page, and approval publishes them with the bank image', function () {
    $operator = User::factory()->admin(Role::DataOperator)->create();
    $result = app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.vietcombank')->sole());

    expect($result['status'])->toBe('new');
    $items = collect($result['draft']->payload['items'])->keyBy('key');
    expect($items)->toHaveCount(2)
        ->and($items['credit_vcb-vibe-platinum']['details'])->toMatchArray(['annual_fee' => 800000, 'issuance_fee' => 0, 'interest_free_days' => 50, 'network' => null])
        ->and($items['credit_vcb-vibe-platinum']['details']['benefits'])->toContain('travel', 'installment')
        ->and($items['credit_vcb-vibe-platinum']['image_url'])->toBe('https://www.vietcombank.com.vn/-/media/Project/VCB-Sites/VCB/KHCN/San-pham-Dich-vu/The/Chi-tiet-the/Destop/Card-Vibe-Platinum.png')
        ->and($items['credit_vcb-vnairlines-amex']['details'])->toMatchArray(['annual_fee' => 400000, 'network' => 'amex'])
        ->and($items['credit_vcb-vnairlines-amex']['details']['benefits'])->toContain('miles')
        ->and($items['credit_vcb-vnairlines-amex']['image_url'])->toEndWith('Card%20VNA%20Amex.png');

    $this->actingAs($operator)->get(DRAFTS_ADMIN_URL.'/'.$result['draft']->id)->assertOk()
        ->assertSee('Vietcombank Vibe Platinum')->assertSee('Phí thường niên')->assertSee('800.000 đ');

    expect(app(ApproveDraft::class)->handle($result['draft'], $operator))->toContain('Đã tạo 2');

    $card = FinancialProduct::where('name', 'Vietcombank Vietnam Airlines American Express®')->sole();
    expect($card->type)->toBe(ProductType::CreditCard)
        ->and($card->status)->toBe(ProductStatus::Published)
        ->and($card->source_url)->toBe('https://www.vietcombank.com.vn/vi-VN/KHCN/SPDV/The/Credit_VCB-VNAirlines-Amex')
        ->and($card->verified_at->toDateString())->toBe('2026-11-17')
        ->and($card->institution->slug)->toBe('vietcombank')
        ->and($card->creditCard->network)->toBe(CardNetwork::Amex)
        ->and((int) $card->creditCard->annual_fee)->toBe(400000)
        ->and($card->image_path)->toStartWith('products/vietcombank-credit-vcb-vnairlines-amex-');
    Storage::disk('public')->assertExists($card->image_path);
});

it('updates existing products without erasing data the admin entered, and warns about fee changes and removed cards', function () {
    $operator = User::factory()->admin(Role::DataOperator)->create();
    $source = SyncSource::where('key', 'credit_card.vietcombank')->sole();
    app(ApproveDraft::class)->handle(app(RunSource::class)->handle($source)['draft'], $operator);

    $card = FinancialProduct::where('name', 'Vietcombank Vibe Platinum')->sole();
    $card->update(['name' => 'VCB Vibe Platinum', 'status' => ProductStatus::Draft]);
    $card->creditCard->update(['interest_rate' => '27.0000', 'min_monthly_income' => '10000000.00']);

    $cards = json_decode(file_get_contents(base_path('tests/Fixtures/DataSync/vietcombank-cards.json')), true);
    array_pop($cards['Results']);
    $vibe = str_replace('800.000', '900.000', file_get_contents(base_path('tests/Fixtures/DataSync/vietcombank-card-vibe.html')));
    fakeBankSites([
        'www.vietcombank.com.vn/vi-VN/sxa/searchapi/customresults/*5BFF185D*' => Http::response($cards),
        'www.vietcombank.com.vn/vi-VN/KHCN/SPDV/The/Credit_VCB-Vibe-Platinum' => Http::response($vibe),
    ]);

    $draft = app(RunSource::class)->handle($source->fresh())['draft'];
    expect($draft->warnings)->toHaveCount(2)
        ->and($draft->warnings[0])->toContain('không còn trên trang')
        ->and($draft->warnings[1])->toContain('800000')->toContain('900000');

    app(ApproveDraft::class)->handle($draft, $operator);
    $card->refresh();
    expect($card->name)->toBe('VCB Vibe Platinum')
        ->and($card->status)->toBe(ProductStatus::Draft)
        ->and((int) $card->creditCard->annual_fee)->toBe(900000)
        ->and($card->creditCard->interest_rate)->toBe('27.0000')
        ->and((int) $card->creditCard->min_monthly_income)->toBe(10000000)
        ->and(FinancialProduct::where('type', ProductType::CreditCard)->count())->toBe(2);
});

it('drafts Vietcombank personal, home and car loans, skipping business loans', function () {
    $result = app(RunSource::class)->handle(SyncSource::where('key', 'loan.vietcombank')->sole());
    $items = collect($result['draft']->payload['items'])->keyBy('key');

    expect($items->keys()->all())->toBe(['nha-moi-thanh-dat', 'vay-mua--oto', 'vay-tieu-dung-co-tsbd', 'vay-tin-chap-doi-voi-nguoi-lao-dong'])
        ->and($items['vay-mua--oto']['details'])->toBe(['kind' => 'auto', 'max_ltv' => '100.0000', 'max_amount' => null, 'max_term_months' => 96])
        ->and($items['nha-moi-thanh-dat']['details'])->toMatchArray(['kind' => 'home', 'max_ltv' => '70.0000', 'max_term_months' => 480])
        ->and($items['vay-tieu-dung-co-tsbd']['details'])->toMatchArray(['kind' => 'personal', 'max_amount' => 2000000000, 'max_term_months' => 120])
        ->and($items['vay-tin-chap-doi-voi-nguoi-lao-dong']['details'])->toMatchArray(['kind' => 'personal', 'max_amount' => null, 'max_term_months' => 84]);

    app(ApproveDraft::class)->handle($result['draft'], User::factory()->admin(Role::DataOperator)->create());

    $loan = FinancialProduct::where('name', 'Vay mua ô tô')->sole();
    expect($loan->loan->kind)->toBe(LoanKind::Auto)
        ->and($loan->loan->max_term_months)->toBe(96)
        ->and($loan->loan->promo_rate)->toBeNull()
        ->and(FinancialInstitution::count())->toBe(1);
    $this->get('/vay')->assertOk()->assertSee('Vay mua ô tô');
});

it('reads Techcombank and BIDV cards from their listing pages and the fee wording on each card page', function () {
    $tcb = collect(app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.techcombank')->sole())['draft']->payload['items'])->keyBy('key');
    $bidv = collect(app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.bidv')->sole())['draft']->payload['items'])->keyBy('key');

    // Thẻ trên tên miền khác (Techcom Wealth) bỏ qua; chữ trong script, đầu/chân trang không tính.
    expect($tcb->keys()->all())->toBe(['techcombank-spark', 'vietnam-airlines-techcombank-visa-platinum'])
        ->and($tcb['techcombank-spark']['name'])->toBe('Techcombank Spark')
        ->and($tcb['techcombank-spark']['image_url'])->toBe('https://techcombank.com/content/dam/techcombank/public-site/image-card/img-tcb-spark-credit-card.jpg.rendition/cq5dam.web.768.768.webp')
        ->and($tcb['techcombank-spark']['details'])->toMatchArray(['annual_fee' => 699000, 'interest_free_days' => null, 'network' => null])
        ->and($tcb['techcombank-spark']['details']['benefits'])->toContain('travel')
        ->and($tcb['vietnam-airlines-techcombank-visa-platinum']['details'])->toMatchArray([
            'network' => 'visa', 'annual_fee' => 990000, 'interest_free_days' => 55, 'interest_rate' => '31.8000', 'min_monthly_income' => 15000000,
        ])
        ->and($tcb['vietnam-airlines-techcombank-visa-platinum']['details']['benefits'])->toContain('miles');

    expect($bidv['the-bidv-visa-infinite']['name'])->toBe('BIDV Visa Infinite')
        ->and($bidv['the-bidv-visa-infinite']['image_url'])->toBe('https://bidv.com.vn/wps/wcm/connect/5b87bf1e-b14a-4e6d-b0e3-4f5f8ba4fc00/Infinite+01_111x176.png?MOD=AJPERES&CACHEID=ROOTWORKSPACE-5b87bf1e-b14a-4e6d-b0e3-4f5f8ba4fc00-q2A1MFv')
        ->and($bidv['the-bidv-visa-infinite']['details'])->toMatchArray(['annual_fee' => 30000000, 'issuance_fee' => 0, 'interest_free_days' => 45])
        ->and($bidv['the-bidv-visa-infinite']['details']['annual_fee_note'])->toContain('15.000.000')
        ->and($bidv['the-bidv-visa-cashback-online']['details'])->toMatchArray(['annual_fee' => 1000000, 'interest_free_days' => 45])
        ->and($bidv['the-bidv-visa-cashback-online']['details']['benefits'])->toContain('cashback', 'shopping');
});

it('lists VietinBank cards without inventing a fee the card page does not state, skipping discontinued cards', function () {
    $items = app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.vietinbank')->sole())['draft']->payload['items'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('VietinBank Visa Platinum')
        ->and($items[0]['image_url'])->toBe('https://vietinbank.vn/assets/4a042b7e-59f0-4b8a-9279-ac3a721148eb')
        ->and($items[0]['details'])->toMatchArray(['annual_fee' => null, 'issuance_fee' => 0, 'network' => 'visa']);
});

it('reads ACB credit cards from the website content API, skipping debit and business cards', function () {
    $items = app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.acb')->sole())['draft']->payload['items'];

    expect($items)->toHaveCount(1)
        ->and($items[0])->toMatchArray(['key' => 'the-acb-visa-platinum', 'name' => 'ACB Visa Platinum', 'url' => 'https://acb.com.vn/the/the-acb-visa-platinum'])
        ->and($items[0]['summary'])->toStartWith('Ưu đãi mỗi ngày, tiện ích mọi chi tiêu; Tích 5%')
        ->and($items[0]['details'])->toMatchArray(['annual_fee' => 899000, 'interest_free_days' => 55, 'network' => 'visa'])
        ->and($items[0]['details']['benefits'])->toContain('shopping', 'dining');
});

it('reads Sacombank cards with the annual fee from the card listing data', function () {
    $items = collect(app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.sacombank')->sole())['draft']->payload['items'])->keyBy('key');

    expect($items['the-tin-dung-sacombank-visa-infinite'])->toMatchArray([
        'name' => 'Sacombank Visa Infinite',
        'url' => 'https://www.sacombank.com.vn/ca-nhan/the/the-tin-dung/the-tin-dung-sacombank-visa-infinite.html',
        'image_url' => 'https://www.sacombank.com.vn/content/dam/sacombank/images/the-new/the-tin-dung/Visa%20Infinite_contactless-01.png',
    ])
        ->and($items['the-tin-dung-sacombank-visa-infinite']['details'])->toMatchArray(['annual_fee' => 29999000, 'network' => 'visa'])
        ->and($items['the-tin-dung-sacombank-visa-infinite']['details']['benefits'])->toContain('travel')
        ->and($items['the-tin-dung-sacombank-visa-platinum-cashback']['details']['annual_fee'])->toBe(599000)
        ->and($items['the-tin-dung-sacombank-visa-platinum-cashback']['details']['benefits'])->toContain('cashback', 'shopping');
});

it('fails with an alert when the card listing comes back empty', function () {
    fakeBankSites(['www.vietcombank.com.vn/vi-VN/sxa/searchapi/customresults/*' => Http::response(['Results' => []])]);

    $result = app(RunSource::class)->handle(SyncSource::where('key', 'credit_card.vietcombank')->sole());

    expect($result['status'])->toBe('failed')->and($result['message'])->toContain('không đọc được sản phẩm');
});
