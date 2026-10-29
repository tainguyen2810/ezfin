<?php

namespace App\Modules\Compare\Models;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Core\Concerns\Auditable;
use Database\Factories\FinancialInstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    use Auditable, HasFactory;

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

    protected static function booted(): void
    {
        // Không để file logo mồ côi khi thay hoặc xóa logo.
        static::updated(function (self $institution) {
            if ($institution->wasChanged('logo_path')) {
                self::deleteLogo($institution->getOriginal('logo_path'));
            }
        });

        static::deleted(fn (self $institution) => self::deleteLogo($institution->logo_path));
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

    private static function deleteLogo(?string $path): void
    {
        if ($path) {
            Storage::disk(self::LOGO_DISK)->delete($path);
        }
    }
}
