<?php

namespace App\Modules\Personal\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dữ liệu tài chính cá nhân: khi có người đăng nhập, mọi truy vấn chỉ thấy bản ghi của chính họ
 * (kể cả route model binding, nên bản ghi của người khác trả 404) và bản ghi mới tự gán user_id.
 * Policy vẫn kiểm tra lại quyền sở hữu.
 *
 * @mixin Model
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (auth()->check()) {
                $query->where($query->qualifyColumn('user_id'), auth()->id());
            }
        });

        static::creating(function (Model $model) {
            $model->user_id ??= auth()->id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
}
