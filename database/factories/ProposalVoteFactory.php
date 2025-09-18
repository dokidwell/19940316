<?php

namespace Database\Factories;

use App\Models\ProposalVote;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalVoteFactory extends Factory
{
    protected $model = ProposalVote::class;

    public function definition(): array
    {
        $voteStrength = $this->faker->numberBetween(1, 100);
        $pointsCost = bcpow($voteStrength, 2, 8); // 二次方投票成本

        return [
            'proposal_id' => Proposal::factory(),
            'user_id' => User::factory(),
            'position' => $this->faker->randomElement(['for', 'against', 'abstain']),
            'vote_strength' => $voteStrength,
            'points_cost' => $pointsCost,
            'justification' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'voting_method' => $this->faker->randomElement(['web', 'mobile', 'api']),
                'confidence_level' => $this->faker->randomElement(['low', 'medium', 'high'])
            ],
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function for(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'for',
            'justification' => $this->faker->paragraph() . ' 我支持這個提案。',
        ]);
    }

    public function against(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'against',
            'justification' => $this->faker->paragraph() . ' 我反對這個提案。',
        ]);
    }

    public function abstain(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'abstain',
            'justification' => $this->faker->optional(0.5)->paragraph(),
        ]);
    }

    public function highStrength(): static
    {
        return $this->state(function (array $attributes) {
            $voteStrength = $this->faker->numberBetween(50, 100);
            return [
                'vote_strength' => $voteStrength,
                'points_cost' => bcpow($voteStrength, 2, 8),
            ];
        });
    }

    public function lowStrength(): static
    {
        return $this->state(function (array $attributes) {
            $voteStrength = $this->faker->numberBetween(1, 10);
            return [
                'vote_strength' => $voteStrength,
                'points_cost' => bcpow($voteStrength, 2, 8),
            ];
        });
    }
}