# TASKS — Phase 1: Public Website & Compare

Trạng thái: `Chưa làm` · `Đang làm` · `Chờ duyệt` (PR mở) · `Xong` (đã merge, đạt tiêu chí).

| # | Việc | Tiêu chí nghiệm thu | Trạng thái |
| --- | --- | --- | --- |
| 1 | Khởi tạo repo, Laravel, Tailwind/Vite, Pest, CLAUDE.md, TASKS.md, CHANGELOG.md, .env.example | Chạy được trên WAMP theo hướng dẫn; CI test xanh | Xong |
| 2 | Khung module, route theo tên miền (ezfin.vn / app.ezfin.vn), feature flag | Tắt flag thì menu ẩn và URL trả 404; có test | Xong |
| 3 | Design token, font Be Vietnam Pro tự host, component dùng chung, trang `/_ui` | Đủ component và trạng thái; ảnh Playwright 360/768/1280px không tràn ngang | Xong |
| 4 | Đăng nhập admin (Filament), role, 2FA, audit log | Không đủ quyền nhận 403; sửa/xóa ghi audit; giới hạn đăng nhập sai | Xong |
| 5 | Quản lý ngân hàng/tổ chức tài chính | CRUD, upload logo, slug duy nhất; test validation | Xong |
| 6 | Quản lý sản phẩm thẻ tín dụng | Không xuất bản khi thiếu nguồn hoặc ngày xác minh; có test | Xong |
| 7 | Danh sách và bộ lọc thẻ (public) | Lọc theo ngân hàng, phí, nhu cầu, thu nhập; trạng thái lọc trên URL; test bộ lọc | Xong |
| 8 | So sánh tối đa 3 thẻ | Khay so sánh giữ lựa chọn khi chuyển trang; đọc được trên 360px | Xong |
| 9 | Trang chi tiết thẻ | Ngày cập nhật, nguồn, công bố liên kết, sản phẩm tương tự, schema hợp lệ | Xong |
| 10 | Nhập lãi suất tiết kiệm: form + import CSV | Báo đúng dòng lỗi, không ghi dở dang; import lại không tạo trùng | Xong |
| 11 | So sánh lãi suất theo số tiền và kỳ hạn | Xếp theo tiền lãi thực nhận; khớp bộ số liệu kiểm thử | Xong |
| 12 | Tài khoản ngân hàng: danh sách, chi tiết | Như task 7 và 9 | Xong |
| 13 | Bộ máy tính toán (PHP) + khung trang công cụ | Công thức có test số liệu tính tay; chạy được khi tắt JS; URL chia sẻ giữ kết quả | Xong |
| 14 | 5 công cụ: lãi kép, lãi tiền gửi, tiết kiệm định kỳ, khoản vay, trả góp | Nêu giả định, kiểm tra đầu vào, bảng + biểu đồ; trả góp phân biệt lãi phẳng/dư nợ giảm dần | Xong |
| 15 | CMS bài viết, chuyên mục, tác giả, duyệt bài | Biên tập viên không tự xuất bản; lưu lịch sử sửa; lọc XSS | Chờ duyệt |
| 16 | SEO nền: meta, canonical, sitemap, robots, breadcrumb, OG, 301 | Sitemap chỉ gồm trang đã xuất bản; app và `/di/` là noindex | Chưa làm |
| 17 | Trang chủ, footer, trang pháp lý, phương pháp đánh giá | Không nút giả, số liệu giả; mọi link hoạt động | Chưa làm |
| 18 | Chuyển hướng `/di/` + nhật ký click | Mã click không đoán được; lọc bot; rate limit; `rel="sponsored nofollow"` | Chưa làm |
| 19 | Deploy staging trên aaPanel | HTTPS, cron, queue worker, backup hằng ngày; đã khôi phục thử thành công | Chưa làm |
| 20 | Nghiệm thu Phase 1 | Toàn bộ test xanh; Lighthouse mobile ≥ 90 (trang chủ, so sánh lãi suất, lãi kép); chủ dự án duyệt trên staging | Chưa làm |

## Nhật ký phiên

### 29/10/2026 — Task 15

