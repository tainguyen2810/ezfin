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
        'www.vietcombank.com.vn/vi-VN/KHCN/Cong-cu-tien-ich/Ty-gia' => Http::response($fixture('vietcombank-fx.html')),
        'www.vietcombank.com.vn/vi-VN/sxa/searchapi/customresults/*5BFF185D*' => Http::response($fixture('vietcombank-cards.json'), 200, ['Content-Type' => 'application/json']),
        'www.vietcombank.com.vn/vi-VN/sxa/searchapi/customresults/*BD096303*' => Http::response($fixture('vietcombank-loans.json'), 200, ['Content-Type' => 'application/json']),
        'www.vietcombank.com.vn/vi-VN/KHCN/SPDV/The/Credit_VCB-Vibe-Platinum' => Http::response($fixture('vietcombank-card-vibe.html')),
        'www.vietcombank.com.vn/vi-VN/KHCN/SPDV/The/Credit_VCB-VNAirlines-Amex' => Http::response($fixture('vietcombank-card-vna.html')),
        'www.vietcombank.com.vn/vi-VN/KHCN/Cong-cu-tien-ich/KHCN---Lai-suat' => Http::response($fixture('vietcombank.html')),
        'techcombank.com/khach-hang-ca-nhan/chi-tieu/the/the-tin-dung' => Http::response($fixture('techcombank-cards.html')),
        'techcombank.com/khach-hang-ca-nhan/chi-tieu/the/the-tin-dung/techcombank-spark' => Http::response($fixture('techcombank-card-spark.html')),
        'techcombank.com/khach-hang-ca-nhan/chi-tieu/the/the-tin-dung/vietnam-airlines-techcombank-visa-platinum' => Http::response($fixture('techcombank-card-vna.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/dich-vu-the/the-tin-dung-quoc-te' => Http::response($fixture('bidv-cards.html')),
        'bidv.com.vn/bidv/ca-nhan/san-pham-dich-vu/dich-vu-the/the-tin-dung-quoc-te/the-bidv-visa-infinite' => Http::response($fixture('bidv-card-infinite.html')),
        'bidv.com.vn/bidv/ca-nhan/san-pham-dich-vu/dich-vu-the/the-tin-dung-quoc-te/2/the-bidv-visa-cashback-online' => Http::response($fixture('bidv-card-cashback.html')),
        'sjc.com.vn/GoldPrice/Services/PriceService.ashx' => Http::response($fixture('sjc-gold.json')),
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
