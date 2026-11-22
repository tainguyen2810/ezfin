<?php

use App\Modules\Core\Jobs\QueueHeartbeat;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Support\Heartbeat;
use App\Modules\Partner\Models\OutboundClick;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Xóa nhật ký click và nhật ký gửi thông báo quá hạn lưu trữ (xem trang Quyền riêng tư).
Schedule::command('model:prune', ['--model' => [OutboundClick::class, NotificationLog::class]])->daily();

// Ứng dụng quản lý tiền: ghi giao dịch định kỳ đến hạn, gửi email nhắc hạn (lệnh tự bỏ qua khi tắt flag personal).
Schedule::command('ezfin:recurring')->dailyAt('06:00')->withoutOverlapping()->onOneServer();

// Heartbeat cho `php artisan ezfin:check`: cron còn chạy, queue worker còn xử lý job.
Schedule::call(fn () => Heartbeat::beat(Heartbeat::SCHEDULE))->everyMinute()->name('heartbeat:schedule');
Schedule::job(new QueueHeartbeat)->everyFiveMinutes()->name('heartbeat:queue');

// Nhận tin qua email (lệnh tự bỏ qua khi tắt flag newsletter): báo lãi tăng hằng ngày, bản tin tuần sáng thứ Hai.
Schedule::command('ezfin:rate-alerts')->dailyAt('07:30')->withoutOverlapping()->onOneServer();
Schedule::command('ezfin:newsletter-weekly')->weeklyOn(1, '08:00')->withoutOverlapping()->onOneServer();

// Dữ liệu tự động (lệnh tự bỏ qua khi tắt flag data_sync): lấy lãi suất tiết kiệm từ trang ngân hàng 2 lần/ngày,
// lưu bản nháp chờ duyệt; nguồn lỗi hoặc quá hạn được báo trong trang quản trị và qua email.
Schedule::command('ezfin:sync', ['--kind' => 'savings'])->twiceDailyAt(6, 14, 20)->withoutOverlapping(30)->onOneServer();
// Tỷ giá, giá vàng: mỗi giờ trong ngày (nguồn cập nhật nhiều lần/ngày), tự duyệt khi hợp lệ.
Schedule::command('ezfin:sync', ['--kind' => 'fx'])->hourlyAt(5)->between('7:00', '19:00')->withoutOverlapping(30)->onOneServer();
Schedule::command('ezfin:sync', ['--kind' => 'gold'])->hourlyAt(10)->between('7:00', '19:00')->withoutOverlapping(30)->onOneServer();
// Hồ sơ ngân hàng (website, logo): mỗi tuần, chờ duyệt.
Schedule::command('ezfin:sync', ['--kind' => 'institution'])->weeklyOn(1, '05:30')->withoutOverlapping(30)->onOneServer();
// Danh mục thẻ tín dụng, khoản vay, tài khoản (đọc cả trang chi tiết từng sản phẩm, chờ duyệt): hằng tuần.
Schedule::command('ezfin:sync', ['--kind' => 'credit_card'])->weeklyOn(2, '05:30')->withoutOverlapping(60)->onOneServer();
Schedule::command('ezfin:sync', ['--kind' => 'loan'])->weeklyOn(3, '05:30')->withoutOverlapping(60)->onOneServer();
Schedule::command('ezfin:sync', ['--kind' => 'bank_account'])->weeklyOn(4, '05:30')->withoutOverlapping(60)->onOneServer();
