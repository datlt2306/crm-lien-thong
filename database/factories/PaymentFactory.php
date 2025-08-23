<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'organization_id' => null,
            'student_id' => null,
            'primary_collaborator_id' => null,
            'sub_collaborator_id' => null,
            'program_type' => $this->faker->randomElement(['REGULAR', 'PART_TIME']),
            'amount' => $this->faker->numberBetween(1000000, 5000000),
            'bill_path' => null,
            'status' => 'SUBMITTED',
        ];
    }
}
