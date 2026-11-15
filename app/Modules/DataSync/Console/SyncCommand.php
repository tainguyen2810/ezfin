<?php

namespace App\Modules\DataSync\Console;

use App\Modules\DataSync\Actions\RunSource;
use App\Modules\DataSync\Enums\SourceHealth;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Support\AdminAlerts;
use App\Modules\DataSync\Support\SourceRegistry;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/**
 * Lấy dữ liệu tự động từ các nguồn đang bật (lịch chạy trong routes/console.php). Kết quả mỗi nguồn ghi vào
 * trang quản trị Nguồn dữ liệu; nguồn lỗi hoặc quá hạn được báo cho quản trị viên.
 *
 *   php artisan ezfin:sync                 tất cả nguồn đang bật
 *   php artisan ezfin:sync --kind=savings  chỉ một loại
 *   php artisan ezfin:sync savings.bidv    một nguồn (chạy cả khi đang tắt)
 */
class SyncCommand extends Command
{
    protected $signature = 'ezfin:sync {source? : Mã nguồn, ví dụ savings.vietcombank} {--kind= : Loại nguồn (savings, fx, gold)} {--list : Chỉ liệt kê nguồn và tình trạng}';

    protected $description = 'Lấy dữ liệu tự động (lãi suất, tỷ giá, giá vàng) từ nguồn chính thức, lưu bản nháp chờ duyệt';

    public function handle(SourceRegistry $registry, RunSource $run, AdminAlerts $alerts): int
    {
        if (! Feature::active('data_sync') && ! $this->argument('source')) {
            $this->warn('Feature flag data_sync đang tắt, bỏ qua.');

            return self::SUCCESS;
        }

        $registry->sync();

        if ($this->option('list')) {
            $this->table(['Mã', 'Tên', 'Tình trạng', 'Thành công gần nhất', 'Lỗi gần nhất'], SyncSource::query()->orderBy('key')->get()->map(fn (SyncSource $s) => [
                $s->key, $s->name, $s->health()->getLabel(), $s->last_success_at?->format('d/m/Y H:i') ?? '-', (string) $s->last_error,
            ]));

            return self::SUCCESS;
        }

        $query = SyncSource::query()->orderBy('key');
        if ($key = $this->argument('source')) {
            $query->where('key', $key);
        } else {
            $query->where('is_enabled', true)->when($this->option('kind'), fn ($q, $kind) => $q->where('kind', SyncKind::from($kind)->value));
        }

        $sources = $query->get();
        if ($sources->isEmpty()) {
            $this->error('Không có nguồn nào phù hợp.');

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($sources as $source) {
            $result = $run->handle($source);
            $failed += $result['status'] === 'failed' ? 1 : 0;
            $line = "{$source->key}: {$result['message']}";
            $result['status'] === 'failed' ? $this->error($line) : $this->line($line);
        }

        // Nguồn đang bật mà lâu không thành công (ví dụ cron ngừng chạy một thời gian) cũng phải báo.
        SyncSource::query()->where('is_enabled', true)->get()
            ->filter(fn (SyncSource $source) => $source->health() === SourceHealth::Stale)
            ->each(fn (SyncSource $source) => $alerts->stale($source));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
