<?php

namespace Database\Factories;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsRate>
 */
class SavingsRateFactory extends Factory
{
    protected $model = SavingsRate::class;

    public function definition(): array
    {
        return [
            'product_id' => FinancialProduct::factory()->state(['type' => ProductType::Savings]),
            'term_months' => 12,
            'channel' => SavingsChannel::Online,
            'interest_payment' => InterestPayment::EndOfTerm,
            'min_amount' => 0,
            'max_amount' => null,
            'rate' => 5.2,
            'effective_from' => today()->subDays(10),
        ];
    }
}
