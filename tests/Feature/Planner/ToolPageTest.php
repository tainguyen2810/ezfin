<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const TOOLS_URL = 'http://ezfin.test/cong-cu';

it('lists implemented tools only', function () {
    $this->get(TOOLS_URL)
        ->assertOk()
        ->assertSee('Công cụ tính toán tài chính')
        ->assertSee('href="'.TOOLS_URL.'/lai-kep"', false)
        ->assertDontSee('noindex', false);
});

it('shows the compound interest tool with a result for the example figures', function () {
    // 100.000.000 đ, góp 2.000.000 đ/tháng, 6%/năm ghép lãi hằng tháng, 10 năm (tính từng tháng, làm tròn đến đồng).
    $this->get(TOOLS_URL.'/lai-kep')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.TOOLS_URL.'/lai-kep">', false)
        ->assertDontSee('noindex', false)
        ->assertSee('value="100.000.000"', false)
        ->assertSeeInOrder(['Số tiền sau 10 năm', '509.698.374 đ', 'Tổng tiền bỏ vào', '340.000.000 đ', 'Tiền lãi', '169.698.374 đ'])
        ->assertSee('Cách tính và giả định')
        ->assertDontSee('Liên kết đến kết quả này');
});

it('keeps the result in a shareable URL', function () {
    // 50.000.000 đ × 1,075^5, ghép lãi hằng năm = 71.781.466 đ.
    $url = TOOLS_URL.'/lai-kep?so_tien=50000000&gop_them=0&lai_suat=7.5&so_nam=5&ghep_lai=hang-nam';

    // utm_* không đi theo liên kết chia sẻ.
    $this->get($url.'&utm_source=zalo')->assertSee('value="'.e($url).'"', false);

    $this->get($url)
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex', false)
        ->assertSee('<link rel="canonical" href="'.TOOLS_URL.'/lai-kep">', false)
        ->assertSee('value="50.000.000"', false)
        ->assertSee('value="7.5"', false)
        ->assertSee('<option value="hang-nam" selected>', false)
        ->assertSeeInOrder(['Số tiền sau 5 năm', '71.781.466 đ'])
        ->assertSee('Liên kết đến kết quả này')
        ->assertSee('value="'.e($url).'"', false)
        ->assertSee('Đặt lại');
});

it('accepts the form as submitted without JavaScript and redirects to a clean URL', function () {
    $this->get(TOOLS_URL.'/lai-kep?so_tien=50.000.000&gop_them=2.000.000&lai_suat=7,5&so_nam=5&ghep_lai=hang-thang&utm_source=zalo')
        ->assertRedirect(TOOLS_URL.'/lai-kep?utm_source=zalo&so_tien=50000000&lai_suat=7.5&so_nam=5');

    // Gửi đúng giá trị mặc định → về trang gốc.
    $this->get(TOOLS_URL.'/lai-kep?so_tien=100000000&gop_them=2000000&lai_suat=6&so_nam=10&ghep_lai=hang-thang')
        ->assertRedirect(TOOLS_URL.'/lai-kep');

    $this->get(TOOLS_URL.'/lai-kep?so_tien[]=1&so_nam[x]=2')->assertRedirect(TOOLS_URL.'/lai-kep');
});

it('shows field errors and no result for invalid figures', function (string $query, string $message) {
    $this->get(TOOLS_URL.'/lai-kep?'.$query)
        ->assertOk()
        ->assertSee($message)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('Số liệu chưa hợp lệ')
        ->assertDontSee('Tổng tiền bỏ vào')
        ->assertDontSee('Liên kết đến kết quả này');
})->with([
    'rate too high' => ['lai_suat=45', 'Nhập lãi suất từ 0 đến 30%/năm'],
    'rate not a number' => ['lai_suat=abc', 'Nhập lãi suất từ 0 đến 30%/năm'],
    'too many years' => ['so_nam=80', 'Nhập số năm từ 1 đến 50.'],
    'amount not a number' => ['so_tien=abc', 'Nhập số tiền ban đầu từ 0 đến 100 tỷ đồng.'],
    'amount too large' => ['so_tien=900000000000', 'Nhập số tiền ban đầu từ 0 đến 100 tỷ đồng.'],
    'unknown compounding' => ['ghep_lai=hang-gio', 'Chọn kỳ ghép lãi.'],
]);

it('explains when the result is beyond the calculation limit', function () {
    $this->get(TOOLS_URL.'/lai-kep?so_tien=100000000000&lai_suat=30&so_nam=50')
        ->assertOk()
        ->assertSee('Kết quả vượt quá 1 triệu tỷ đồng')
        ->assertDontSee('Tổng tiền bỏ vào');
});

it('submits money without JavaScript and hands over to the hidden field with JavaScript', function () {
    $html = $this->get(TOOLS_URL.'/lai-kep')->getContent();

    expect($html)->toContain('name="so_tien" type="text"')
        ->and($html)->toContain('x-init="$el.removeAttribute(\'name\')"')
        ->and($html)->toContain('x-bind:name="\'so_tien\'"')
        ->and($html)->not->toContain('type="hidden" name="so_tien"');
});

it('returns 404 for unknown tools', function () {
    $this->get(TOOLS_URL.'/khong-co')->assertNotFound();
    $this->get(TOOLS_URL.'/Lai-Kep')->assertNotFound();
});

it('hides tools and the menu item when the planner module is off', function () {
    $this->get(TOOLS_URL)->assertSee('href="'.TOOLS_URL.'" class', false);

    Feature::deactivate('planner');

    $this->get(TOOLS_URL)->assertNotFound();
    $this->get(TOOLS_URL.'/lai-kep')->assertNotFound();
    $this->get('http://ezfin.test/lai-suat-tiet-kiem')->assertDontSee('href="'.TOOLS_URL.'"', false);
});
