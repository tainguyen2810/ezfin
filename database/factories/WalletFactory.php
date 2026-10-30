<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Personal\Enums\WalletType;
use App\Modules\Personal\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Wallet> */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Tiền mặt', 'Ví chính', 'Tài khoản lương']),
            'type' => WalletType::Cash,
            'opening_balance' => fake()->numberBetween(0, 50) * 100_000,
            'include_in_total' => true,
        ];
    }
}
