<?php

use App\Modules\Core\Jobs\QueueHeartbeat;
use App\Modules\Core\Support\Heartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('fails when cron or the queue worker have not run recently', function () {
    expect(Artisan::call('ezfin:check'))->toBe(1)
        ->and(Artisan::output())->toContain('Chưa chạy lần nào. Thêm cron mỗi phút')->toContain('Chưa chạy lần nào. Kiểm tra Supervisor');

    Heartbeat::beat(Heartbeat::SCHEDULE);
    $this->travel(20)->minutes();
    (new QueueHeartbeat)->handle();

    expect(Artisan::call('ezfin:check'))->toBe(1)
        ->and(Artisan::output())->toContain('Lần cuối 20 phút trước. Thêm cron mỗi phút')->not->toContain('Kiểm tra Supervisor');
});

it('passes when the server is set up', function () {
    Heartbeat::beat(Heartbeat::SCHEDULE);
    (new QueueHeartbeat)->handle();
    // CI chạy test không build asset và không tạo public/storage: tạo tạm rồi dọn.
    $created = [];
    if (! file_exists($manifest = public_path('build/manifest.json'))) {
        @mkdir(dirname($manifest), recursive: true);
        file_put_contents($manifest, '{}');
        $created[] = fn () => unlink($manifest);
    }
    if (! file_exists($link = public_path('storage'))) {
        symlink(storage_path('app/public'), $link);
        $created[] = fn () => unlink($link);
    }

    try {
        $this->artisan('ezfin:check')->doesntExpectOutputToContain('✗')->assertExitCode(0);
    } finally {
        array_map(fn ($cleanup) => $cleanup(), $created);
    }
});

it('flags debug mode and a non-https url on a live server', function () {
    app()->detectEnvironment(fn () => 'staging');
    config(['app.debug' => true, 'app.url' => 'http://staging.ezfin.vn', 'ezfin.indexable' => true]);

    $this->artisan('ezfin:check')
        ->expectsOutputToContain('APP_DEBUG=true làm lộ thông tin lỗi')
        ->expectsOutputToContain('EZFIN_INDEXABLE=true nhưng không phải production')
        ->assertExitCode(1);
});

it('schedules the heartbeats and the click log cleanup', function () {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->description ?? $event->command);

    expect($commands->implode("\n"))
        ->toContain('heartbeat:schedule')
        ->toContain('heartbeat:queue')
        ->toContain('model:prune');
});
