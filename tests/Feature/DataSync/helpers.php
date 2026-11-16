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
        'techcombank.com/khach-hang-ca-nhan/vay' => Http::response($fixture('techcombank-loans.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-mua-nha' => Http::response($fixture('techcombank-loan-group.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-tieu-dung' => Http::response($fixture('techcombank-loan-group.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-mua-nha/vay-mua-nha-o' => Http::response($fixture('techcombank-loan-home.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-mua-nha/vay-nhan-chuyen-nhuong' => Http::response($fixture('techcombank-loan-transfer.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-mua-o-to' => Http::response($fixture('techcombank-loan-auto.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-tieu-dung/vay-tieu-dung-tin-chap' => Http::response($fixture('techcombank-loan-personal.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-tieu-dung/vay-xay-sua-nha' => Http::response($fixture('techcombank-loan-build.html')),
        'techcombank.com/khach-hang-ca-nhan/vay/vay-tieu-dung/mycash' => Http::response($fixture('techcombank-loan-mycash.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan' => Http::response($fixture('bidv-loans.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-nhu-cau-nha-o' => Http::response($fixture('bidv-loan-home.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-du-hoc' => Http::response($fixture('bidv-loan-other.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-tieu-dung-co-tai-san-dam-bao' => Http::response($fixture('bidv-loan-secured.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-tieu-dung-khong-tai-san-dam-bao' => Http::response($fixture('bidv-loan-unsecured.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-mua-o-to' => Http::response($fixture('bidv-loan-auto.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-cam-co' => Http::response($fixture('bidv-loan-pawn.html')),
        'bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/vay-ca-nhan/vay-sx-kinh-doanh' => Http::response($fixture('bidv-loan-business.html')),
        'acb.com.vn/api/front/v1/posts*vay-von*' => Http::response($fixture('acb-loans.json'), 200, ['Content-Type' => 'application/json']),
        'acb.com.vn/vay-von/thau-chi-tieu-dung-online' => Http::response($fixture('acb-loan-overdraft.html')),
        'acb.com.vn/vay-von/vay-mua-nha-dat' => Http::response($fixture('acb-loan-home.html')),
        'acb.com.vn/vay-von/vay-tieu-dung-the-chap' => Http::response($fixture('acb-loan-mortgage.html')),
        'acb.com.vn/vay-von/vay-mua-xe-o-to' => Http::response($fixture('acb-loan-auto.html')),
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
        'www.baca-bank.vn/SitePages/website/lai-xuat.aspx*' => fn ($request) => $request->method() === 'POST' && str_contains($request->body(), 'VND%7CVN')
            ? Http::response($fixture('bacabank-vnd.html'))
            : Http::response($fixture('bacabank-usd.html')),
        'www.namabank.com.vn/lai-suat' => Http::response($fixture('namabank-index.html')),
        'www.namabank.com.vn/lai-suat-tien-gui-vnd-hieu-luc-20102026' => Http::response($fixture('namabank-counter.html')),
        'www.namabank.com.vn/lai-suat-tiet-kiem-truc-tuyen-online-nam-hieu-luc-ngay-20102026' => Http::response($fixture('namabank-online.html')),
        'www.msb.com.vn/khach-hang-ca-nhan/lai-suat-tiet-kiem/' => Http::response($fixture('msb-rates.html')),
        'www.vib.com.vn/PwsStaticData/api/saving-rate/core' => Http::response($fixture('vib-rates.json'), 200, ['Content-Type' => 'text/plain; charset=UTF-8']),
        'media.eximbank.com.vn/exim/files/TB%20LSHD%20VND%20KHBL%20hieu%20luc%20tu%20ngay%2031102026.pdf' => Http::response($fixture('eximbank-savings.pdf'), 200, ['Content-Type' => 'application/pdf']),
    ]);
}
