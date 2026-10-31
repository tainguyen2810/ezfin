<?php

namespace App\Support\Demo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * Danh sách bản ghi và file do lệnh `ezfin:demo` tạo ra, để xóa sạch đúng những gì đã tạo.
 * Khi file này tồn tại, website hiện dải thông báo "dữ liệu demo".
 */
class DemoManifest
{
    /** @var array<class-string<Model>, list<int>> */
    private array $records = [];

    /** @var list<string> đường dẫn trên ổ public */
    private array $files = [];

    /** @var list<string> */
    private array $users = [];

    public static function path(): string
    {
        return config('ezfin.demo_manifest') ?? storage_path('app/ezfin-demo.json');
    }

    public static function active(): bool
    {
        return File::exists(self::path());
    }

    public static function load(): self
    {
        $manifest = new self;
        if (File::exists(self::path())) {
            $data = json_decode(File::get(self::path()), true) ?: [];
            $manifest->records = $data['records'] ?? [];
            $manifest->files = $data['files'] ?? [];
            $manifest->users = $data['users'] ?? [];
        }

        return $manifest;
    }

    /**
     * @template T of Model
     *
     * @param  T  $model
     * @return T
     */
    public function track(Model $model): Model
    {
        $this->records[$model::class][] = $model->getKey();

        return $model;
    }

    public function trackFile(string $path): string
    {
        $this->files[] = $path;

        return $path;
    }

    public function trackUser(string $email): void
    {
        $this->users[] = $email;
    }

    /** @return list<int> */
    public function ids(string $class): array
    {
        return $this->records[$class] ?? [];
    }

    /** @return array<class-string<Model>, list<int>> */
    public function records(): array
    {
        return $this->records;
    }

    /** @return list<string> */
    public function files(): array
    {
        return $this->files;
    }

    /** @return list<string> */
    public function users(): array
    {
        return array_values(array_unique($this->users));
    }

    public function save(): void
    {
        File::put(self::path(), json_encode([
            'created_at' => now()->toIso8601String(),
            'records' => $this->records,
            'files' => $this->files,
            'users' => $this->users,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function forget(): void
    {
        File::delete(self::path());
    }
}
