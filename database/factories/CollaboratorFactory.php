<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collaborator>
 */
class CollaboratorFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->unique()->numerify('0#########'),
            'email' => $this->faker->unique()->safeEmail(),
            'organization_id' => null,
            'ref_id' => $this->faker->unique()->regexify('[A-Z]{8}'),
            'upline_id' => null,
            'note' => null,
            'status' => 'active',
        ];
    }
}
