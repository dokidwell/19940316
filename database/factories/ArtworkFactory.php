<?php

namespace Database\Factories;

use App\Models\Artwork;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtworkFactory extends Factory
{
    protected $model = Artwork::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'image_url' => $this->faker->imageUrl(800, 600, 'art'),
            'thumbnail_url' => $this->faker->imageUrl(200, 200, 'art'),
            'file_path' => 'artworks/' . $this->faker->uuid() . '.jpg',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'file_type' => 'image/jpeg',
            'tags' => $this->faker->words(3),
            'is_published' => $this->faker->boolean(80),
            'published_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'likes_count' => $this->faker->numberBetween(0, 1000),
            'metadata' => [
                'width' => $this->faker->numberBetween(400, 2000),
                'height' => $this->faker->numberBetween(400, 2000),
                'color_scheme' => $this->faker->randomElement(['warm', 'cool', 'neutral']),
                'art_style' => $this->faker->randomElement(['digital', 'traditional', 'photography', 'mixed'])
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}