<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const TOOL_URL = 'http://ezfin.test/cong-cu/';

it('lists the five tools', function () {
    $this->get('http://ezfin.test/cong-cu')
        ->assertOk()
        ->assertSeeInOrder(['Tính lãi kép', 'Tính lãi tiền gửi tiết kiệm', 'Tính tiết kiệm định kỳ', 'Tính khoản vay', 'Tính trả góp']);
});

it('renders every tool with a chart, a table and its assumptions', function (string $slug) {
    $this->get(TOOL_URL.$slug)
        ->assertOk()
        ->assertSee('<figure', false)
        ->assertSee('<table', false)
        ->assertSee('Cách tính và giả định')
        ->assertDontSee('Số liệu chưa hợp lệ');
})->with(['lai-kep', 'lai-tien-gui', 'tiet-kiem-dinh-ky', 'khoan-vay', 'tra-gop']);

it('makes every chart column readable without JavaScript', function () {
    // 12.000.000 đ, 12%/năm, 12 tháng, dư nợ giảm dần: tháng 1 trả 1.000.000 đ gốc + 120.000 đ lãi.
    $this->get(TOOL_URL.'khoan-vay?so_tien=12000000&lai_suat=12&so_nam=1')
        ->assertSee('aria-label="Tháng 1: Tiền gốc 1.000.000 đ, Tiền lãi 120.000 đ"', false)
        ->assertSee('aria-label="Tháng 12: Tiền gốc 1.000.000 đ, Tiền lãi 10.000 đ"', false);
});

describe('deposit interest', function () {
    it('pays at the end of the term', function () {
        // 50.000.000 × 4,75% × 7 / 12 = 1.385.416,67 → 1.385.417.
        $this->get(TOOL_URL.'lai-tien-gui?so_tien=50000000&lai_suat=4.75&ky_han=7')
            ->assertOk()
            ->assertSeeInOrder(['Tiền lãi', '1.385.417 đ', 'Tổng nhận cuối kỳ', '51.385.417 đ'])
            ->assertSee('href="http://ezfin.test/lai-suat-tiet-kiem?so_tien=50000000&amp;ky_han=7"', false);
    });

    it('pays every month', function () {
        $this->get(TOOL_URL.'lai-tien-gui?linh_lai=hang-thang&lai_suat=6')
            ->assertSeeInOrder(['Tiền lãi', '6.000.000 đ', 'Lãi nhận mỗi tháng', '500.000 đ']);
    });

    it('hides the comparison link when the compare module is off', function () {
        Feature::deactivate('compare');

        $this->get(TOOL_URL.'lai-tien-gui')->assertOk()->assertDontSee('So sánh lãi suất tiết kiệm');
    });

    it('validates the inputs', function (string $query, string $message) {
        $this->get(TOOL_URL.'lai-tien-gui?'.$query)->assertSee($message)->assertDontSee('Tổng nhận cuối kỳ');
    })->with([
        ['so_tien=500000', 'Nhập số tiền gửi từ 1.000.000 đ'],
        ['ky_han=0', 'Nhập kỳ hạn từ 1 đến 60 tháng.'],
        ['lai_suat=25', 'Nhập lãi suất từ 0 đến 20%/năm'],
    ]);
});

describe('recurring savings', function () {
    it('adds interest on each monthly deposit', function () {
        // 1.000.000 đ/tháng, 6%/năm, 12 tháng: 5.000 × 78 = 390.000 đ.
        $this->get(TOOL_URL.'tiet-kiem-dinh-ky?gui_hang_thang=1000000&lai_suat=6&so_thang=12')
            ->assertSeeInOrder(['Nhận về sau 12 tháng', '12.390.000 đ', 'Tổng tiền đã gửi', '12.000.000 đ', 'Tiền lãi', '390.000 đ']);
    });

    it('keeps long charts readable by showing year ends only', function () {
        $html = $this->get(TOOL_URL.'tiet-kiem-dinh-ky?so_thang=30')->getContent();
        $long = $this->get(TOOL_URL.'tiet-kiem-dinh-ky?so_thang=60')->getContent();

        expect(substr_count($html, 'aria-label="Tháng '))->toBe(30)
            ->and(substr_count($long, 'aria-label="Tháng '))->toBe(5);
    });
});

