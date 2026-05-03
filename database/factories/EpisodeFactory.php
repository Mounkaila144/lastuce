<?php

namespace Database\Factories;

use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Episode>
 */
class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        $titre = $this->faker->sentence(4, true);
        // La colonne type a un CHECK constraint 'episode'|'coulisse'|'bonus'.
        // Le type 'special' est défini dans le modèle mais pas encore dans
        // le schéma (migration à planifier).
        $types = [Episode::TYPE_EPISODE, Episode::TYPE_COULISSE, Episode::TYPE_BONUS];

        $youtubeUrls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=oHg5SJYRHA0',
            'https://www.youtube.com/watch?v=SQoA_wjmE9w',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=J9FImc2LOr8',
        ];

        return [
            'titre' => $titre,
            'description' => $this->faker->paragraphs(3, true),
            'youtube_url' => $this->faker->optional(0.8)->randomElement($youtubeUrls),
            'type' => $this->faker->randomElement($types),
            'statut' => $this->faker->randomElement(array_keys(Episode::STATUTS)),
            'date_publication' => $this->faker->optional(0.9)->dateTimeBetween('-2 years', '+6 months'),
            'slug' => Str::slug($titre) . '-' . $this->faker->unique()->randomNumber(3),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Episode::STATUT_PUBLIE,
            'date_publication' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Episode::STATUT_BROUILLON,
            'date_publication' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Episode::STATUT_PROGRAMME,
            'date_publication' => $this->faker->dateTimeBetween('+1 day', '+3 months'),
        ]);
    }

    public function episode(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Episode::TYPE_EPISODE,
        ]);
    }

    public function coulisse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Episode::TYPE_COULISSE,
        ]);
    }

    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Episode::TYPE_BONUS,
        ]);
    }

    public function withoutYoutube(): static
    {
        return $this->state(fn (array $attributes) => [
            'youtube_url' => null,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => Episode::STATUT_PUBLIE,
            'date_publication' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
