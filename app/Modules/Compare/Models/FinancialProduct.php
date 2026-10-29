<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Core\Concerns\Auditable;
use App\Modules\Core\Concerns\DeletesReplacedFiles;
use Database\Factories\FinancialProductFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Sản phẩm tài chính (trường chung). Chi tiết theo loại nằm ở bảng riêng, ví dụ credit_card_details.
 * Quy tắc bắt buộc: chỉ xuất bản khi có nguồn và ngày xác minh không ở tương lai.
 *
 * @property int $id
 * @property ProductType $type
 * @property int $institution_id
 * @property string $name
 * @property string $slug
 * @property ProductStatus $status
 * @property ?list<string> $pros
 * @property ?list<string> $cons
 * @property ?string $image_path
 * @property ?string $source_url
 * @property ?Carbon $verified_at
 * @property ?Carbon $next_review_at
 * @property ?Carbon $published_at
 */
#[UseFactory(FinancialProductFactory::class)]
class FinancialProduct extends Model
{
    use Auditable, DeletesReplacedFiles, HasFactory;

    /** Mặc định hẹn kiểm tra lại sau 3 tháng kể từ ngày xác minh. */
    public const REVIEW_INTERVAL_MONTHS = 3;

    public const IMAGE_DIRECTORY = 'products';

    protected $fillable = [
        'type', 'institution_id', 'name', 'slug', 'status', 'summary', 'pros', 'cons', 'image_path',
        'source_url', 'source_note', 'verified_at', 'next_review_at',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'pros' => 'array',
            'cons' => 'array',
            'verified_at' => 'date',
            'next_review_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if ($product->verified_at && ! $product->next_review_at) {
                $product->next_review_at = $product->verified_at->copy()->addMonths(self::REVIEW_INTERVAL_MONTHS);
            }

            if ($product->status === ProductStatus::Published) {
                if ($errors = $product->publishingErrors()) {
                    throw ValidationException::withMessages($errors);
                }

                $product->published_at ??= now();
            }
        });
    }

    /**
     * Lý do chưa được xuất bản, theo tên trường. Rỗng = đủ điều kiện.
     *
     * @return array<string, string>
     */
    public function publishingErrors(): array
    {
        $errors = [];

        if (blank($this->source_url)) {
            $errors['source_url'] = 'Cần nguồn chính thức trước khi xuất bản.';
        }

        if (! $this->verified_at) {
            $errors['verified_at'] = 'Cần ngày xác minh trước khi xuất bản.';
        } elseif ($this->verified_at->isFuture()) {
            $errors['verified_at'] = 'Ngày xác minh không được ở tương lai.';
        }

        return $errors;
    }

    public function isDueForReview(): bool
    {
        return $this->next_review_at !== null && $this->next_review_at->lte(today());
    }

    public static function auditSubjectLabel(): string
    {
        return 'Sản phẩm';
    }

    public function fileAttributes(): array
    {
        return ['image_path'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(FinancialInstitution::class, 'institution_id');
    }

    public function creditCard(): HasOne
    {
        return $this->hasOne(CreditCardDetail::class, 'product_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    public function scopeOfType(Builder $query, ProductType $type): void
    {
        $query->where('type', $type);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
