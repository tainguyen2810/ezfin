<?php

namespace App\Modules\Content\Models;

use App\Models\User;
use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Support\ArticleHtml;
use App\Modules\Core\Concerns\Auditable;
use Database\Factories\ArticleFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Bài viết kiến thức. Quy tắc bắt buộc:
 * - Nội dung HTML được lọc XSS ngay khi gán (mọi đường ghi, không chỉ form quản trị).
 * - Chỉ người có quyền `publish` (quản trị cao nhất) được xuất bản, gỡ hoặc sửa bài đã xuất bản;
 *   biên tập viên gửi duyệt. Kiểm tra cả ở đây, không chỉ ở nút bấm.
 * - Mỗi lần đổi tiêu đề, tóm tắt, nội dung hoặc nguồn tham khảo được lưu thành một bản trong lịch sử.
 *
 * @property int $id
 * @property int $category_id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property ?string $body
 * @property ?list<array{title: string, url: ?string}> $references
 * @property ?string $cover_path
 * @property ArticleStatus $status
 * @property ?string $review_note
 * @property ?Carbon $submitted_at
 * @property ?Carbon $published_at
 * @property ?Carbon $content_updated_at
 * @property ?int $created_by
 * @property ?int $reviewed_by
 */
#[UseFactory(ArticleFactory::class)]
class Article extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    public const COVER_DIRECTORY = 'articles';

    /** Trường được lưu vào lịch sử sửa. */
    public const REVISIONED = ['title', 'excerpt', 'body', 'references'];

    protected $fillable = ['category_id', 'author_id', 'title', 'slug', 'excerpt', 'body', 'references', 'cover_path'];

    protected $attributes = ['status' => 'draft'];

    /** Nội dung dài đã có trong lịch sử bài viết, không chép vào audit log. */
    protected array $auditExclude = ['body'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'references' => 'array',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
        ];
    }

    protected function body(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => ArticleHtml::sanitize($value));
    }

    /** Nguồn tham khảo: bỏ dòng trống; liên kết chỉ nhận http(s), tránh javascript: trong href. */
    protected function references(): Attribute
    {
        return Attribute::make(set: function (?array $value) {
            $references = collect($value ?? [])
                ->map(fn ($item) => [
                    'title' => trim((string) ($item['title'] ?? '')),
                    'url' => ArticleHtml::safeUrl($item['url'] ?? null),
                ])
                ->filter(fn (array $item) => $item['title'] !== '')
                ->values()
                ->all();

            return $references === [] ? null : json_encode($references, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
    }

    protected static function booted(): void
    {
        static::saving(function (self $article) {
            $article->guardPublishing();

            if ($article->status === ArticleStatus::Published) {
                if ($errors = $article->publishingErrors()) {
                    throw ValidationException::withMessages($errors);
                }

                $article->published_at ??= now();
            }

            if (! $article->exists || $article->isDirty(self::REVISIONED)) {
                $article->content_updated_at = now();
            }
        });

        static::creating(function (self $article) {
            $article->created_by ??= auth()->id();
        });

        static::created(fn (self $article) => $article->recordRevision());

        static::updated(function (self $article) {
            if ($article->wasChanged(self::REVISIONED)) {
                $article->recordRevision();
            }

            if ($article->wasChanged('cover_path') && ($old = $article->getOriginal('cover_path'))) {
                Storage::disk('public')->delete($old);
            }
        });

        static::forceDeleted(function (self $article) {
            if ($article->cover_path) {
                Storage::disk('public')->delete($article->cover_path);
            }
        });
    }

    private function recordRevision(): void
    {
        $this->revisions()->create([
            'user_id' => auth()->id(),
            ...$this->only(self::REVISIONED),
        ]);
    }

    /**
     * Người không có quyền duyệt không được đưa bài lên trang, gỡ bài, hay sửa bài đang hiển thị.
     * Chạy không đăng nhập (lệnh console, queue) thì bỏ qua.
     */
    private function guardPublishing(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $wasPublished = $this->exists && $this->getOriginal('status') === ArticleStatus::Published;
        $becomesPublished = $this->status === ArticleStatus::Published && ! $wasPublished;

        if (($becomesPublished || ($wasPublished && $this->isDirty())) && Gate::forUser($user)->denies('publish', $this)) {
            throw new AuthorizationException('Chỉ người duyệt bài mới được xuất bản hoặc sửa bài đã xuất bản.');
        }
    }

    /**
     * Lý do chưa xuất bản được, theo tên trường. Rỗng = đủ điều kiện.
     *
     * @return array<string, string>
     */
    public function publishingErrors(): array
    {
        $errors = [];

        if (blank($this->excerpt)) {
            $errors['excerpt'] = 'Cần tóm tắt trước khi xuất bản.';
        }

        if (ArticleHtml::text($this->body) === '') {
            $errors['body'] = 'Bài viết chưa có nội dung.';
        }

        return $errors;
    }

    public function isPublic(): bool
    {
        return $this->status === ArticleStatus::Published && ! $this->trashed()
            && $this->published_at !== null && $this->published_at->lte(now());
    }

    /** Ngày cập nhật đáng hiển thị: nội dung đổi sau ngày xuất bản. */
    public function updatedAfterPublishing(): ?Carbon
    {
        return $this->published_at && $this->content_updated_at?->gt($this->published_at->copy()->endOfDay())
            ? $this->content_updated_at
            : null;
    }

    public function readingMinutes(): int
    {
        return ArticleHtml::readingMinutes($this->body);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function url(): string
    {
        return route('content.articles.show', [$this->category->slug, $this->slug]);
    }

    public static function auditSubjectLabel(): string
    {
        return 'Bài viết';
    }

    /** Đã xuất bản và đến giờ hiển thị (bài hẹn giờ chưa hiện). */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)->where('published_at', '<=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->latest('id');
    }
}
