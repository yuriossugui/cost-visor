<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'category_id' => Category::factory()->create(['user_id' => $user->id, 'type' => 'DESPESA'])->id,
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'installment_count' => fake()->numberBetween(2, 12),
            'first_installment_date' => fake()->date(),
            'description' => fake()->sentence(),
        ];
    }
}