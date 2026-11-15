<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Support\InstitutionAliases;
use Illuminate\Support\Facades\Storage;

/**
 * Ghi hồ sơ ngân hàng đã duyệt (từ nguồn tự động): tạo nếu chưa có; ngân hàng đã có thì cập nhật website và logo,
 * giữ nguyên tên người quản trị đã sửa. Logo lưu trên ổ public, file cũ tự xóa khi thay (DeletesReplacedFiles).
 */
class UpdateInstitutionProfile
{
    /** @param  array{slug: string, name: string, short_name: string, website: string}  $profile */
    public function handle(array $profile, ?string $logo = null, string $extension = 'png'): FinancialInstitution
    {
        $institution = FinancialInstitution::query()->firstOrNew(['slug' => $profile['slug']]);

        if (! $institution->exists) {
            $institution->fill([
                'type' => InstitutionType::Bank,
                'name' => $profile['name'],
                'short_name' => $profile['short_name'],
                'is_active' => true,
            ]);
        }
        $institution->website = $profile['website'];
        $institution->search_aliases ??= InstitutionAliases::KNOWN[$profile['slug']] ?? null;

        if ($logo !== null) {
            $path = FinancialInstitution::LOGO_DIRECTORY.'/'.$profile['slug'].'-'.substr(sha1($logo), 0, 10).'.'.$extension;
            if ($path !== $institution->logo_path) {
                Storage::disk(FinancialInstitution::LOGO_DISK)->put($path, $logo, 'public');
                $institution->logo_path = $path;
            }
        }

        $institution->save();

        return $institution;
    }
}
