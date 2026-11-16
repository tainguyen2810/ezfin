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
        'www.vietinbank.vn/vi/ca-nhan/san-pham-dich-vu/danh-sach-the?the=the_tin_dung' => Http::response($fixture('vietinbank-cards.html')),
        'www.vietinbank.vn/vi/ca-nhan/san-pham-dich-vu/danh-sach-the/vietinbank-visa-platinum-20-html' => Http::response($fixture('vietinbank-card-platinum.html')),
        'acb.com.vn/api/front/v1/posts*' => Http::response($fixture('acb-cards.json'), 200, ['Content-Type' => 'application/json']),
        'acb.com.vn/the/the-acb-visa-platinum' => Http::response($fixture('acb-card-platinum.html')),
        'www.sacombank.com.vn/ca-nhan/the/the-tin-dung/_jcr_content/*' => Http::response($fixture('sacombank-cards.json'), 200, ['Content-Type' => 'application/json']),
        'www.sacombank.com.vn/ca-nhan/the/the-tin-dung/the-tin-dung-sacombank-visa-platinum-cashback.html' => Http::response($fixture('sacombank-card-cashback.html')),
        'www.sacombank.com.vn/ca-nhan/the/the-tin-dung/the-tin-dung-sacombank-visa-infinite.html' => Http::response('<html><body><p>Phòng chờ VIP</p></body></html>'),
        'www.agribank.com.vn/vn/ca-nhan/san-pham/the/the-tin-dung-quoc-te' => Http::response($fixture('agribank-cards.html')),
        'www.agribank.com.vn/vn/ca-nhan/san-pham/the/dtl*the-agribank-visa-gold' => Http::response($fixture('agribank-card-gold.html')),
        'www.agribank.com.vn/vn/ca-nhan/san-pham/the/dtl*the-agribank-visa-standard' => Http::response($fixture('agribank-card-standard.html')),
        'sjc.com.vn/GoldPrice/Services/PriceService.ashx' => Http::response($fixture('sjc-gold.json')),
        'btmc.vn' => Http::response($fixture('btmc.html')),
        'btmc.vn/' => Http::response($fixture('btmc.html')),
        'edge-cf-api.pnj.io/ecom-frontend/v3/get-gold-price' => Http::response($fixture('pnj-gold.json'), 200, ['Content-Type' => 'application/json']),
        'bidv.com.vn/ServicesBIDV/InterestDetailServlet' => Http::response($fixture('bidv.json'), 200, ['Content-Type' => 'application/json']),
        'www.vietinbank.vn/ca-nhan/*' => Http::response($fixture('vietinbank.html')),
        'www.agribank.com.vn/vn/lai-suat-tien-gui' => Http::response($fixture('agribank-page.html')),
        'www.agribank.com.vn/wcm/connect/ttkhac/lai-suat/lai-suat-cn*' => Http::response($fixture('agribank-cn.html')),
        'www.mbbank.com.vn/mb-website/v1/interest-rate/document-categories*' => Http::response($fixture('mb-categories.json')),
        'www.mbbank.com.vn/mb-website/v1/interest-rate/documents*' => Http::response($fixture('mb-documents.json')),
        'acb.com.vn/lai-suat-tien-gui' => Http::response($fixture('acb.html')),
        'tpb.vn/cong-cu-tinh-toan/lai-suat' => Http::response($fixture('tpbank-page.html')),
        'tpb.vn/wps/wcm/connect/tpbank_data/*' => Http::response($fixture('tpbank-component.html')),
        'ocb.com.vn/vi/cong-cu/lai-suat' => Http::response($fixture('ocb.html')),
        'techcombank.com/cong-cu-tien-ich/bieu-phi-lai-suat' => Http::response($fixture('techcombank-rates.html')),
        'techcombank.com/content/dam/techcombank/public-site/documents/techcombank-bieu-lai-suat-tien-gui-tiet-kiem-tien-gui-co-ky-han-20261101.pdf' => Http::response($fixture('techcombank-savings.pdf'), 200, ['Content-Type' => 'application/pdf']),
        'techcombank.com/content/dam/techcombank/public-site/documents/vn-phu-luc-lai-suat-the-tin-dung-09-2026.pdf' => Http::response($fixture('techcombank-card-rates.pdf'), 200, ['Content-Type' => 'application/pdf']),
        'hdbank.com.vn/vi/personal/cong-cu/interest-rate' => Http::response($fixture('hdbank-rates.html')),
        'cdn.hdbank.com.vn/hdbank-file/document/20261910BIEULAISUATTIENGUIKHACHHANGCANHANKHCNWEBSITEF2TD_1792402278333.pdf' => Http::response($fixture('hdbank-savings.pdf'), 200, ['Content-Type' => 'application/pdf']),
        'eximbank.com.vn/lai-suat-tiet-kiem' => Http::response($fixture('eximbank-rates.html')),
        'media.eximbank.com.vn/exim/files/TB%20LSHD%20VND%20KHBL%20hieu%20luc%20tu%20ngay%2031102026.pdf' => Http::response($fixture('eximbank-savings.pdf'), 200, ['Content-Type' => 'application/pdf']),
    ]);
}
