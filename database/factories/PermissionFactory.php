<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 11,
            'date_permission' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'image' => $this->faker->imageUrl(),
            'approval_status' => $this->faker->randomElement(['Pending', 'Approved', 'Rejected']),
        ];
    }
}
