<?php

namespace App\Modules\Personal\Actions;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Personal\Imports\StatementUploads;
use Illuminate\Support\Facades\DB;

/**
 * Khách hàng tự xóa tài khoản: xóa người dùng cùng toàn bộ dữ liệu tài chính (khóa ngoại cascade),
 * phiên đăng nhập và mã đặt lại mật khẩu. Tài khoản nội bộ (có vai trò quản trị) không tự xóa ở đây.
 */
class DeleteAccount
{
    public function isStaff(User $user): bool
    {
        return $user->hasAnyRole(Role::values());
    }

    public function handle(User $user): void
    {
        abort_if($this->isStaff($user), 403);

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        app(StatementUploads::class)->forgetUser($user->id);
    }
}
