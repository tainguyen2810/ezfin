<?php

namespace Database\Factories;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Chỉ dùng trong test. Không phải sản phẩm có thật.
 *
 * @extends Factory<FinancialProduct>
 */
class FinancialProductFactory extends Factory
{
    protected $model = FinancialProduct::class;

    public function definition(): array
    {
        $name = 'Thẻ thử nghiệm '.Str::upper(Str::random(5));

        return [
            'type' => ProductType::CreditCard,
            'institution_id' => FinancialInstitution::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => ProductStatus::Draft,
        ];
    }

    public function savings(): static
    {
        return $this->state(function () {
            $name = 'Tiết kiệm thử nghiệm '.Str::upper(Str::random(5));

            return ['type' => ProductType::Savings, 'name' => $name, 'slug' => Str::slug($name)];
        });
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'source_url' => 'https://example.com/bieu-phi',
            'verified_at' => today()->subDays(3),
        ]);
    }

    public function published(): static
    {
        return $this->verified()->state(fn () => ['status' => ProductStatus::Published]);
    }
}
