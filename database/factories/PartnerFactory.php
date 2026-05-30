<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->company(),
            'site_web' => $this->faker->optional()->url(),
            'is_visible' => true,
            'ordre' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
