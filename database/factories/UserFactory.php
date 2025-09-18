<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hoho_id' => 'HOHO' . str_pad(fake()->unique()->numberBetween(100000, 999999), 6, '0', STR_PAD_LEFT),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'phone_verified_at' => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'password' => static::$password ??= Hash::make('password'),
            'nickname' => fake()->optional(0.8)->userName(),
            'avatar_url' => fake()->optional(0.5)->imageUrl(200, 200, 'people'),
            'avatar_source' => fake()->randomElement(['default', 'nft', 'whale']),
            'status' => fake()->randomElement(['active', 'banned', 'pending']),
            'role' => fake()->randomElement(['user', 'admin']),
            'points_balance' => fake()->randomFloat(6, 0, 10000),
            'last_checkin_at' => fake()->optional(0.4)->dateTimeBetween('-30 days', 'now'),
            'notification_settings' => [
                'email_notifications' => fake()->boolean(),
                'push_notifications' => fake()->boolean(),
                'sms_notifications' => fake()->boolean()
            ],
            'referral_code' => fake()->optional(0.3)->regexify('[A-Z0-9]{8}'),
            'referred_by' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
