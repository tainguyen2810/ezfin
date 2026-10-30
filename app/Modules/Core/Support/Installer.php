<?php

namespace App\Modules\Core\Support;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Trạng thái cài đặt cho trang /cai-dat (kiểu WordPress): môi trường → database → tạo bảng → tài khoản quản trị.
 * Đã có tài khoản quản trị cao nhất là coi như đã cài, trang cài đặt trả 404.
 */
class Installer
{
    public const STEP_REQUIREMENTS = 'requirements';

    public const STEP_DATABASE = 'database';

    public const STEP_MIGRATE = 'migrate';

    public const STEP_ADMIN = 'admin';

    public const EXTENSIONS = ['pdo_mysql', 'mbstring', 'intl', 'openssl', 'fileinfo', 'curl', 'ctype', 'tokenizer', 'xml'];

    public function markerPath(): string
    {
        return storage_path('app/ezfin-installed');
    }

    private function tokenPath(): string
    {
        return storage_path('app/ezfin-install.token');
    }

    public function isInstalled(): bool
    {
        if (is_file($this->markerPath())) {
            return true;
        }

        if ($this->hasAdmin()) {
            $this->markInstalled();

            return true;
        }

        return false;
    }

    public function markInstalled(): void
    {
        @file_put_contents($this->markerPath(), now()->toIso8601String());
        @unlink($this->tokenPath());
    }

    /** Mã chống giả mạo cho các form cài đặt (trang này chạy không cần session). */
    public function token(): string
    {
        if (! is_file($this->tokenPath())) {
            file_put_contents($this->tokenPath(), Str::random(40));
        }

        return (string) file_get_contents($this->tokenPath());
    }

    public function validToken(?string $token): bool
    {
        return is_file($this->tokenPath()) && is_string($token) && hash_equals($this->token(), $token);
    }

    /** Tạo .env và APP_KEY nếu chưa có, để các bước sau chạy được. */
    public function prepareEnvironment(): void
    {
        $env = EnvFile::app();

        if (! $env->exists() && is_file(base_path('.env.example')) && $env->writable()) {
            $env->ensureExists(base_path('.env.example'));
        }

        if ($env->exists() && $env->writable() && blank($env->get('APP_KEY'))) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $env->set(['APP_KEY' => $key]);
            config(['app.key' => $key]);
        }
    }

    /** @return list<array{label: string, ok: bool, hint: string}> */
    public function requirements(): array
    {
        $checks = [[
            'label' => 'PHP 8.3 trở lên (đang dùng '.PHP_VERSION.')',
            'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'hint' => 'Chọn PHP 8.3 trong WAMP.',
        ]];

        foreach (self::EXTENSIONS as $extension) {
            $checks[] = ['label' => "Extension PHP $extension", 'ok' => extension_loaded($extension), 'hint' => "Bật $extension trong WAMP (PHP → PHP extensions)."];
        }

        foreach (['storage' => storage_path(), 'bootstrap/cache' => base_path('bootstrap/cache')] as $label => $path) {
            $checks[] = ['label' => "Ghi được thư mục $label", 'ok' => is_writable($path), 'hint' => 'Cấp quyền ghi cho thư mục này.'];
        }

        $checks[] = ['label' => 'Ghi được file .env', 'ok' => EnvFile::app()->writable(), 'hint' => 'Cấp quyền ghi cho file .env (hoặc thư mục gốc nếu chưa có .env).'];
        $checks[] = ['label' => 'Thư viện PHP đã cài (vendor)', 'ok' => is_file(base_path('vendor/autoload.php')), 'hint' => 'Chạy composer install.'];
        $checks[] = ['label' => 'Giao diện đã build (public/build)', 'ok' => is_file(public_path('build/manifest.json')), 'hint' => 'Chạy npm install rồi npm run build.'];

        return $checks;
    }

    public function requirementsMet(): bool
    {
        return collect($this->requirements())->every('ok');
    }

    public function databaseError(): ?string
    {
        try {
            DB::connection()->getPdo();

            return null;
        } catch (Throwable $e) {
            return Str::limit($e->getMessage(), 300);
        }
    }

    public function pendingMigrations(): int
    {
        $migrator = app(Migrator::class);

        if (! $migrator->repositoryExists()) {
            return PHP_INT_MAX;
        }

        $paths = array_merge([database_path('migrations')], $migrator->paths());

        return count(array_diff(array_keys($migrator->getMigrationFiles($paths)), $migrator->getRepository()->getRan()));
    }

    public function hasAdmin(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasTable('roles')
                && User::role(Role::SuperAdmin->value)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function currentStep(): string
    {
        return match (true) {
            ! $this->requirementsMet() => self::STEP_REQUIREMENTS,
            $this->databaseError() !== null => self::STEP_DATABASE,
            $this->pendingMigrations() > 0 => self::STEP_MIGRATE,
            default => self::STEP_ADMIN,
        };
    }
}
