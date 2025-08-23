<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'full_name' => $this->faker->name(),
            'dob' => $this->faker->date(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->unique()->numerify('0#########'),
            'email' => $this->faker->unique()->safeEmail(),
            'organization_id' => null,
            'collaborator_id' => null,
            'target_university' => $this->faker->company(),
            'major' => $this->faker->randomElement(['Công nghệ thông tin', 'Quản trị kinh doanh', 'Kế toán']),
            'source' => 'ref',
            'status' => 'new',
            'notes' => null,
        ];
    }
}
