<?php

namespace App\Modules\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Bản lưu nội dung bài viết sau mỗi lần sửa. Chỉ thêm, không sửa, không xóa (trừ khi xóa hẳn bài).
 *
 * @property int $id
 * @property int $article_id
 * @property ?int $user_id
 * @property string $title
 * @property ?string $excerpt
 * @property ?string $body
 * @property ?list<array{title: string, url: ?string}> $references
 */
class ArticleRevision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'references' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Không được sửa lịch sử bài viết.'));
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
