<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Concerns\Auditable;
use App\Modules\Core\Concerns\DeletesReplacedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Ghi đè SEO của một trang: tiêu đề, mô tả, ảnh chia sẻ, không cho lập chỉ mục.
 * Trường để trống thì trang dùng giá trị tự sinh.
 *
 * @property ?string $title
 * @property ?string $description
 * @property ?string $og_image_path
 * @property bool $noindex
 */
class SeoMeta extends Model
{
    use Auditable, DeletesReplacedFiles;

    public const IMAGE_DIRECTORY = 'seo';

    protected $table = 'seo_meta';

    protected $fillable = ['title', 'description', 'og_image_path', 'noindex'];

    protected $attributes = ['noindex' => false];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    public static function auditSubjectLabel(): string
    {
        return 'SEO';
    }

    public function fileAttributes(): array
    {
        return ['og_image_path'];
    }

    public function ogImageUrl(): ?string
    {
        return $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null;
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
