<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Hạn mức chi của một danh mục chi tiêu trong một tháng.
 *
 * @property int $category_id
 * @property Carbon $month
 * @property string $amount
 */
class Budget extends Model
{
    use BelongsToUser;

    protected $fillable = ['category_id', 'month', 'amount'];

    protected function casts(): array
    {
        return ['month' => DateOnly::class, 'amount' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
