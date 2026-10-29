<?php

namespace App\Modules\Content\Models;

use App\Models\User;
use App\Modules\Content\Support\ArticleHtml;
use App\Modules\Core\Concerns\Auditable;
use App\Modules\Core\Concerns\DeletesReplacedFiles;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Tác giả hiển thị trên bài viết và trang /tac-gia/{slug}. Có thể gắn với tài khoản quản trị.
 *
 * @property int $id
 * @property ?int $user_id
 * @property string $name
 * @property string $slug
 * @property ?string $job_title
 * @property ?string $expertise
 * @property ?string $bio
 * @property ?string $avatar_path
 * @property ?list<string> $links
 * @property bool $is_active
 */
#[UseFactory(AuthorFactory::class)]
class Author extends Model
{
    use Auditable, DeletesReplacedFiles, HasFactory;

    public const AVATAR_DIRECTORY = 'authors';

    protected $fillable = ['user_id', 'name', 'slug', 'job_title', 'expertise', 'bio', 'avatar_path', 'links', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Liên kết hồ sơ: chỉ nhận http(s). */
    protected function links(): Attribute
    {
        return Attribute::make(set: function (?array $value) {
            $links = array_values(array_filter(array_map(fn ($url) => ArticleHtml::safeUrl(is_string($url) ? $url : null), $value ?? [])));

            return $links === [] ? null : json_encode($links, JSON_UNESCAPED_SLASHES);
        });
    }

    public static function auditSubjectLabel(): string
    {
        return 'Tác giả';
    }

    public function fileAttributes(): array
    {
        return ['avatar_path'];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** Chữ cái đầu của tên (tên tiếng Việt đứng cuối), dùng khi chưa có ảnh. */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name)) ?: [];

        return mb_strtoupper(mb_substr((string) end($words), 0, 1));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
