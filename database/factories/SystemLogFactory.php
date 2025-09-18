<?php

namespace Database\Factories;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemLogFactory extends Factory
{
    protected $model = SystemLog::class;

    public function definition(): array
    {
        $actions = [
            'user_login', 'user_logout', 'user_register', 'points_transfer',
            'proposal_created', 'proposal_voted', 'artwork_uploaded', 'whale_sync',
            'admin_action', 'system_maintenance', 'security_alert', 'api_call'
        ];

        $levels = ['info', 'warning', 'error', 'critical', 'debug'];

        return [
            'user_id' => $this->faker->optional(0.8)->randomElement([null, User::factory()]),
            'action' => $this->faker->randomElement($actions),
            'description' => $this->faker->sentence(),
            'level' => $this->faker->randomElement($levels),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'request_data' => $this->generateRequestData(),
            'response_data' => $this->faker->optional(0.6)->randomElements([
                'status' => 'success',
                'code' => 200,
                'message' => 'Operation completed'
            ]),
            'execution_time' => $this->faker->randomFloat(3, 0.001, 5.000),
            'metadata' => [
                'module' => $this->faker->randomElement(['auth', 'points', 'governance', 'whale', 'marketplace']),
                'environment' => $this->faker->randomElement(['production', 'staging', 'development']),
                'server_id' => $this->faker->randomElement(['web-01', 'web-02', 'api-01']),
                'session_id' => $this->faker->uuid()
            ],
            'created_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }

    private function generateRequestData(): ?array
    {
        if ($this->faker->boolean(30)) {
            return null;
        }

        return [
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'url' => $this->faker->url(),
            'parameters' => $this->faker->optional(0.7)->randomElements([
                'id' => $this->faker->numberBetween(1, 1000),
                'type' => $this->faker->word(),
                'amount' => $this->faker->randomFloat(8, 0, 1000)
            ])
        ];
    }

    public function userAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['user_login', 'points_transfer', 'proposal_voted']),
            'level' => 'info',
        ]);
    }

    public function systemAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'action' => $this->faker->randomElement(['system_maintenance', 'whale_sync', 'api_call']),
            'level' => 'info',
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'error',
            'action' => $this->faker->randomElement(['api_call', 'whale_sync', 'points_transfer']),
            'description' => 'Error: ' . $this->faker->sentence(),
            'response_data' => [
                'status' => 'error',
                'code' => $this->faker->randomElement([400, 401, 403, 404, 500]),
                'message' => $this->faker->sentence()
            ]
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 'critical',
            'action' => 'security_alert',
            'description' => 'Critical security event: ' . $this->faker->sentence(),
            'execution_time' => null,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}