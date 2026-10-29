<?php

namespace Database\Factories;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialInstitution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Chỉ dùng trong test. Tên là dữ liệu mẫu, không phải tổ chức có thật.
 *
 * @extends Factory<FinancialInstitution>
 */
class FinancialInstitutionFactory extends Factory
{
    protected $model = FinancialInstitution::class;

    public function definition(): array
    {
        $short = 'Ngân hàng thử '.Str::upper(Str::random(4));

        return [
            'type' => InstitutionType::Bank,
            'name' => $short.' (tên đầy đủ)',
            'short_name' => $short,
            'slug' => Str::slug($short),
            'website' => 'https://example.com',
            'is_active' => true,
        ];
    }
}
