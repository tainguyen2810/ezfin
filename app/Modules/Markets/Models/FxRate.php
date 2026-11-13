<?php

namespace App\Modules\Markets\Models;

use App\Modules\Core\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Một dòng trong bảng tỷ giá ngân hàng công bố tại một thời điểm. Đơn vị: đồng cho 1 đơn vị ngoại tệ.
 *
 * @property int $id
 * @property string $source
 * @property string $currency
 * @property Carbon $quoted_at
 * @property ?string $buy_cash
 * @property ?string $buy_transfer
 * @property string $sell
 * @property ?string $source_url
 */
class FxRate extends Model
{
    use Auditable;

    /** Ngoại tệ nhận nhập, theo thứ tự hiển thị. */
    public const CURRENCIES = [
        'USD' => 'Đô la Mỹ', 'EUR' => 'Euro', 'JPY' => 'Yên Nhật', 'GBP' => 'Bảng Anh', 'CNY' => 'Nhân dân tệ',
        'KRW' => 'Won Hàn Quốc', 'AUD' => 'Đô la Úc', 'SGD' => 'Đô la Singapore', 'CAD' => 'Đô la Canada',
        'CHF' => 'Franc Thụy Sĩ', 'HKD' => 'Đô la Hồng Kông', 'THB' => 'Baht Thái', 'TWD' => 'Đô la Đài Loan',
    ];

    protected $fillable = ['source', 'currency', 'quoted_at', 'buy_cash', 'buy_transfer', 'sell', 'source_url'];

    protected function casts(): array
    {
        return [
            'quoted_at' => 'datetime',
            'buy_cash' => 'decimal:2',
            'buy_transfer' => 'decimal:2',
            'sell' => 'decimal:2',
        ];
    }

    public function currencyName(): string
    {
        return self::CURRENCIES[$this->currency] ?? $this->currency;
    }

    /** Ghi nhật ký khi người quản trị nhập, sửa, xóa; bỏ qua lệnh console (dữ liệu demo). */
    public function shouldAudit(): bool
    {
        return auth()->check();
    }

    public static function auditSubjectLabel(): string
    {
        return 'Tỷ giá';
    }
}
