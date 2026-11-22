<?php

namespace App\Modules\Core\Notifications;

use InvalidArgumentException;

/** Danh sách các loại thông báo. Mỗi module đăng ký loại của mình trong ServiceProvider::boot(). */
class NotificationCatalog
{
    /** @var array<string, NotificationType> */
    private array $types = [];

    public function register(NotificationType ...$types): void
    {
        foreach ($types as $type) {
            $this->types[$type->key] = $type;
        }
    }

    public function get(string $key): NotificationType
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Chưa đăng ký loại thông báo: $key");
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /** @return array<string, NotificationType> */
    public function all(): array
    {
        return $this->types;
    }
}
