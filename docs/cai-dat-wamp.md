# Cài đặt EzFin trên Windows + WAMP

## Yêu cầu

- WampServer với Apache 2.4, **PHP 8.3**, MySQL 8 (hoặc MariaDB 10.11+).
- Extension PHP bật trong WAMP: `mbstring`, `intl`, `pdo_mysql`, `openssl`, `fileinfo`, `zip`, `gd`, `curl`.
- Module Apache: `rewrite_module`.
- Composer 2 và Node.js 22 (chỉ cần để build CSS/JS).
- PHP CLI trong PATH phải là bản 8.3 (`php -v`), trùng với bản Apache dùng.

## 1. Lấy mã nguồn

```bat
cd C:\wamp64\www
git clone https://github.com/tainguyen2810/ezfin.git
cd ezfin
composer install
npm install
copy .env.example .env
php artisan key:generate
```

## 2. Database và tài khoản quản trị

Cách nhanh (kiểu WordPress): làm xong bước 3 và 4 rồi mở `http://ezfin.test/cai-dat`. Trang này đi lần lượt: kiểm tra môi trường, nhập thông tin MySQL (tự tạo database nếu chưa có, tự ghi vào `.env`), tạo bảng, tạo tài khoản quản trị. Tạo xong tài khoản thì trang tự khóa (trả 404).

Cách dùng dòng lệnh: tạo database `ezfin` (utf8mb4, `utf8mb4_unicode_ci`) trong phpMyAdmin, sửa `DB_*` trong `.env` nếu tài khoản MySQL khác `root` không mật khẩu, rồi chạy:

```bat
php artisan migrate
php artisan ezfin:admin
```

Trang quản trị: `http://app.ezfin.test/quan-tri` (đổi bằng `EZFIN_ADMIN_PATH`).

Xác thực 2 lớp (2FA): với `APP_ENV=local` không bắt buộc, có thể bật trong trang hồ sơ. Muốn thử như production thì đặt `EZFIN_ADMIN_2FA=true` trong `.env`; lần đăng nhập đầu sẽ cần quét mã QR bằng Google Authenticator hoặc ứng dụng tương tự và lưu lại mã khôi phục. Production luôn bắt buộc 2FA.

## 3. Tên miền local

Thêm vào `C:\Windows\System32\drivers\etc\hosts` (mở Notepad bằng quyền Administrator):

```
127.0.0.1 ezfin.test
127.0.0.1 app.ezfin.test
```

Thêm vào `C:\wamp64\bin\apache\apache2.4.x\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName ezfin.test
    ServerAlias app.ezfin.test
    DocumentRoot "C:/wamp64/www/ezfin/public"
    <Directory "C:/wamp64/www/ezfin/public/">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require local
    </Directory>
</VirtualHost>
```

Cả hai tên miền trỏ vào cùng thư mục `public/`; Laravel tự phân biệt theo tên miền. Khởi động lại WAMP.

## 4. Build giao diện và kiểm tra

```bat
npm run build
php artisan test
```

Mở http://ezfin.test. Khi sửa giao diện, chạy `npm run dev` để tự cập nhật.

## Queue và scheduler khi phát triển

- `.env` mặc định `QUEUE_CONNECTION=database`. Khi cần xử lý job (gửi email...), mở một cửa sổ và chạy `php artisan queue:work`.
- Chạy tác vụ định kỳ thủ công: `php artisan schedule:work`.

## Lỗi thường gặp

| Lỗi | Cách xử lý |
| --- | --- |
| 404 cho mọi trang trừ `/` | Bật `rewrite_module`, kiểm tra `AllowOverride All` |
| `could not find driver` | Bật `pdo_mysql` cho cả PHP Apache và PHP CLI |
| Trang trắng, không có CSS | Chạy `npm run build` |
| `Specified key was too long; max key length is 1000 bytes` khi migrate | MySQL đang mặc định MyISAM. EzFin luôn tạo bảng InnoDB (`DB_ENGINE`, mặc định `InnoDB`); xóa các bảng tạo dở rồi chạy lại `php artisan migrate` |
| `php -v` ra bản khác 8.3 | Sửa PATH trỏ tới `C:\wamp64\bin\php\php8.3.x` |
