<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Core\Concerns\Auditable;
use App\Modules\Core\Concerns\DeletesReplacedFiles;
use Database\Factories\FinancialInstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Ngân hàng hoặc công ty tài chính phát hành sản phẩm.
 *
 * @property int $id
 * @property InstitutionType $type
 * @property string $name
 * @property string $short_name
 * @property string $slug
 * @property ?string $logo_path
 * @property ?string $website
 * @property bool $is_active
 */
#[UseFactory(FinancialInstitutionFactory::class)]
class FinancialInstitution extends Model
{
    use Auditable, DeletesReplacedFiles, HasFactory;

    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'institutions';

    protected $fillable = ['type', 'name', 'short_name', 'slug', 'logo_path', 'website', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => InstitutionType::class,
            'is_active' => 'boolean',
        ];
    }

    public function fileAttributes(): array
    {
        return ['logo_path'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(FinancialProduct::class, 'institution_id');
    }

    public static function auditSubjectLabel(): string
    {
        return 'Tổ chức tài chính';
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk(self::LOGO_DISK)->url($this->logo_path) : null;
    }
}
