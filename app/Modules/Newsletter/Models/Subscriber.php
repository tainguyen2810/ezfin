<?php

namespace App\Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Một địa chỉ email đã xác nhận nhận tin. Token dùng cho link quản lý/hủy trong mọi email (không đoán được).
 * Không dùng Auditable: dữ liệu cá nhân, không chép vào nhật ký quản trị.
 *
 * @property int $id
 * @property string $email
 * @property string $token
 * @property bool $weekly_digest
 * @property ?string $source_path
 * @property Carbon $consent_at
 * @property Carbon $confirmed_at
 * @property ?Carbon $weekly_sent_at
 */
class Subscriber extends Model
{
    public const TOKEN_LENGTH = 40;

    protected $fillable = ['email', 'weekly_digest', 'source_path', 'consent_at', 'confirmed_at'];

    protected function casts(): array
    {
        return [
            'weekly_digest' => 'boolean',
            'consent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'weekly_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            $subscriber->email = Str::lower(trim($subscriber->email));
            $subscriber->token = Str::random(self::TOKEN_LENGTH);
        });
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(RateAlert::class);
    }

    /** Không còn nhận gì thì xóa hẳn (không giữ email khi không cần). */
    public function deleteIfEmpty(): bool
    {
        if (! $this->weekly_digest && ! $this->alerts()->exists()) {
            $this->delete();

            return true;
        }

        return false;
    }

    public function manageUrl(): string
    {
        return route('newsletter.manage', $this->token);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }
}
