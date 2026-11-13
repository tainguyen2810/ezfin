<?php

namespace App\Modules\Markets\Models;

use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Giá mua, bán một loại vàng do một doanh nghiệp công bố tại một thời điểm. Đơn vị: đồng/lượng.
 *
 * @property int $id
 * @property string $source
 * @property string $product
 * @property Carbon $quoted_at
 * @property ?string $buy
 * @property string $sell
 * @property ?string $source_url
 */
class GoldPrice extends Model
{
    use Auditable;

    protected $fillable = ['source', 'product', 'quoted_at', 'buy', 'sell', 'source_url'];

    protected function casts(): array
    {
        return [
            'quoted_at' => 'datetime',
            'buy' => 'decimal:2',
            'sell' => 'decimal:2',
        ];
    }

    /** Ghi nhật ký khi người quản trị nhập, sửa, xóa; bỏ qua lệnh console (dữ liệu demo). */
    public function shouldAudit(): bool
    {
        return auth()->check();
    }

    public static function auditSubjectLabel(): string
    {
        return 'Giá vàng';
    }
}
