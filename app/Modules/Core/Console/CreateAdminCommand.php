<?php

namespace App\Modules\Core\Console;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/** Tạo tài khoản quản trị (dùng khi cài đặt lần đầu). 2FA được thiết lập ở lần đăng nhập đầu tiên. */
class CreateAdminCommand extends Command
{
    protected $signature = 'ezfin:admin {--name=} {--email=} {--role=super-admin}';

    protected $description = 'Tạo tài khoản quản trị EzFin';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Họ tên');
        $email = $this->option('email') ?: $this->ask('Email');
        $role = $this->option('role');
        $password = $this->secret('Mật khẩu (tối thiểu 12 ký tự, có chữ hoa, chữ thường, số)');

        $validator = Validator::make(compact('name', 'email', 'role', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', Role::values())],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create(compact('name', 'email', 'password'));
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($role);

        $this->info("Đã tạo tài khoản {$email} (".Role::from($role)->label().'). Lần đăng nhập đầu sẽ yêu cầu thiết lập xác thực 2 lớp.');
        $this->line('Đăng nhập tại: '.(parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https').'://'.config('ezfin.domains.app').'/'.config('ezfin.admin_path'));

        return self::SUCCESS;
    }
}
