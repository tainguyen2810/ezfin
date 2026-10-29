<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * @property int $id
 * @property ?int $user_id
 * @property string $event
 * @property ?array<string, mixed> $old_values
 * @property ?array<string, mixed> $new_values
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Không được sửa nhật ký audit.'));
        static::deleting(fn () => throw new LogicException('Không được xóa nhật ký audit.'));
    }

    /** Ghi một sự kiện, kèm người thực hiện và thông tin request hiện tại. */
    public static function record(string $event, ?Model $subject = null, ?array $old = null, ?array $new = null, ?int $userId = null): self
    {
        $request = app()->runningInConsole() ? null : request();

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'restored' => 'Khôi phục',
            'login' => 'Đăng nhập',
            'login_failed' => 'Đăng nhập sai',
            'logout' => 'Đăng xuất',
            'lockout' => 'Bị khóa tạm do đăng nhập sai nhiều lần',
            default => $this->event,
        };
    }
}
