<?php

namespace App\Modules\DataSync\Actions;

use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Support\AdminAlerts;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\SourceRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Chạy một nguồn: lấy dữ liệu, kiểm tra hợp lệ, so với lần đã duyệt, lưu bản nháp chờ duyệt (hoặc tự duyệt nếu nguồn bật
 * và không có cảnh báo). Lỗi bất kỳ (bị chặn, đổi giao diện, số vô lý) được ghi vào nguồn và báo cho quản trị viên.
 */
class RunSource
{
    public function __construct(
        private readonly SourceRegistry $registry,
        private readonly Fetcher $fetcher,
        private readonly AdminAlerts $alerts,
        private readonly ApproveDraft $approve,
    ) {}

    /** @return array{status: 'new'|'unchanged'|'approved'|'failed', message: string, draft: ?SyncDraft} */
    public function handle(SyncSource $source): array
    {
        $scraper = $this->registry->for($source);
        $source->forceFill(['last_run_at' => now()])->save();

        try {
            if ($scraper === null) {
                throw new ScrapeException("Không còn bộ lấy dữ liệu cho nguồn {$source->key}.");
            }

            $result = $scraper->scrape($this->fetcher);
            if ($errors = $scraper->validate($result->payload)) {
                throw new ScrapeException('Dữ liệu lấy về không hợp lệ: '.implode(' ', $errors));
            }
        } catch (Throwable $e) {
            return $this->fail($source, $e);
        }

        $hash = hash('sha256', json_encode([$result->payload, $result->effectiveFrom?->toDateString()]));
        $latest = $source->drafts()->whereIn('status', [DraftStatus::Pending, DraftStatus::Approved, DraftStatus::Rejected])->latest('id')->first();
        $this->succeed($source);

        if ($latest && $latest->payload_hash === $hash) {
            if ($latest->status === DraftStatus::Pending) {
                $latest->forceFill(['fetched_at' => now()])->save();
            }

            return ['status' => 'unchanged', 'message' => 'Không có thay đổi so với lần trước.', 'draft' => $latest];
        }

        $approved = $source->drafts()->where('status', DraftStatus::Approved)->latest('id')->first();
        $warnings = $scraper->compare($result->payload, $approved?->payload);

        $draft = DB::transaction(function () use ($source, $result, $hash, $warnings) {
            $source->pendingDrafts()->update(['status' => DraftStatus::Superseded->value]);

            return $source->drafts()->create([
                'payload' => $result->payload,
                'payload_hash' => $hash,
                'warnings' => $warnings ?: null,
                'effective_from' => $result->effectiveFrom,
                'fetched_at' => now(),
            ]);
        });

        if ($source->auto_approve && $warnings === []) {
            try {
                $message = $this->approve->handle($draft, null, 'Tự duyệt (nguồn bật tự duyệt, không có cảnh báo).');

                return ['status' => 'approved', 'message' => $message, 'draft' => $draft];
            } catch (Throwable $e) {
                // Không ghi được (ví dụ dữ liệu trùng/không hợp lệ với bảng đích): để bản nháp chờ người duyệt xem.
                $draft->forceFill(['warnings' => ['Tự duyệt không thành công: '.Str::limit($e->getMessage(), 300)]])->save();
            }
        }

        $this->alerts->draftWaiting($source, $draft);

        return ['status' => 'new', 'message' => 'Có dữ liệu mới chờ duyệt'.($warnings ? ' (có '.count($warnings).' cảnh báo).' : '.'), 'draft' => $draft];
    }

    private function fail(SyncSource $source, Throwable $e): array
    {
        $message = $e instanceof ScrapeException ? $e->getMessage() : 'Lỗi không xác định: '.Str::limit($e->getMessage(), 300);
        if (! $e instanceof ScrapeException) {
            Log::warning("Data sync {$source->key} failed", ['exception' => $e]);
        }

        $source->forceFill([
            'last_failure_at' => now(),
            'last_error' => $message,
            'consecutive_failures' => $source->consecutive_failures + 1,
        ])->save();

        $this->alerts->failed($source);

        return ['status' => 'failed', 'message' => $message, 'draft' => null];
    }

    private function succeed(SyncSource $source): void
    {
        $recovered = $source->consecutive_failures > 0;
        $source->forceFill(['last_success_at' => now(), 'consecutive_failures' => 0])->save();

        if ($recovered) {
            $this->alerts->recovered($source);
        }
    }
}
