<?php

use App\Modules\Core\Jobs\QueueHeartbeat;
use App\Modules\Core\Support\Heartbeat;
use App\Modules\Partner\Models\OutboundClick;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Xóa nhật ký click quá hạn lưu trữ (xem trang Quyền riêng tư).
Schedule::command('model:prune', ['--model' => [OutboundClick::class]])->daily();

// Ứng dụng quản lý tiền: ghi giao dịch định kỳ đến hạn, gửi email nhắc hạn (lệnh tự bỏ qua khi tắt flag personal).
Schedule::command('ezfin:recurring')->dailyAt('06:00')->withoutOverlapping()->onOneServer();

// Heartbeat cho `php artisan ezfin:check`: cron còn chạy, queue worker còn xử lý job.
Schedule::call(fn () => Heartbeat::beat(Heartbeat::SCHEDULE))->everyMinute()->name('heartbeat:schedule');
Schedule::job(new QueueHeartbeat)->everyFiveMinutes()->name('heartbeat:queue');
