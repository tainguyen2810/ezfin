<?php

namespace App\Modules\Core\Console;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Support\Heartbeat;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kiểm tra máy chủ sau khi cài đặt hoặc deploy (staging, production): cấu hình, database, migration,
 * thư mục ghi được, asset, cron, queue worker, email. Trả mã lỗi 1 nếu có mục hỏng.
 */
class CheckDeploymentCommand extends Command
{
    protected $signature = 'ezfin:check';

    protected $description = 'Kiểm tra cấu hình máy chủ EzFin sau khi cài đặt hoặc deploy';

    /** Cron chạy mỗi phút; quá 3 phút không thấy là hỏng. */
    public const SCHEDULE_MAX_MINUTES = 3;

    /** Scheduler đẩy job heartbeat 5 phút một lần; quá 15 phút worker chưa xử lý là hỏng. */
    public const QUEUE_MAX_MINUTES = 15;

    /** @var list<array{string, string, string}> */
    private array $rows = [];

    public function handle(Migrator $migrator): int
    {
        $this->rows = [];
        $live = ! app()->environment('local', 'testing');

        $this->check('APP_KEY', filled(config('app.key')), 'Chưa có, chạy php artisan key:generate');
        $this->check('APP_DEBUG tắt', ! config('app.debug') || ! $live, 'APP_DEBUG=true làm lộ thông tin lỗi');
        $this->check('APP_URL dùng https', str_starts_with((string) config('app.url'), 'https://') || ! $live, (string) config('app.url'), warn: true);
        $this->check('Cookie phiên chỉ qua HTTPS', (bool) config('session.secure') || ! $live, 'Đặt SESSION_SECURE_COOKIE=true', warn: true);
        $this->check('Tên miền', collect([config('ezfin.domains.public'), config('ezfin.domains.app')])->every(fn ($domain) => ! Str::endsWith((string) $domain, ['.test', '.local'])) || ! $live,
            config('ezfin.domains.public').' / '.config('ezfin.domains.app'));

        $indexable = (bool) config('ezfin.indexable');
        $this->check('Lập chỉ mục tìm kiếm', $indexable === app()->isProduction(),
            $indexable ? 'EZFIN_INDEXABLE=true nhưng không phải production' : 'Production nhưng EZFIN_INDEXABLE=false (Google không index)', warn: true,
            ok: $indexable ? 'Bật (production)' : 'Tắt, mọi trang noindex');

        try {
            DB::connection()->getPdo();
            $this->check('Kết nối database', true, '', ok: DB::connection()->getDriverName().' / '.DB::connection()->getDatabaseName());

            $paths = array_merge([database_path('migrations')], $migrator->paths());
            $pending = $migrator->repositoryExists()
                ? array_diff(array_keys($migrator->getMigrationFiles($paths)), $migrator->getRepository()->getRan())
                : ['(chưa migrate)'];
            $this->check('Migration', $pending === [], count($pending).' migration chưa chạy, chạy php artisan migrate --force');

            $this->check('Tài khoản quản trị', User::role(Role::SuperAdmin->value)->exists(), 'Chưa có, chạy php artisan ezfin:admin', warn: true);
        } catch (Throwable $e) {
            $this->check('Kết nối database', false, Str::limit($e->getMessage(), 120));
        }

        try {
            Cache::put('ezfin:check', 'ok', 60);
            $this->check('Cache', Cache::pull('ezfin:check') === 'ok', 'Không đọc lại được giá trị vừa ghi');
        } catch (Throwable $e) {
            $this->check('Cache', false, Str::limit($e->getMessage(), 120));
        }

        foreach ([storage_path(), storage_path('framework/cache'), storage_path('logs'), base_path('bootstrap/cache')] as $path) {
            $this->check('Ghi được '.Str::after($path, base_path().DIRECTORY_SEPARATOR), is_dir($path) && is_writable($path), 'Không ghi được, kiểm tra chủ sở hữu (www)');
        }
        // Trên Windows, storage:link tạo junction: is_link()/is_dir() có thể trả false, realpath() vẫn lần ra đích.
        $this->check('Liên kết public/storage', is_link(public_path('storage')) || is_dir(public_path('storage')) || realpath(public_path('storage')) !== false, 'Chạy php artisan storage:link');
        $this->check('Giao diện đã build', is_file(public_path('build/manifest.json')), 'Chạy npm ci && npm run build');

        $this->heartbeat('Cron (schedule:run)', Heartbeat::SCHEDULE, self::SCHEDULE_MAX_MINUTES, 'Thêm cron mỗi phút: php artisan schedule:run');
        $this->heartbeat('Queue worker', Heartbeat::QUEUE, self::QUEUE_MAX_MINUTES, 'Kiểm tra Supervisor chạy php artisan queue:work');

        $mailer = (string) config('mail.default');
        $this->check('Gửi email', ! in_array($mailer, ['log', 'array'], true) || ! $live, "MAIL_MAILER=$mailer: email không được gửi đi", warn: true, ok: $mailer);

        $this->table(['', 'Mục', 'Kết quả'], $this->rows);

        $failed = collect($this->rows)->where(0, '✗')->count();
        $warned = collect($this->rows)->where(0, '!')->count();

        if ($failed > 0) {
            $this->error("$failed mục hỏng, $warned cảnh báo.");

            return self::FAILURE;
        }

        $warned > 0 ? $this->warn("Không có mục hỏng, $warned cảnh báo.") : $this->info('Mọi mục đều ổn.');

        return self::SUCCESS;
    }

    private function heartbeat(string $label, string $name, int $maxMinutes, string $hint): void
    {
        $last = Heartbeat::last($name);
        $fresh = $last !== null && $last->gt(now()->subMinutes($maxMinutes));

        $this->check($label, $fresh, $last ? 'Lần cuối '.$last->diffForHumans().'. '.$hint : 'Chưa chạy lần nào. '.$hint,
            ok: 'Lần cuối '.$last?->diffForHumans());
    }

    private function check(string $label, bool $passed, string $problem, bool $warn = false, string $ok = 'OK'): void
    {
        $this->rows[] = $passed ? ['✓', $label, $ok] : [$warn ? '!' : '✗', $label, $problem];
    }
}
