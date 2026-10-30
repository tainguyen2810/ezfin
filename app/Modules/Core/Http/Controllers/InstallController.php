<?php

namespace App\Modules\Core\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Support\EnvFile;
use App\Modules\Core\Support\Installer;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trang cài đặt từng bước /cai-dat (kiểu WordPress). Chạy không cần session/cache (database có thể chưa có),
 * nên form dùng mã cài đặt lưu trong file thay cho CSRF và lỗi được hiển thị trực tiếp.
 * Đã có tài khoản quản trị cao nhất thì toàn bộ trang trả 404.
 */
class InstallController
{
    public function __construct(private Installer $installer) {}

    public function show(): Response
    {
        $this->ensureNotInstalled();
        $this->installer->prepareEnvironment();

        return $this->page();
    }

    public function database(Request $request): Response
    {
        $this->ensureNotInstalled();
        $this->ensureToken($request);

        $validator = Validator::make($request->all(), [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ], [
            'database.regex' => 'Tên database chỉ gồm chữ không dấu, số và dấu gạch dưới.',
        ]);

        if ($validator->fails()) {
            return $this->page(errors: $validator->errors()->all(), status: 422);
        }

        $data = $validator->validated();

        try {
            $pdo = new PDO("mysql:host={$data['host']};port={$data['port']}", $data['username'], $data['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            return $this->page(errors: ['Không kết nối được MySQL: '.Str::limit($e->getMessage(), 300)], status: 422);
        }

        EnvFile::app()->set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => (string) $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'] ?? '',
        ]);

        Artisan::call('config:clear');

        return redirect()->route('install.show');
    }

    public function migrate(Request $request): Response
    {
        $this->ensureNotInstalled();
        $this->ensureToken($request);

        @set_time_limit(300);

        try {
            Artisan::call('migrate', ['--force' => true]);

            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
        } catch (Throwable $e) {
            return $this->page(errors: ['Tạo bảng bị lỗi: '.Str::limit($e->getMessage(), 500)], status: 500);
        }

        return redirect()->route('install.show');
    }

    public function admin(Request $request): Response
    {
        $this->ensureNotInstalled();
        $this->ensureToken($request);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            return $this->page(errors: $validator->errors()->all(), status: 422);
        }

        $user = User::create($validator->safe()->only(['name', 'email', 'password']));
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(Role::SuperAdmin->value);

        $this->installer->markInstalled();

        return redirect()->to(Filament::getPanel('admin')->getLoginUrl());
    }

    /** @param  list<string>  $errors */
    private function page(array $errors = [], int $status = 200): Response
    {
        $step = $this->installer->currentStep();

        return response()->view('core::install.show', [
            'step' => $step,
            'steps' => [
                Installer::STEP_REQUIREMENTS => 'Môi trường',
                Installer::STEP_DATABASE => 'Database',
                Installer::STEP_MIGRATE => 'Tạo bảng',
                Installer::STEP_ADMIN => 'Tài khoản quản trị',
            ],
            'requirements' => $this->installer->requirements(),
            'databaseError' => $step === Installer::STEP_DATABASE ? $this->installer->databaseError() : null,
            'db' => [
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => config('database.connections.mysql.database'),
                'username' => config('database.connections.mysql.username'),
            ],
            'token' => $this->installer->token(),
            'problems' => $errors,
            'old' => request()->except(['password', 'password_confirmation', '_install']),
        ], $status);
    }

    private function ensureNotInstalled(): void
    {
        abort_if($this->installer->isInstalled(), 404);
    }

    private function ensureToken(Request $request): void
    {
        abort_unless($this->installer->validToken($request->input('_install')), 419, 'Trang cài đặt đã hết hạn, hãy tải lại trang.');
    }
}
