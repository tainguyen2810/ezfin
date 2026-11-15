<?php

namespace App\Modules\DataSync\Models;

use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Enums\SourceHealth;
use App\Modules\DataSync\Enums\SyncKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Một nguồn dữ liệu tự động. Bản ghi được tạo/cập nhật từ danh sách bộ lấy dữ liệu (SourceRegistry);
 * quản trị viên chỉ bật/tắt và chọn tự duyệt.
 *
 * @property int $id
 * @property string $key
 * @property SyncKind $kind
 * @property string $name
 * @property string $url
 * @property bool $is_enabled
 * @property bool $auto_approve
 * @property ?Carbon $last_run_at
 * @property ?Carbon $last_success_at
 * @property ?Carbon $last_failure_at
 * @property ?string $last_error
 * @property int $consecutive_failures
 * @property ?Carbon $last_alerted_at
 */
class SyncSource extends Model
{
    protected $fillable = ['key', 'kind', 'name', 'url', 'is_enabled', 'auto_approve'];

    protected $attributes = [
        'is_enabled' => true,
        'auto_approve' => false,
        'consecutive_failures' => 0,
    ];

    protected function casts(): array
    {
        return [
            'kind' => SyncKind::class,
            'is_enabled' => 'boolean',
            'auto_approve' => 'boolean',
            'last_run_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'last_alerted_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(SyncDraft::class, 'source_id');
    }

    public function pendingDrafts(): HasMany
    {
        return $this->drafts()->where('status', DraftStatus::Pending->value);
    }

    public function health(): SourceHealth
    {
        if (! $this->is_enabled) {
            return SourceHealth::Disabled;
        }

        if ($this->consecutive_failures > 0) {
            return SourceHealth::Failing;
        }

        if ($this->last_success_at === null) {
            return SourceHealth::Never;
        }

        return $this->last_success_at->lt(now()->subHours($this->kind->staleAfterHours()))
            ? SourceHealth::Stale
            : SourceHealth::Ok;
    }
}
