<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Concerns\BelongsToUser;
use App\Modules\Personal\Enums\CategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Danh mục thu/chi của người dùng. Bộ mặc định được tạo khi cần (CreateDefaultCategories).
 *
 * @property int $id
 * @property string $name
 * @property CategoryType $type
 * @property string $icon
 * @property string $color
 */
class Category extends Model
{
    use BelongsToUser;

    /** Màu nền + màu icon. Viết đủ tên lớp để Tailwind sinh CSS. */
    public const COLORS = [
        'orange' => 'bg-orange-50 text-orange-600',
        'red' => 'bg-red-50 text-red-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'pink' => 'bg-pink-50 text-pink-600',
        'teal' => 'bg-teal-50 text-teal-600',
        'green' => 'bg-green-50 text-green-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];

    /** Icon chọn được cho danh mục (có trong resources/svg/icons). */
    public const ICONS = [
        'cake', 'home', 'truck', 'shopping-bag', 'shopping-cart', 'film', 'heart', 'academic-cap', 'bolt', 'gift', 'users',
        'device-phone-mobile', 'globe-asia-australia', 'wrench-screwdriver', 'shield-check', 'receipt-percent', 'briefcase',
        'banknotes', 'trophy', 'currency-dollar', 'chart-bar', 'sparkles', 'face-smile', 'ticket', 'tag', 'ellipsis-horizontal',
    ];

    protected $fillable = ['name', 'type', 'icon', 'color', 'sort'];

    protected function casts(): array
    {
        return ['type' => CategoryType::class, 'archived_at' => 'datetime'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeOfType(Builder $query, CategoryType $type): void
    {
        $query->where('type', $type);
    }

    public function colorClasses(): string
    {
        return self::COLORS[$this->color] ?? self::COLORS['slate'];
    }
}
