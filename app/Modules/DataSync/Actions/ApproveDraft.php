<?php

namespace App\Modules\DataSync\Actions;

use App\Models\User;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\SourceRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Duyệt bản nháp: ghi dữ liệu vào bảng sản phẩm qua bộ lấy dữ liệu của nguồn, đánh dấu đã duyệt. */
class ApproveDraft
{
    public function __construct(private readonly SourceRegistry $registry) {}

    public function handle(SyncDraft $draft, ?User $reviewer, ?string $note = null): string
    {
        if ($draft->status !== DraftStatus::Pending) {
            throw new RuntimeException('Chỉ duyệt được bản đang chờ duyệt.');
        }

        $scraper = $this->registry->for($draft->source) ?? throw new RuntimeException('Nguồn này không còn bộ lấy dữ liệu.');

        return DB::transaction(function () use ($draft, $reviewer, $note, $scraper) {
            $message = $scraper->apply($draft);
            $draft->forceFill([
                'status' => DraftStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            return $message;
        });
    }

    /**
     * Duyệt nhiều bản một lượt (hồ sơ ngân hàng trước, để logo có trước sản phẩm). Bản lỗi được giữ chờ duyệt, không chặn bản khác.
     *
     * @param  iterable<SyncDraft>  $drafts
     * @return array{approved: int, failed: list<string>}
     */
    public function handleMany(iterable $drafts, ?User $reviewer): array
    {
        $approved = 0;
        $failed = [];

        $ordered = Collection::make($drafts)
            ->filter(fn (SyncDraft $draft) => $draft->status === DraftStatus::Pending)
            ->sortBy(fn (SyncDraft $draft) => [$draft->source->kind === SyncKind::Institution ? 0 : 1, $draft->fetched_at?->timestamp ?? 0]);

        foreach ($ordered as $draft) {
            try {
                $this->handle($draft, $reviewer, 'Duyệt hàng loạt');
                $approved++;
            } catch (Throwable $e) {
                $failed[] = $draft->source->name.' ('.$draft->source->kind->getLabel().'): '.$e->getMessage();
            }
        }

        return ['approved' => $approved, 'failed' => $failed];
    }

    public function reject(SyncDraft $draft, User $reviewer, ?string $note = null): void
    {
        if ($draft->status !== DraftStatus::Pending) {
            throw new RuntimeException('Chỉ từ chối được bản đang chờ duyệt.');
        }

        $draft->forceFill([
            'status' => DraftStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();
    }
}
