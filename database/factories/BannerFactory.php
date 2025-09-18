<?php

namespace Database\Factories;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'image_url' => $this->faker->imageUrl(1200, 400, 'business'),
            'link_url' => $this->faker->optional(0.7)->url(),
            'button_text' => $this->faker->optional(0.6)->words(2, true),
            'position' => $this->faker->randomElement(['home_hero', 'home_secondary', 'sidebar', 'footer']),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(80),
            'start_date' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', '+7 days'),
            'end_date' => $this->faker->optional(0.5)->dateTimeBetween('+7 days', '+60 days'),
            'target_audience' => $this->faker->optional(0.6)->randomElement(['all', 'new_users', 'active_users', 'whale_users']),
            'click_count' => $this->faker->numberBetween(0, 10000),
            'impression_count' => $this->faker->numberBetween(0, 100000),
            'created_by' => User::factory(),
            'metadata' => [
                'campaign_id' => $this->faker->optional(0.5)->uuid(),
                'banner_type' => $this->faker->randomElement(['promotional', 'informational', 'announcement']),
                'color_scheme' => $this->faker->randomElement(['blue', 'red', 'green', 'purple', 'orange']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high'])
            ],
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'start_date' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'end_date' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function homeHero(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'home_hero',
            'image_url' => $this->faker->imageUrl(1920, 600, 'business'),
            'sort_order' => $this->faker->numberBetween(1, 5),
        ]);
    }

    public function sidebar(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'sidebar',
            'image_url' => $this->faker->imageUrl(300, 250, 'business'),
        ]);
    }
}