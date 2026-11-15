<?php

namespace App\Modules\DataSync\Actions;

use App\Models\User;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\SourceRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
