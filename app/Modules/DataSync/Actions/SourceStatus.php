<?php

namespace App\Modules\DataSync\Actions;

use App\Modules\DataSync\Models\SyncSource;
use Illuminate\Support\Facades\Schema;

/** Tóm tắt tình trạng nguồn dữ liệu tự động cho module khác (ví dụ lệnh ezfin:check). */
class SourceStatus
{
    /** @return list<string> Tên nguồn đang bật mà lỗi hoặc quá hạn, kèm tình trạng. */
    public function problems(): array
    {
        if (! Schema::hasTable('sync_sources')) {
            return [];
        }

        return SyncSource::query()->where('is_enabled', true)->orderBy('name')->get()
            ->filter(fn (SyncSource $source) => $source->health()->needsAttention())
            ->map(fn (SyncSource $source) => "{$source->name} ({$source->kind->getLabel()}): {$source->health()->getLabel()}")
            ->values()->all();
    }
}
