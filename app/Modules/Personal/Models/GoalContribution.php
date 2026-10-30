<?php

namespace App\Modules\Personal\Models;

use App\Modules\Personal\Casts\DateOnly;
use App\Modules\Personal\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một lần góp (+) hoặc rút (−) tiền cho mục tiêu. */
class GoalContribution extends Model
{
    use BelongsToUser;

    protected $fillable = ['goal_id', 'amount', 'contributed_on', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'contributed_on' => DateOnly::class];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