- Chủ dự án yêu cầu: PR xong và CI xanh thì tự merge, làm tiếp tới hết Phase 1; giao diện sẽ được kiểm tra sau.
- Xong: module Content.
  - Quản trị (nhóm "Nội dung"): Bài viết, Chuyên mục, Tác giả. Trình soạn thảo giới hạn định dạng (đậm, nghiêng, liên kết, H2/H3, danh sách, trích dẫn, bảng, ảnh); nguồn tham khảo; ảnh bìa.
  - Quy trình: biên tập viên viết nháp → "Gửi duyệt" (cần tóm tắt và nội dung) → bài bị khóa sửa, có thể "Rút về nháp". Quản trị cao nhất "Duyệt và xuất bản" (ngay hoặc hẹn giờ), "Trả lại" kèm ghi chú, "Gỡ xuất bản", sửa trực tiếp bài đang hiển thị. Menu hiện số bài chờ duyệt. Quy tắc kiểm tra ở cả `ArticlePolicy`, `ArticleWorkflow` và model `Article` (gọi thẳng model cũng không qua được).
  - Lịch sử sửa: mỗi lần đổi tiêu đề/tóm tắt/nội dung/nguồn lưu một bản (ai, lúc nào), xem lại và khôi phục; bản lưu không sửa được. Audit log ghi thay đổi nhưng không chép nội dung dài.
  - Lọc XSS: `ArticleHtml` (symfony/html-sanitizer, đã có sẵn qua Filament) chỉ giữ thẻ định dạng; bỏ script, iframe, style, thuộc tính sự kiện, liên kết `javascript:`/`data:`; ảnh chỉ đường dẫn tương đối hoặc https. Chạy mỗi khi gán nội dung.
  - Xóa bài là xóa mềm (khôi phục được); không xóa chuyên mục/tác giả còn bài.
  - Trang công khai: `/kien-thuc` (lọc chuyên mục, phân trang), `/kien-thuc/{chuyen-muc}`, `/kien-thuc/{chuyen-muc}/{bai}` (bài đổi chuyên mục thì 301 về URL mới), `/tac-gia/{slug}`; chỉ hiện bài đã xuất bản và đến giờ; ghi rõ tác giả, ngày xuất bản, ngày cập nhật (khi nội dung đổi sau ngày xuất bản), nguồn tham khảo, lưu ý không phải khuyến nghị cá nhân; JSON-LD Article và ProfilePage. Menu "Kiến thức", tắt cùng flag `content`.
  - Component mới `x-ui.article-card` (có trong `/_ui`).
- Test đã chạy: `vendor/bin/pest` (330 test xanh; mới: `tests/Unit/Content/ArticleHtmlTest.php`, `tests/Feature/Content/{ArticleAdminTest,KnowledgePagesTest}.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm `/kien-thuc`), Playwright 360px và 1280px danh sách, chuyên mục, bài viết, tác giả: không tràn ngang, không lỗi JS. Sửa thêm test `CreditCardAdminTest` phụ thuộc múi giờ (trong PR #14).
- Chưa giải quyết: biên tập viên chưa sửa được bài đang hiển thị qua bản nháp chờ duyệt (hiện phải nhờ người duyệt sửa hoặc gỡ bài về nháp). Chưa có vai trò "tổng biên tập" riêng; quyền duyệt thuộc quản trị cao nhất. Nhúng bảng lãi suất/thẻ sản phẩm vào bài để Phase 2.
- File quan trọng: `app/Modules/Content/{Models,Actions,Policies,Support,Filament,Http,resources/views}`, `resources/views/components/ui/article-card.blade.php`, `resources/css/app.css` (`.article-body`).
- Tiếp theo: task 16 (SEO nền: meta, canonical, sitemap, robots, breadcrumb, OG, 301).

### 29/10/2026 — Task 14

- Xong: đủ 5 công cụ tại `ezfin.vn/cong-cu`, mỗi công cụ có kiểm tra đầu vào (thông báo tiếng Việt dưới từng ô), thẻ kết quả, biểu đồ, bảng số liệu, mục "Cách tính và giả định", liên kết chia sẻ:
  - Lãi kép (`/cong-cu/lai-kep`): thêm biểu đồ tiền bỏ vào + lãi cộng dồn theo năm.
  - Lãi tiền gửi (`/cong-cu/lai-tien-gui`): lĩnh cuối kỳ hoặc hằng tháng; link sang trang so sánh lãi suất với cùng số tiền và kỳ hạn (ẩn khi tắt module compare).
  - Tiết kiệm định kỳ (`/cong-cu/tiet-kiem-dinh-ky`): gửi đầu mỗi tháng, có số tiền ban đầu; kỳ trên 36 tháng biểu đồ lấy cuối mỗi năm.
  - Khoản vay (`/cong-cu/khoan-vay`): dư nợ giảm dần hoặc trả đều, so sánh tổng lãi với cách còn lại; vay trên 3 năm hiện lịch theo năm, lịch từng tháng gập trong `<details>` (mở được khi tắt JS).
  - Trả góp (`/cong-cu/tra-gop`): giá, % trả trước, lãi phẳng hoặc dư nợ giảm dần; hiện lãi suất dư nợ giảm dần tương đương của lãi phẳng và tiền lãi của cả hai cách với cùng mức lãi suất.
- Component mới `x-ui.bar-chart` (có trong `/_ui`): biểu đồ cột chồng dựng bằng HTML/CSS từ server, không thêm thư viện JS; hiện khi tắt JS; rê chuột hoặc Tab vào cột để xem số, mỗi cột có `aria-label` đọc đủ số liệu. Màu đã kiểm tra độ phân biệt cho người mù màu: xanh dương = gốc/tiền bỏ vào, xanh lá = lãi nhận, cam = lãi phải trả.
- `RepaymentSchedule::byYear()` cộng lịch trả nợ theo năm. Icon mới `calendar`.
- Test đã chạy: `vendor/bin/pest` (284 test xanh; mới: `tests/Feature/Planner/ToolsTest.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm 4 trang công cụ), Playwright 360px và 1280px cả 5 công cụ: không tràn ngang, không lỗi JS; tắt JS: trả góp tính đúng 21,46%/năm, mở được lịch trả nợ từng tháng.
- File quan trọng: `app/Modules/Planner/Tools/{DepositInterestTool,RecurringSavingsTool,LoanTool,InstallmentTool}.php`, `app/Modules/Planner/resources/views/tools/*`, `resources/views/components/ui/bar-chart.blade.php`.
- Tiếp theo: task 15 (CMS bài viết, chuyên mục, tác giả, duyệt bài).

