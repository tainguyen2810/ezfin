<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/** Giả lập website các ngân hàng bằng file mẫu chụp từ trang thật (rút gọn). $override: mẫu URL => phản hồi, ưu tiên hơn. */
function fakeBankSites(array $override = []): void
{
    $fixture = fn (string $name) => file_get_contents(base_path('tests/Fixtures/DataSync/'.$name));

    // Mỗi lần gọi thay toàn bộ bảng giả lập (Http::fake cộng dồn, mẫu đăng ký trước sẽ khớp trước).
    Http::swap(new Factory(app('events')));
    Http::preventStrayRequests();
    Http::fake($override + [
        '*/robots.txt' => Http::response('', 404),
        'www.vietcombank.com.vn/vi-VN/KHCN/*' => Http::response($fixture('vietcombank.html')),
        'bidv.com.vn/ServicesBIDV/InterestDetailServlet' => Http::response($fixture('bidv.json'), 200, ['Content-Type' => 'application/json']),
        'www.vietinbank.vn/ca-nhan/*' => Http::response($fixture('vietinbank.html')),
        'www.agribank.com.vn/vn/lai-suat-tien-gui' => Http::response($fixture('agribank-page.html')),
        'www.agribank.com.vn/wcm/connect/ttkhac/lai-suat/lai-suat-cn*' => Http::response($fixture('agribank-cn.html')),
        'www.mbbank.com.vn/mb-website/v1/interest-rate/document-categories*' => Http::response($fixture('mb-categories.json')),
        'www.mbbank.com.vn/mb-website/v1/interest-rate/documents*' => Http::response($fixture('mb-documents.json')),
        'acb.com.vn/lai-suat-tien-gui' => Http::response($fixture('acb.html')),
        'tpb.vn/cong-cu-tinh-toan/lai-suat' => Http::response($fixture('tpbank-page.html')),
        'tpb.vn/wps/wcm/connect/tpbank_data/*' => Http::response($fixture('tpbank-component.html')),
    ]);
}
