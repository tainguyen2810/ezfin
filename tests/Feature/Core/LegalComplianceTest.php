<?php

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Partner\Models\OutboundLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

it('links the disclaimer page from the footer and lists it in the sitemap', function () {
    $this->get('http://ezfin.test/')->assertSee('href="http://ezfin.test/mien-tru-trach-nhiem"', false);
    $this->get('http://ezfin.test/sitemap.xml')->assertSee('http://ezfin.test/mien-tru-trach-nhiem');
    $this->get('http://ezfin.test/mien-tru-trach-nhiem')->assertOk()->assertSee('Không phải lời khuyên tài chính');
});

it('describes every kind of personal data that is collected, under the 2025 data protection law', function () {
    Feature::activate(['newsletter', 'personal']);

    $this->get('http://ezfin.test/quyen-rieng-tu')->assertOk()
        ->assertSee('91/2025/QH15')
        ->assertSee('Lượt bấm sang đối tác')
        ->assertSee('Kết quả chuyển đổi do đối tác báo lại')
        ->assertSee('Email nhận tin')
        ->assertSee('Tài khoản ứng dụng quản lý tiền')
        ->assertSee('rút lại sự đồng ý')
        ->assertDontSee('@if');

    Feature::deactivate(['newsletter', 'personal']);

    $this->get('http://ezfin.test/quyen-rieng-tu')->assertDontSee('Email nhận tin')->assertDontSee('Tài khoản ứng dụng quản lý tiền');
});

it('names the controller from the company settings only', function () {
    config(['ezfin.company.name' => 'Công ty TNHH Ví Dụ', 'ezfin.company.email' => 'dulieu@vidu.vn']);

    $this->get('http://ezfin.test/quyen-rieng-tu')->assertSee('Công ty TNHH Ví Dụ')->assertSee('mailto:dulieu@vidu.vn', false);
});

it('lists institutions with active partner links on the disclosure page', function () {
    $active = FinancialProduct::factory()->published()->create(['institution_id' => FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Có Link'])->id]);
    $inactive = FinancialProduct::factory()->published()->create(['institution_id' => FinancialInstitution::factory()->create(['short_name' => 'Ngân hàng Đã Tắt'])->id]);
    OutboundLink::factory()->create(['product_id' => $active->id]);
    OutboundLink::factory()->inactive()->create(['product_id' => $inactive->id]);

    $this->get('http://ezfin.test/cong-bo-lien-ket')->assertOk()
        ->assertSee('Ngân hàng Có Link')->assertDontSee('Ngân hàng Đã Tắt');

    Feature::deactivate('partner');
    $this->get('http://ezfin.test/cong-bo-lien-ket')->assertSee('chưa có liên kết đối tác nào');
});
