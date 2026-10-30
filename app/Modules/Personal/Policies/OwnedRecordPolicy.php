<?php

namespace App\Modules\Personal\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Chính sách chung cho dữ liệu cá nhân dùng BelongsToUser: chỉ chủ sở hữu được xem, sửa, xóa. */
class OwnedRecordPolicy
{
    public function view(User $user, Model $record): bool
    {
        return $record->isOwnedBy($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $record->isOwnedBy($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return $record->isOwnedBy($user);
    }
}
