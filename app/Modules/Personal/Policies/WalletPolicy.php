<?php

namespace App\Modules\Personal\Policies;

use App\Models\User;
use App\Modules\Personal\Models\Wallet;

/** Chỉ chủ sở hữu được xem, sửa, xóa ví của mình. */
class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $wallet->isOwnedBy($user);
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $wallet->isOwnedBy($user);
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $wallet->isOwnedBy($user);
    }
}
