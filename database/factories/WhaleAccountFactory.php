<?php

namespace Database\Factories;

use App\Models\WhaleAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhaleAccountFactory extends Factory
{
    protected $model = WhaleAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'alipay_user_id' => 'whale_' . $this->faker->uuid(),
            'nickname' => $this->faker->userName(),
            'avatar_url' => $this->faker->imageUrl(200, 200, 'people'),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => $this->faker->dateTimeBetween('+1 hour', '+30 days'),
            'last_sync_at' => $this->faker->optional(0.8)->dateTimeBetween('-7 days', 'now'),
            'last_checkin_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 day', 'now'),
            'total_nft_count' => $this->faker->numberBetween(0, 200),
            'total_checkin_days' => $this->faker->numberBetween(0, 365),
            'total_rewards_earned' => $this->faker->randomFloat(8, 0, 10000),
            'account_status' => $this->faker->randomElement(['active', 'suspended', 'pending']),
            'metadata' => [
                'signup_source' => $this->faker->randomElement(['web', 'mobile', 'referral']),
                'verification_level' => $this->faker->randomElement(['basic', 'verified', 'premium']),
                'preferred_categories' => $this->faker->randomElements([
                    'art', 'collectibles', 'gaming', 'sports', 'music', 'photography'
                ], $this->faker->numberBetween(1, 3)),
                'risk_level' => $this->faker->randomElement(['low', 'medium', 'high'])
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'active',
            'last_sync_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'total_nft_count' => $this->faker->numberBetween(1, 200),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'suspended',
            'last_sync_at' => $this->faker->optional(0.3)->dateTimeBetween('-30 days', '-7 days'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'pending',
            'last_sync_at' => null,
            'total_nft_count' => 0,
        ]);
    }

    public function recentlyActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sync_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'last_checkin_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'account_status' => 'active',
        ]);
    }

    public function withManyNfts(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_nft_count' => $this->faker->numberBetween(50, 200),
            'total_rewards_earned' => $this->faker->randomFloat(8, 1000, 50000),
        ]);
    }

    public function longTimeUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_checkin_days' => $this->faker->numberBetween(100, 365),
            'total_rewards_earned' => $this->faker->randomFloat(8, 5000, 100000),
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }
}