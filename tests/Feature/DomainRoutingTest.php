<?php

use App\Modules\Core\CoreServiceProvider;

it('moves every old app subdomain URL to the main site permanently', function (string $old, string $new) {
    $this->get('http://app.ezfin.test'.$old)
        ->assertRedirect('http://ezfin.test'.$new)
        ->assertStatus(301)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'trang đầu' => ['/', '/ung-dung'],
    'màn hình ứng dụng' => ['/giao-dich?thang=2026-11', '/ung-dung/giao-dich?thang=2026-11'],
    'đăng nhập' => ['/dang-nhap', '/dang-nhap'],
    'đặt lại mật khẩu' => ['/dat-lai-mat-khau/abc?email=a%40b.vn', '/dat-lai-mat-khau/abc?email=a%40b.vn'],
    'quản trị' => ['/quan-tri/tiet-kiem', '/quan-tri/tiet-kiem'],
]);

it('keeps the old app subdomain out of search engines', function () {
    $this->get('http://app.ezfin.test/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
});

it('needs no old subdomain when none is configured', function () {
    config(['ezfin.domains.legacy_app' => null]);

    expect(CoreServiceProvider::publicUrlFor('vi'))->toBe('http://ezfin.test/ung-dung/vi');
});

it('sends the app root to the dashboard, or to login for guests', function () {
    $this->get('http://ezfin.test/ung-dung')->assertRedirect('http://ezfin.test/ung-dung/tong-quan');
    $this->get('http://ezfin.test/ung-dung/tong-quan')->assertRedirect(route('personal.login'));
});
