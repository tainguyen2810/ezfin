# Nghiệm thu Phase 1

Tiêu chí (task 20): toàn bộ test xanh; Lighthouse mobile ≥ 90 cho trang chủ, so sánh lãi suất, công cụ lãi kép; chủ dự án duyệt trên staging.

## 1. Test tự động

- CI (GitHub Actions) chạy Pest trên PHP 8.3 và 8.4 với MySQL 8, cùng kiểm tra giao diện Playwright 360/768/1280px, ở mọi PR.
- Trên máy: `vendor/bin/pest`, `npm run build`, rồi `npm run test:ui` (cần site chạy ở `ezfin.test`).

## 2. Lighthouse mobile

Chạy trên staging (đã có HTTPS, nén gzip và HTTP/2 của nginx):

```sh
npx lighthouse https://staging.ezfin.vn/ --only-categories=performance,accessibility,best-practices,seo --view
npx lighthouse https://staging.ezfin.vn/lai-suat-tiet-kiem --only-categories=performance,accessibility,best-practices,seo --view
npx lighthouse https://staging.ezfin.vn/cong-cu/lai-kep --only-categories=performance,accessibility,best-practices,seo --view
```

Mặc định Lighthouse đo theo cấu hình mobile (máy tầm trung, mạng 4G chậm). Trên staging, điểm SEO thấp là đúng thiết kế vì `EZFIN_INDEXABLE=false` gắn `noindex`. Điểm SEO thật được đo trên production.

Kết quả đo trên máy dev ngày 29/10/2026. Máy chủ PHP có thêm proxy nén gzip để giống nginx. Dữ liệu mẫu, chưa có HTTPS.

| Trang | Hiệu năng | Trợ năng | Ghi chú |
| --- | --- | --- | --- |
| `/` | 97 | 100 | LCP 1,5 s |
| `/lai-suat-tiet-kiem` | 99 | 100 | LCP 1,7 s |
| `/cong-cu/lai-kep` | 99 | 100 | LCP 1,7 s |
| `/the-tin-dung` | 99 | 100 | |
| `/kien-thuc/...` (bài viết) | 98 | 100 | |

Best practices đạt 79 vì máy dev chạy HTTP. Trên staging có HTTPS thì mục này hết lỗi.

## 3. Chủ dự án duyệt trên staging

1. `php artisan ezfin:check` trên staging không còn mục ✗.
2. Mở trên điện thoại: trang chủ, so sánh lãi suất (đổi số tiền, kỳ hạn), thẻ tín dụng (lọc, chọn 2 đến 3 thẻ để so sánh), tài khoản ngân hàng, 5 công cụ tính, kiến thức, các trang pháp lý ở chân trang.
3. Admin (`app.staging.ezfin.vn/<đường dẫn quản trị>`): đăng nhập kèm 2FA. Thử thêm ngân hàng, sản phẩm có nguồn và ngày xác minh, nhập lãi suất từ CSV, viết và duyệt bài, tạo liên kết đối tác rồi bấm thử `/di/...`.
4. Ghi các điểm cần sửa và gửi cho Claude. Khi đạt, ghi "Đã duyệt" kèm ngày vào `TASKS.md`.
