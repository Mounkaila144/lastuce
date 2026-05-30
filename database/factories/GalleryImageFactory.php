<?php

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    protected $model = GalleryImage::class;

    public function definition(): array
    {
        return [
            'titre' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'is_visible' => true,
            'ordre' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
