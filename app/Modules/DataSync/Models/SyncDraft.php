<?php

namespace App\Modules\DataSync\Models;

use App\Models\User;
use App\Modules\DataSync\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dữ liệu lấy về từ một nguồn, chờ duyệt. `payload` có cấu trúc theo loại nguồn,
 * ví dụ lãi suất tiết kiệm: {"rows": [{"term": 12, "channel": "online", "payment": "end_of_term", "min_amount": 0, "max_amount": null, "rate": "5.4000"}]}.
 *
 * @property int $id
 * @property int $source_id
 * @property DraftStatus $status
 * @property array $payload
 * @property string $payload_hash
 * @property ?list<string> $warnings
 * @property ?Carbon $effective_from
 * @property Carbon $fetched_at
 * @property ?int $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property ?string $review_note
 */
class SyncDraft extends Model
{
    protected $fillable = ['status', 'payload', 'payload_hash', 'warnings', 'effective_from', 'fetched_at'];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => DraftStatus::class,
            'payload' => 'array',
            'warnings' => 'array',
            'effective_from' => 'date',
            'fetched_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SyncSource::class, 'source_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Bản đã duyệt gần nhất trước bản này của cùng nguồn, để so sánh. */
    public function previousApproved(): ?self
    {
        return self::query()->where('source_id', $this->source_id)->where('status', DraftStatus::Approved)
            ->where('id', '<', $this->id)->latest('id')->first();
    }

    /** @return list<array<string, mixed>> */
    public function rows(): array
    {
        return $this->payload['rows'] ?? [];
    }
}
