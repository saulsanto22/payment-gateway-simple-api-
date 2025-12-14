<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'order_number' => 'ORD-'.now()->format('YmdHis').'-'.strtoupper(fake()->lexify('?????')),
            'total_price' => fake()->numberBetween(50000, 500000),
            'status' => \App\Enums\OrderStatus::PENDING,
            'snap_token' => fake()->optional()->uuid(),
            'redirect_url' => fake()->optional()->url(),
        ];
    }
}
