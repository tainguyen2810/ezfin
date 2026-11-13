<?php

namespace App\Modules\Partner\Models;

use App\Modules\Core\Concerns\Auditable;
use App\Modules\Partner\Enums\ConversionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Một chuyển đổi (mở thẻ, mở tài khoản...) đối tác ghi nhận. Một mã giao dịch của một mạng chỉ có một dòng;
 * báo lại thì cập nhật trạng thái và hoa hồng. Hoa hồng: VND, DECIMAL(18,2).
 *
 * @property int $id
 * @property string $network
 * @property string $external_id
 * @property ?string $click_id
 * @property ?int $link_id
 * @property ?string $source_path
 * @property ConversionStatus $status
 * @property string $commission
 * @property Carbon $occurred_at
 */
class AffiliateConversion extends Model
{
    use Auditable;

    protected $fillable = ['network', 'external_id', 'click_id', 'link_id', 'source_path', 'status', 'commission', 'occurred_at'];

    protected $attributes = ['status' => 'pending', 'commission' => 0];

    protected function casts(): array
    {
        return [
            'status' => ConversionStatus::class,
            'commission' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    /** Chỉ ghi nhật ký khi người quản trị sửa tay hoặc nhập file; postback tự động của đối tác thì không. */
    public function shouldAudit(): bool
    {
        return auth()->check();
    }

    public static function auditSubjectLabel(): string
    {
        return 'Chuyển đổi đối tác';
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(OutboundLink::class, 'link_id');
    }
}