### 29/10/2026 — Task 13

- Xong: bộ máy tính toán trong `app/Modules/Planner/Calculators` (PHP thuần, tiền là số nguyên đồng, lãi suất quy về phần vạn của 1%, cộng phần lẻ rồi mới làm tròn nửa lên đến đồng):
  - `CompoundInterest`: lãi kép ghép hằng tháng/quý/năm, góp thêm cuối mỗi tháng, bảng theo năm.
  - `DepositInterest`: lãi tiền gửi có kỳ hạn, lĩnh cuối kỳ hoặc hằng tháng.
  - `RecurringSavings`: tiết kiệm định kỳ, gửi đầu mỗi tháng, lãi đơn đến cuối kỳ.
  - `Repayment`: lịch trả nợ dư nợ giảm dần (gốc đều), trả đều hằng tháng (niên kim), lãi phẳng kèm lãi suất dư nợ giảm dần tương đương.
  - Kết quả vượt 1 triệu tỷ đồng thì báo lỗi thay vì tràn số. Chỉ phép lũy thừa tìm số tiền trả đều và lãi suất tương đương dùng số thực; lịch trả nợ tính lại bằng số nguyên.
- Khung trang công cụ: `ezfin.vn/cong-cu` (danh sách) và `ezfin.vn/cong-cu/{slug}`; menu "Công cụ tính", tắt cùng module `planner`. Mỗi công cụ là một class kế thừa `Tools\Tool` (ô nhập, mặc định, kiểm tra, giả định, view form/kết quả), đăng ký trong `PlannerServiceProvider`. Form GET chạy được khi tắt JS; URL gọn (bỏ dấu chấm trong số tiền, đổi dấu phẩy lãi suất, bỏ giá trị mặc định, giữ utm_*) là liên kết chia sẻ giữ nguyên kết quả; trang có số liệu riêng là noindex, canonical về trang gốc. Lỗi nhập hiện ngay dưới từng ô.
- Công cụ đầu tiên: Tính lãi kép (`/cong-cu/lai-kep`). Biểu đồ và 4 công cụ còn lại thuộc task 14.
- Component mới `x-ui.share-link` (có trong `/_ui`). Sửa `x-ui.money-input`: khi tắt JS, ô hiển thị gửi giá trị lên server (trước đây ô ẩn gửi giá trị cũ, nên sửa số tiền trên trang so sánh lãi suất không có tác dụng khi tắt JS).
- Test đã chạy: `vendor/bin/pest` (258 test xanh; mới: `tests/Unit/Planner/CalculatorsTest.php` với số liệu tính tay và hàm PMT, `tests/Feature/Planner/ToolPageTest.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm `/cong-cu`, `/cong-cu/lai-kep`), Playwright: tắt JS nhập 50.000.000 đ / 7,5% / 5 năm ra 71.781.466 đ đúng URL; có JS ở 360px và 1280px nhập, gửi, sao chép liên kết, không tràn ngang, không lỗi JS.
- File quan trọng: `app/Modules/Planner/{Calculators/*,Enums/*,Tools/*,Http/Controllers/ToolController.php,PlannerServiceProvider.php,resources/views/tools/*}`, `resources/views/components/ui/{money-input,share-link}.blade.php`.
- Tiếp theo: task 14 (5 công cụ: lãi kép, lãi tiền gửi, tiết kiệm định kỳ, khoản vay, trả góp; bảng và biểu đồ).

### 29/10/2026 — Task 12

- Xong: loại sản phẩm "Tài khoản thanh toán" (`ProductType::BankAccount`) và bảng `bank_account_details`: phí quản lý/tháng, ghi chú phí, số dư tối thiểu, phí chuyển khoản, phí rút ATM (DECIMAL(18,2), để trống = chưa công bố, khác 0), tính năng (mở online eKYC, miễn phí chuyển khoản, thẻ ghi nợ quốc tế, số đẹp, sinh lời tự động, ngoại tệ).
- Quản trị `app.ezfin.vn/<admin>/tai-khoan-ngan-hang`: cùng quyền, nguồn, ngày xác minh, ưu/nhược điểm như thẻ (phần ưu/nhược điểm chuyển vào `ProductFormComponents`).
- Trang `ezfin.vn/tai-khoan-ngan-hang` (menu "Tài khoản ngân hàng"): lọc ngân hàng, phí quản lý (miễn phí / đến 10.000 đ), tính năng; sắp xếp phí quản lý, số dư tối thiểu, phí chuyển khoản, mới xác minh (chưa công bố xếp cuối). URL gọn, giữ utm_*, có lọc thì noindex.
- Trang `ezfin.vn/tai-khoan-ngan-hang/{slug}`: biểu phí, tính năng, ưu/nhược điểm, nguồn, ngày cập nhật, tài khoản tương tự (tính năng chung rồi phí gần nhất), canonical, JSON-LD `WebPage` + `BankAccount` + `BreadcrumbList` (không đánh giá sao).
- Không có khay so sánh cho tài khoản (task không yêu cầu), nên thẻ sản phẩm ẩn ô "So sánh" để không có nút giả. `x-ui.product-card` dùng 4 cột khi có đúng 4 thông số.
- Công bố affiliate: chưa có link đối tác, sẽ thêm ở task 18.
- Test đã chạy: `vendor/bin/pest` (215 test xanh; mới: `BankAccountAdminTest`, `BankAccountListingTest`, `BankAccountDetailTest`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm `/tai-khoan-ngan-hang`), Playwright 360px và 1280px danh sách, có lọc, chi tiết: không tràn ngang, không lỗi JS.
- File quan trọng: `app/Modules/Compare/{Actions/{SearchBankAccounts,FindSimilarBankAccounts}.php,Support/{BankAccountFilters,BankAccountFacts,BankAccountSchema}.php,Http/Controllers/BankAccountController.php,Models/BankAccountDetail.php,Enums/BankAccountFeature.php,Filament/Resources/BankAccounts/*,resources/views/bank-accounts/*}`.
- Tiếp theo: task 13 (bộ máy tính toán + khung trang công cụ).

### 29/10/2026 — Task 11

- Xong: trang `ezfin.vn/lai-suat-tiet-kiem` (menu "Lãi suất tiết kiệm", tắt cùng module compare). Nhập số tiền, chọn kỳ hạn (1–36 tháng), kênh, hình thức lĩnh lãi (mặc định 100.000.000 đ, 12 tháng, cuối kỳ, mọi kênh). Mỗi sản phẩm một dòng, xếp theo tiền lãi nhận được (bằng nhau thì lãi suất, rồi tên); hiện lãi suất, tiền lãi, tổng nhận, kênh, lĩnh lãi, mức tiền áp dụng, ngày hiệu lực, nguồn, ngày xác minh. Ghi rõ cách tính và giả định.
- Quy tắc chọn lãi suất (`CompareSavingsRates`): chỉ sản phẩm đã xuất bản của tổ chức đang hoạt động; mỗi biểu (sản phẩm, kênh, lĩnh lãi) chỉ dùng lần công bố mới nhất đã có hiệu lực (bỏ ngày tương lai, bỏ bậc chỉ có ở biểu cũ); bậc số tiền: từ ≤ số tiền < đến, nhiều bậc khớp lấy bậc "từ" cao nhất.
- Công thức (`SavingsInterest::simple`): số tiền × lãi suất × tháng / 12, tính bằng số nguyên, làm tròn nửa lên đến đồng; test với 7 bộ số liệu tính tay. Số tiền giới hạn 1.000.000 đ – 100 tỷ đ.
- URL gọn (bỏ giá trị mặc định, giữ utm_*); trang mặc định có canonical và được lập chỉ mục, trang có điều kiện khác là noindex.
- Test đã chạy: `vendor/bin/pest` (177 test xanh, 15 test mới trong `tests/Feature/Compare/SavingsCompareTest.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm `/lai-suat-tiet-kiem`), Playwright 360px và 1280px: nhập 2.000.000.000 đ, không tràn ngang, không lỗi JS.
- File quan trọng: `app/Modules/Compare/{Actions/CompareSavingsRates.php,Support/{SavingsFilters,SavingsInterest}.php,Http/Controllers/SavingsController.php,resources/views/savings/index.blade.php}`.
- Tiếp theo: task 12 (tài khoản ngân hàng: danh sách, chi tiết).

### 29/10/2026 — Task 10

- Xong: loại sản phẩm mới "Tiết kiệm" (`ProductType::Savings`) và bảng `savings_rates`: kỳ hạn (tháng, 0 = không kỳ hạn), kênh (online/tại quầy), hình thức lĩnh lãi (cuối kỳ/hằng tháng/hằng quý/đầu kỳ), số tiền từ/đến, lãi suất %/năm DECIMAL(7,4), ngày hiệu lực. Khóa duy nhất (sản phẩm, kỳ hạn, kênh, lĩnh lãi, số tiền từ, ngày hiệu lực); giữ lịch sử theo ngày hiệu lực.
- Quản trị `app.ezfin.vn/<admin>/tiet-kiem`: thông tin sản phẩm, nguồn và ngày xác minh (dùng chung với thẻ qua `ProductFormComponents`), bảng lãi suất nhập tay dạng bảng, chặn dòng trùng và lãi suất > 20%. Danh sách hiện số dòng lãi suất, ngày hiệu lực mới nhất, hạn kiểm tra lại.
- Nhập CSV (`ImportSavingsRates`): nút "Nhập CSV" → "Nhập lãi suất từ CSV" / "Tải file mẫu". Cột `san_pham,ky_han,kenh,lai_suat,so_tien_tu,so_tien_den,linh_lai,hieu_luc_tu`. Nhận file Excel tiếng Việt (BOM, dấu chấm phẩy, số thập phân dấu phẩy, tiền có dấu chấm, ngày dd/mm/yyyy). Kiểm tra toàn bộ file trước, báo lỗi theo số dòng thật trong file (kể cả dòng trùng trong file), có lỗi thì không ghi dòng nào; ghi trong transaction; nhập lại cập nhật thay vì tạo trùng; báo số dòng thêm mới/cập nhật/không đổi. Chỉ Vận hành dữ liệu và Quản trị cao nhất thấy nút nhập.
- Test đã chạy: `vendor/bin/pest` (162 test xanh; mới: `SavingsRateImportTest`, `SavingsProductAdminTest`), `vendor/bin/pint`, `npm run build`, Playwright trang quản trị: nhập file lỗi (4 lỗi, không ghi), nhập file đúng (6 dòng), sửa bảng lãi suất.
- File quan trọng: `app/Modules/Compare/{Actions/ImportSavingsRates.php,Models/SavingsRate.php,Enums/{SavingsChannel,InterestPayment}.php,Filament/Resources/SavingsProducts/*,Filament/Resources/Shared/ProductFormComponents.php,database/migrations/2026_10_30_100003_create_savings_rates_table.php}`.
- Tiếp theo: task 11 (trang so sánh lãi suất theo số tiền và kỳ hạn, xếp theo tiền lãi thực nhận).

### 29/10/2026 — Task 9

- Xong: trang `ezfin.vn/the-tin-dung/{slug}` (chỉ thẻ đã xuất bản của tổ chức đang hoạt động, còn lại 404; tắt cùng module compare). Có ảnh thẻ hoặc logo, tên đầy đủ ngân hàng, nhóm lợi ích, mô tả, bảng thông số ("—" là chưa công bố), ưu điểm, hạn chế, nguồn + ghi chú nguồn + ngày cập nhật + cảnh báo quá hạn kiểm tra, nút "Thêm vào so sánh" và khay so sánh. Canonical và JSON-LD schema.org (`WebPage` + `CreditCard` + `BreadcrumbList`, chỉ dữ liệu đã công bố, không có đánh giá sao; mã hóa `<` để không chèn được script).
- Thẻ tương tự (`FindSimilarCreditCards`): nhiều nhóm lợi ích chung trước, rồi phí thường niên gần nhất; tối đa 3 thẻ; không xét đối tác.
- Danh sách thẻ có nút "Xem chi tiết", tên thẻ trong bảng so sánh link tới trang chi tiết. Layout public có thêm `canonical` và slot `head`. Icon mới `minus-circle`.
- Công bố liên kết: chưa có link đối tác (task 18) nên chưa hiện nút đăng ký và đoạn công bố; khi thêm link đối tác thì trang chi tiết phải hiện `x-ui.disclosure` (đã có test khẳng định hiện tại không có).
- Test đã chạy: `vendor/bin/pest` (141 test xanh, 7 test mới trong `tests/Feature/Compare/CreditCardDetailTest.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui`, Playwright trang chi tiết 360px và 1280px (không tràn ngang, không lỗi JS, thêm vào so sánh hoạt động).
- File quan trọng: `app/Modules/Compare/{Actions/FindSimilarCreditCards.php,Support/CreditCardSchema.php,Http/Controllers/CreditCardController.php,resources/views/credit-cards/show.blade.php}`.
- Tiếp theo: task 10 (nhập lãi suất tiết kiệm: form + import CSV).

### 29/10/2026 — Task 8

- Xong: trang `ezfin.vn/the-tin-dung/so-sanh?ids=slug-a,slug-b` (noindex, tắt cùng module compare). Danh sách thẻ bật ô "So sánh" (lưu theo slug) và khay so sánh; lựa chọn giữ trong sessionStorage khi đổi bộ lọc/trang. Bảng so sánh giữ đúng thứ tự người dùng chọn, tối đa 3 thẻ; slug sai, trùng, thẻ nháp hoặc của tổ chức ngừng hoạt động bị bỏ và URL được làm gọn (giữ utm_*). Mỗi tiêu chí số có nhãn "Thấp nhất"/"Cao nhất"/"Dài nhất" chỉ khi ít nhất 2 thẻ có số liệu và không bằng nhau; ô chưa công bố là "—". Mỗi thẻ có link "Bỏ khỏi so sánh", nguồn và ngày xác minh. Mở link chia sẻ thì khay đồng bộ theo URL.
- Component mới `x-ui.compare-table` (nhãn tiêu chí chiếm cả dòng, giá trị chia cột: đọc được ở 360px), đã thêm vào `/_ui/so-sanh`. Store `compare` có thêm `replace()`. Khay so sánh chừa khoảng trống cuối trang để không che phân trang.
- Sửa phát hiện khi test: ô "So sánh" của `x-ui.product-card` không có `x-data` nên Alpine không gắn sự kiện ngoài trang mẫu → thêm `x-data`.
- Test đã chạy: `vendor/bin/pest` (134 test xanh, 7 test mới trong `tests/Feature/Compare/CreditCardCompareTest.php`), `vendor/bin/pint`, `npm run build`, `npm run test:ui` (thêm `/the-tin-dung/so-sanh`), Playwright 360px và 1280px: chọn 2 thẻ, đổi bộ lọc vẫn còn 2/3, mở bảng so sánh, bỏ một thẻ.
- File quan trọng: `app/Modules/Compare/{Actions/FindComparableCreditCards.php,Support/CreditCardComparison.php,Http/Controllers/CreditCardController.php,resources/views/credit-cards/compare.blade.php}`, `resources/views/components/ui/compare-table.blade.php`.
- Tiếp theo: task 9 (trang chi tiết thẻ; route `/the-tin-dung/{slug}` đặt sau route so sánh).

### 29/10/2026 — Task 7

- Xong: trang công khai `ezfin.vn/the-tin-dung` (menu "Thẻ tín dụng", tắt module compare → 404 và ẩn menu). Lọc theo ngân hàng, phí thường niên (miễn phí / đến 500.000 đ / đến 1.000.000 đ), nhu cầu (hoàn tiền, tích dặm, mua sắm, du lịch, ăn uống, trả góp 0%), thu nhập hằng tháng; sắp xếp theo phí, hoàn tiền, thu nhập yêu cầu, mới xác minh; thông số chưa công bố xếp cuối. Bộ lọc nằm trên URL với tên tiếng Việt không dấu, form gửi xong chuyển về một URL gọn (bỏ ô trống, giá trị mặc định, giữ utm_*). Trang có bộ lọc là noindex. Mỗi thẻ hiện ngày cập nhật, link nguồn, cảnh báo "Cần xác minh lại" khi quá hạn kiểm tra. Chỉ hiện thẻ đã xuất bản của tổ chức đang hoạt động.
- Chưa có nút "Xem chi tiết", "So sánh", "Đăng ký" vì trang chi tiết (task 9), trang so sánh (task 8) và link đối tác (task 18) chưa có: không đặt nút giả. Vì chưa có link đối tác nên chưa hiện công bố liên kết.
- Component: `x-ui.product-card` cho phép bỏ `detailUrl` và có slot `footer`; `x-ui.data-freshness` nhận `stale` truyền sẵn.
- Sửa phát hiện khi test: `?phi[]=...` làm lỗi 500 (đọc mảng như chuỗi) → chỉ nhận chuỗi. Phân trang truyền trang và đường dẫn tường minh, không phụ thuộc resolver tĩnh (Livewire của trang quản trị ghi đè resolver trong cùng tiến trình test).
- Test đã chạy: `vendor/bin/pest` (126 test xanh, 24 test mới trong `tests/Feature/Compare/CreditCardListingTest.php`), `vendor/bin/pint --test`, `npm run build`, `npm run test:ui` (thêm `/the-tin-dung`, 5 trang × 3 kích thước), Playwright thử mở ngăn kéo bộ lọc trên 360px, lọc, trạng thái rỗng.
- File quan trọng: `app/Modules/Compare/{Actions/SearchCreditCards.php,Support/CreditCardFilters.php,Http/Controllers/CreditCardController.php,routes/public.php,resources/views/credit-cards/index.blade.php}`.
- Tiếp theo: task 8 (so sánh tối đa 3 thẻ, bật ô "So sánh" trên danh sách).

### 29/10/2026 — Task 6

- Xong: bảng `financial_products` (trường chung mọi loại sản phẩm: tổ chức, tên, slug, trạng thái nháp/xuất bản/ngừng phát hành, mô tả, ưu điểm, hạn chế, ảnh, nguồn, ngày xác minh, hẹn kiểm tra lại) và `credit_card_details` (tổ chức thẻ, phí thường niên, phí phát hành, thu nhập tối thiểu, lãi suất, ngày miễn lãi, hoàn tiền %, trần hoàn tiền, phí ngoại tệ, nhóm lợi ích). Màn hình quản trị "Thẻ tín dụng" có lọc theo ngân hàng, trạng thái, "đến hạn kiểm tra lại". Quy tắc xuất bản chặn ở cả form và model: thiếu nguồn, thiếu ngày xác minh hoặc ngày xác minh ở tương lai thì không xuất bản được. Hẹn kiểm tra lại mặc định 3 tháng. Tổ chức còn sản phẩm thì không xóa được (policy + khóa ngoại).
- Sửa phát hiện khi test: ô nhập tiền dùng `numeric()` + `stripCharacters('.')` làm giá trị "1200000.00" từ DB thành 120.000.000 khi mở form sửa rồi lưu. Đổi sang định dạng/giải mã thủ công, có test giữ nguyên số tiền khi lưu lại.
- Gom xóa file cũ vào trait `DeletesReplacedFiles` (dùng cho logo và ảnh thẻ).
- Test đã chạy: `vendor/bin/pest` (102 test xanh, 20 test mới), `vendor/bin/pint --test`, Playwright: nhập thẻ qua giao diện (chọn ngân hàng, tiền có dấu chấm, ưu/nhược điểm, ảnh), thử xuất bản khi thiếu nguồn (bị chặn), danh sách và form sửa ở 390px và 1366px không tràn ngang, không lỗi JS. Chưa chạy được migrate trên MySQL ở máy này; CI chạy MySQL 8.
- Chưa giải quyết: thông báo "Please fill out this field" là của trình duyệt (tiếng Anh) khi bỏ trống ô bắt buộc; thông báo của hệ thống vẫn tiếng Việt. Chưa nhập thẻ thật (cần nguồn chính thức).
- File quan trọng: `app/Modules/Compare/{Models/FinancialProduct.php,Models/CreditCardDetail.php,Enums,Filament/Resources/CreditCards}`, `app/Modules/Core/Concerns/DeletesReplacedFiles.php`, `tests/Feature/Compare/CreditCardAdminTest.php`.
- Tiếp theo: task 7 (danh sách và bộ lọc thẻ ở trang công khai).

### 29/10/2026 — Task 5

- Xong: bảng `financial_institutions` (module Compare: loại, tên ngắn, tên đầy đủ, slug duy nhất, logo, website, đang hoạt động), màn hình quản trị "Ngân hàng và tổ chức tài chính" (thêm, sửa, xóa, tìm kiếm, lọc theo loại/trạng thái), tự gợi ý slug không dấu khi tạo, upload logo PNG/JPG/WebP ≤ 1 MB (chặn SVG vì có thể chứa script), tự xóa file logo cũ khi thay hoặc xóa, ghi nhật ký. Quyền: Quản trị cao nhất và Vận hành dữ liệu nhập liệu, Biên tập chỉ xem, chỉ Quản trị cao nhất được xóa.
- Sửa: URL ổ `public` đổi sang đường dẫn tương đối `/storage` (đặt lại bằng `FILESYSTEM_PUBLIC_URL`). Trước đó ảnh lưu ở `ezfin.vn/storage/...` nên trang quản trị trên `app.ezfin.vn` không tải lại được logo khi sửa (lỗi CORS).
- Test đã chạy: `vendor/bin/pest` (82 test xanh, 20 test mới), `vendor/bin/pint --test`, Playwright: tạo 3 tổ chức có logo qua giao diện, thử lỗi slug trùng và website http, trang danh sách/sửa ở 390px và 1366px không tràn ngang, không lỗi JS.
- Chưa giải quyết: chặn xóa tổ chức đang có sản phẩm sẽ thêm ở task 6 (khóa ngoại `restrictOnDelete`). Chưa nhập dữ liệu ngân hàng thật: cần nguồn chính thức và ngày xác minh.
- File quan trọng: `app/Modules/Compare/{Models/FinancialInstitution.php,Enums/InstitutionType.php,Policies,Filament/Resources/FinancialInstitutions}`, `config/filesystems.php`, `tests/Feature/Compare/FinancialInstitutionAdminTest.php`.
- Tiếp theo: task 6 (quản lý sản phẩm thẻ tín dụng).

### 29/10/2026 — Task 4

- Xong: Filament 5 tại `app.ezfin.vn/<EZFIN_ADMIN_PATH>` (noindex), 4 vai trò nội bộ (Quản trị cao nhất, Biên tập nội dung, Vận hành dữ liệu, Đối tác và tài chính) bằng spatie/laravel-permission, 2FA bằng ứng dụng xác thực bắt buộc + mã khôi phục, khóa tạm sau 5 lần đăng nhập sai/phút, audit log (tạo/sửa/xóa, đăng nhập, đăng nhập sai, đăng xuất, bị khóa; không bao giờ ghi mật khẩu, khóa 2FA), màn hình quản lý người dùng và xem nhật ký (chỉ Quản trị cao nhất), lệnh `php artisan ezfin:admin`, ảnh đại diện chữ cái tạo tại chỗ (không gọi ui-avatars.com).
- Test đã chạy: `vendor/bin/pest` (62 test xanh, 28 test mới cho quản trị), `vendor/bin/pint --test`, `npm run build`, chụp Playwright màn hình đăng nhập, 2FA, người dùng, nhật ký ở 390px và 1366px (không tràn ngang, không lỗi JS).
- Chưa giải quyết: quyền chi tiết của từng vai trò (biên tập, vận hành dữ liệu, tài chính) sẽ gắn vào policy khi có module tương ứng (task 5, 6, 10, 15); hiện các vai trò này chỉ vào được bảng điều khiển. Email đặt lại mật khẩu admin chưa bật, cần SMTP Brevo (task 19).
- File quan trọng: `app/Providers/Filament/AdminPanelProvider.php`, `app/Modules/Core/{Enums/Role.php,Models/AuditLog.php,Concerns/Auditable.php,Policies,Filament,Console/CreateAdminCommand.php}`, `app/Models/User.php`, `tests/Feature/Admin/*`.
- Tiếp theo: task 5 (quản lý ngân hàng/tổ chức tài chính).

### 29/10/2026 — Task 3

- Xong: design token (Tailwind 4, biến CSS), font Be Vietnam Pro tự host, Alpine.js, 27 component Blade trong `resources/views/components/ui`, layout website (`x-layouts.public`) và ứng dụng (`x-layouts.app`, sidebar + thanh dưới có nút +), `Format` (tiền, %, ngày kiểu Việt Nam), icon Heroicons nhúng, trang mẫu `/_ui`, `/_ui/so-sanh`, `/_ui/ung-dung` (chỉ ngoài production), phân trang tiếng Việt.
- Test đã chạy: `vendor/bin/pest` (34 test xanh), `npm run test:ui` (4 trang × 360/768/1280px: không tràn ngang, không lỗi JS), thử tay ngăn kéo bộ lọc, menu mobile, modal.
- Chưa giải quyết: biểu đồ (Chart.js) để task 13; chuỗi validation tiếng Việt thêm khi làm form thật.
- File quan trọng: `resources/css/app.css`, `resources/js/app.js`, `resources/views/components/**`, `app/Support/Format.php`, `tests/browser/ui-smoke.mjs`.
- Tiếp theo: task 4 (đăng nhập admin Filament, role, 2FA, audit log).

### 29/10/2026 — Task 2

- Xong: lớp `ModuleServiceProvider` (tự nạp route theo tên miền, view, migration), 5 module khung (Core, Compare, Planner, Content, Partner), menu `Navigation` lọc theo route + feature flag, Pennant dùng làm công tắc toàn hệ thống, flag tắt → 404, middleware `noindex` cho tên miền ứng dụng, lệnh `php artisan ezfin:feature`.
- Test đã chạy: `vendor/bin/pest` (17 test xanh), `vendor/bin/pint --test`.
- Chưa giải quyết: không.
- File quan trọng: `config/ezfin.php`, `app/Support/Modules/ModuleServiceProvider.php`, `app/Support/Navigation/*`, `app/Providers/AppServiceProvider.php`.
- Tiếp theo: task 3 (design system, trang `/_ui`).

### 29/10/2026 — Task 1

- Xong: khung Laravel 13 (PHP 8.3), Pest, CI (PHP 8.3/8.4 + MySQL 8), locale vi, tài liệu cài đặt/triển khai/nâng cấp.
- Test đã chạy: `vendor/bin/pest` (3 test xanh, SQLite), `vendor/bin/pint --test`, `npm run build`.
- Chưa giải quyết: chưa chạy thử trên WAMP thật của chủ dự án; CI MySQL sẽ chạy khi mở PR.
- File quan trọng: `composer.json`, `config/app.php`, `.env.example`, `.github/workflows/ci.yml`, `docs/`.
- Tiếp theo: task 2 (module, route theo tên miền, feature flag).