describe('loan', function () {
    it('shows the reducing balance schedule and compares with equal payments', function () {
        // PMT(1%; 12; 12.000.000) = 1.066.185 đ, tổng lãi 794.226 đ; dư nợ giảm dần: 780.000 đ.
        $this->get(TOOL_URL.'khoan-vay?so_tien=12000000&lai_suat=12&so_nam=1')
            ->assertSeeInOrder(['Trả tháng đầu (cao nhất)', '1.120.000 đ', 'Tổng tiền lãi', '780.000 đ', 'Tổng phải trả', '12.780.000 đ'])
            ->assertSee('Nếu chọn trả đều hằng tháng: 1.066.185 đ/tháng, tổng lãi 794.226 đ (14.226 đ nhiều hơn)')
            ->assertSee('Lịch trả nợ theo tháng');
    });

    it('shows equal payments', function () {
        $this->get(TOOL_URL.'khoan-vay?so_tien=12000000&lai_suat=12&so_nam=1&cach_tra=tra-deu')
            ->assertSeeInOrder(['Trả mỗi tháng', '1.066.185 đ', 'Tổng tiền lãi', '794.226 đ'])
            ->assertSee('Nếu chọn dư nợ giảm dần: tháng đầu trả 1.120.000 đ, tổng lãi 780.000 đ (14.226 đ ít hơn)');
    });

    it('groups long loans by year with the monthly schedule folded away', function () {
        $this->get(TOOL_URL.'khoan-vay')
            ->assertSee('Lịch trả nợ theo năm')
            ->assertSee('Xem lịch trả nợ từng tháng (180 tháng)');
    });

    it('rejects a flat-rate method for loans', function () {
        $this->get(TOOL_URL.'khoan-vay?cach_tra=lai-phang')->assertSee('Chọn cách trả nợ.');
    });
});

describe('installment', function () {
    it('explains flat interest against reducing balance', function () {
        // 20.000.000 đ, trả trước 40% = 8.000.000 đ, vay 12.000.000 đ, lãi phẳng 12%/năm, 12 tháng:
        // 1.120.000 đ/tháng, lãi 1.440.000 đ, tương đương ~21,46%/năm; dư nợ giảm dần cùng 12% chỉ 780.000 đ.
        $this->get(TOOL_URL.'tra-gop?tra_truoc=40&lai_suat=12')
            ->assertSeeInOrder(['Trả mỗi tháng', '1.120.000 đ', 'Tổng tiền lãi', '1.440.000 đ', 'Tổng chi phí sản phẩm', '21.440.000 đ'])
            ->assertSeeInOrder(['Trả trước', '8.000.000 đ', 'Số tiền vay', '12.000.000 đ', 'Chênh lệch so với mua thẳng', '1.440.000 đ'])
            ->assertSee('Lãi phẳng 12%/năm tương đương khoảng <strong>21,46%/năm</strong>', false)
            ->assertSee('lãi phẳng tốn 1.440.000 đ tiền lãi, dư nợ giảm dần tốn 780.000 đ');
    });

    it('calculates on a reducing balance', function () {
        $this->get(TOOL_URL.'tra-gop?tra_truoc=40&lai_suat=12&cach_tinh=du-no-giam-dan')
            ->assertSeeInOrder(['Trả tháng đầu (cao nhất)', '1.120.000 đ', 'Tổng tiền lãi', '780.000 đ'])
            ->assertSee('dư nợ giảm dần tốn 780.000 đ');
    });

    it('handles 0% installments without an equivalent rate', function () {
        $this->get(TOOL_URL.'tra-gop?lai_suat=0')
            ->assertOk()
            ->assertSeeInOrder(['Tổng tiền lãi', '0 đ'])
            ->assertDontSee('tương đương khoảng');
    });

    it('validates the inputs', function (string $query, string $message) {
        $this->get(TOOL_URL.'tra-gop?'.$query)->assertSee($message)->assertDontSee('Tổng chi phí sản phẩm');
    })->with([
        ['tra_truoc=95', 'Nhập tỷ lệ trả trước từ 0 đến 90%'],
        ['tra_truoc=12.5', 'Nhập tỷ lệ trả trước từ 0 đến 90%'],
        ['so_thang=61', 'Nhập kỳ hạn từ 1 đến 60 tháng.'],
        ['gia=100000', 'Nhập giá sản phẩm từ 500.000 đ'],
    ]);
});
