<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'status' => 'active',
            'organization_owner_id' => null,
        ];
    }
}
