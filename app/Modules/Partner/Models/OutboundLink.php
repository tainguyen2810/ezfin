<?php

namespace App\Modules\Partner\Models;

use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Core\Concerns\Auditable;
use Database\Factories\OutboundLinkFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Liên kết đối tác. Người dùng luôn đi qua ezfin.vn/di/{code} để ghi click rồi mới sang đối tác.
 * Mã ngẫu nhiên, không đoán được, không đổi sau khi tạo (link đã chia sẻ vẫn chạy).
 *
 * @property int $id
 * @property string $code
 * @property ?int $product_id
 * @property string $name
 * @property ?string $partner
 * @property string $destination_url
 * @property string $cta_label
 * @property bool $is_active
 */
#[UseFactory(OutboundLinkFactory::class)]
class OutboundLink extends Model
{
    use Auditable, HasFactory;

    public const CODE_LENGTH = 12;

    /** Chỗ thay bằng mã click trong URL đích, để đối soát với báo cáo của đối tác. */
    public const CLICK_PLACEHOLDER = '{click_id}';

    public const DEFAULT_CTA = 'Đăng ký';

    protected $fillable = ['product_id', 'name', 'partner', 'destination_url', 'cta_label', 'is_active'];

    protected $attributes = [
        'cta_label' => self::DEFAULT_CTA,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link) {
            do {
                $link->code = Str::random(self::CODE_LENGTH);
            } while (self::where('code', $link->code)->exists());
        });

        static::saving(function (self $link) {
            $link->destination_url = trim($link->destination_url);

            if (! self::isValidDestination($link->destination_url)) {
                throw ValidationException::withMessages(['destination_url' => 'URL đối tác phải bắt đầu bằng https://.']);
            }

            if ($link->isDirty('code') && $link->exists) {
                $link->code = $link->getOriginal('code');
            }
        });
    }

    public static function isValidDestination(string $url): bool
    {
        return str_starts_with($url, 'https://')
            && filter_var(str_replace(self::CLICK_PLACEHOLDER, 'x', $url), FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_HOST) !== null;
    }

    public static function auditSubjectLabel(): string
    {
        return 'Liên kết đối tác';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(OutboundClick::class, 'link_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** URL trên EzFin dùng cho nút đối tác (tương đối, chạy trên cả hai tên miền). */
    public function url(): string
    {
        return route('partner.go', $this->code, false);
    }

    /** URL đích đã gắn mã click (rỗng khi không ghi click, ví dụ bot). */
    public function destinationFor(?string $clickId): string
    {
        return str_replace(self::CLICK_PLACEHOLDER, rawurlencode($clickId ?? ''), $this->destination_url);
    }
}
