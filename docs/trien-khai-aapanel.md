# Triển khai, sao lưu và nâng cấp trên aaPanel

Môi trường: Ubuntu Server + aaPanel, Apache hoặc Nginx, PHP 8.3, MySQL 8. Đường dẫn ví dụ: `/www/wwwroot/ezfin`, PHP CLI `/www/server/php/83/bin/php`.

## 1. Chuẩn bị máy chủ

- App Store của aaPanel: cài PHP 8.3, MySQL 8, Supervisor Manager.
- PHP 8.3 → Install extensions: `fileinfo`, `intl`, `opcache` (thêm `redis` nếu sau này dùng Redis).
- PHP 8.3 → Disabled functions: bỏ chặn `putenv`, `proc_open`, `proc_get_status` (Composer và scheduler cần).

## 2. Tạo website

1. Website → Add site `ezfin.vn`, thêm domain `www.ezfin.vn`, `app.ezfin.vn` vào cùng site. PHP 8.3. Tạo database `ezfin` (utf8mb4).
2. Clone mã nguồn vào `/www/wwwroot/ezfin`, chủ sở hữu `www`.
3. Site settings → Site directory: chọn **Running directory** = `/public`. Tắt "Anti-XSS attack (open_basedir)" hoặc đặt open_basedir bao cả `/www/wwwroot/ezfin/`.
4. URL rewrite: chọn mẫu `laravel5`.
5. SSL: Let's Encrypt cho `ezfin.vn`, `www.ezfin.vn`, `app.ezfin.vn`, bật Force HTTPS.

## 3. Cài đặt lần đầu

```sh
cd /www/wwwroot/ezfin
cp .env.example .env    # sửa APP_ENV=production, APP_DEBUG=false, APP_URL=https://ezfin.vn, DB_*, MAIL_*
                        # EZFIN_PUBLIC_DOMAIN=ezfin.vn, EZFIN_APP_DOMAIN=app.ezfin.vn,
                        # EZFIN_ADMIN_PATH=<chuỗi khó đoán>, SESSION_DOMAIN=.ezfin.vn, SESSION_SECURE_COOKIE=true
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
npm ci && npm run build   # hoặc build trên máy khác rồi upload thư mục public/build
php artisan storage:link
php artisan optimize
chown -R www:www storage bootstrap/cache
```

Email qua Brevo: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, tài khoản SMTP lấy trong Brevo. Không commit `.env`.

## 4. Cron và queue

- Cron → Shell Script, chạy mỗi phút:
  `cd /www/wwwroot/ezfin && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1`
- Supervisor Manager → Add daemon, user `www`, thư mục `/www/wwwroot/ezfin`:
  `/www/server/php/83/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600`

## 5. Cập nhật phiên bản (deploy)

```sh
cd /www/wwwroot/ezfin
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

Luôn sao lưu database trước bước `migrate`. Gắn tag git cho mỗi lần phát hành (`v1.0.0`...) để có điểm quay lại.

## 6. Sao lưu và khôi phục

- Cron → Backup Database: hằng ngày 02:00, giữ 14 bản. Cron → Backup Site hoặc script nén `storage/app` hằng ngày.
- Nên đẩy bản sao lưu ra ngoài máy chủ (aaPanel hỗ trợ lưu trữ ngoài).
- Khôi phục: `php artisan down` → import file `.sql` vào database (phpMyAdmin hoặc `mysql ezfin < backup.sql`) → giải nén `storage/app` → `php artisan optimize` → `php artisan up`.
- Ít nhất mỗi quý khôi phục thử vào database staging để chắc bản sao lưu dùng được.

## 7. Nâng cấp

Nguyên tắc: nâng cấp trên nhánh riêng → CI xanh → staging → sao lưu → production. Không sửa file trong `vendor/`.

**Bản vá Laravel/thư viện (13.x):** `composer update` trên nhánh mới, chạy test, deploy như mục 5.

**Nâng PHP 8.3 → 8.4 (hoặc mới hơn):**
1. CI đã chạy test trên PHP 8.4 ở mọi PR, nên mã nguồn sẵn sàng.
2. Đổi `config.platform.php` trong `composer.json` thành `8.4.0`, chạy `composer update`, mở PR.
3. aaPanel: cài PHP 8.4 kèm extension như mục 1, chuyển site sang PHP 8.4.
4. Đổi đường dẫn PHP CLI trong cron và Supervisor (`/www/server/php/84/bin/php`), restart worker.
5. Quay lại: chuyển site, cron, Supervisor về PHP 8.3 và deploy tag trước đó.

**Nâng Laravel bản lớn (13 → 14):** làm theo hướng dẫn nâng cấp chính thức của Laravel trong một PR riêng, kiểm tra từng thư viện (Filament, Pennant, permission) đã hỗ trợ bản mới, chạy đủ test và thử trên staging trước.
