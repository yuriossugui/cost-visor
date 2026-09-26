<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'category_id' => Category::factory()->create(['user_id' => $user->id])->id,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->sentence(),
            'date' => fake()->date(),
            'type' => fake()->randomElement(['RECEITA', 'DESPESA']),
        ];
    }
}