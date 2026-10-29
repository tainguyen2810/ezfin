<?php

namespace Database\Factories;

use App\Modules\Partner\Models\OutboundLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutboundLink> */
class OutboundLinkFactory extends Factory
{
    protected $model = OutboundLink::class;

    public function definition(): array
    {
        return [
            'name' => 'Link thử nghiệm '.fake()->unique()->numberBetween(1, 99999),
            'partner' => 'Đối tác thử nghiệm',
            'destination_url' => 'https://partner.example/apply?sub='.OutboundLink::CLICK_PLACEHOLDER,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
