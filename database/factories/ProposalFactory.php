<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        $categories = [
            'platform_improvement', 'feature_request', 'community_rule',
            'economic_policy', 'governance_change', 'other'
        ];

        $statuses = ['draft', 'active', 'completed', 'rejected'];

        $votingStartAt = $this->faker->dateTimeBetween('-30 days', '+7 days');
        $votingEndAt = $this->faker->dateTimeBetween($votingStartAt, '+14 days');

        return [
            'creator_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'category' => $this->faker->randomElement($categories),
            'status' => $this->faker->randomElement($statuses),
            'voting_start_at' => $votingStartAt,
            'voting_end_at' => $votingEndAt,
            'min_points_to_vote' => $this->faker->randomFloat(8, 1, 1000),
            'vote_count_for' => $this->faker->numberBetween(0, 1000),
            'vote_count_against' => $this->faker->numberBetween(0, 500),
            'vote_count_abstain' => $this->faker->numberBetween(0, 200),
            'total_vote_power' => $this->faker->numberBetween(0, 50000),
            'total_points_spent' => $this->faker->randomFloat(8, 0, 10000),
            'result' => $this->faker->optional(0.3)->randomElement(['approved', 'rejected']),
            'approved_by' => $this->faker->optional(0.4)->numberBetween(1, 10),
            'approved_at' => $this->faker->optional(0.4)->dateTimeBetween('-30 days', 'now'),
            'rejection_reason' => $this->faker->optional(0.1)->sentence(),
            'metadata' => [
                'creator_ip' => $this->faker->ipv4(),
                'creator_user_agent' => $this->faker->userAgent(),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'estimated_impact' => $this->faker->randomElement(['minor', 'moderate', 'major']),
                'implementation_complexity' => $this->faker->randomElement(['simple', 'medium', 'complex'])
            ],
            'created_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'voting_start_at' => $this->faker->dateTimeBetween('-7 days', '-1 hour'),
            'voting_end_at' => $this->faker->dateTimeBetween('+1 hour', '+7 days'),
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-10 days', '-1 hour'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'result' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'voting_start_at' => $this->faker->dateTimeBetween('-30 days', '-8 days'),
            'voting_end_at' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
            'result' => $this->faker->randomElement(['approved', 'rejected']),
            'vote_count_for' => $this->faker->numberBetween(100, 2000),
            'vote_count_against' => $this->faker->numberBetween(50, 1000),
            'total_vote_power' => $this->faker->numberBetween(1000, 100000),
            'total_points_spent' => $this->faker->randomFloat(8, 1000, 50000),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function platformImprovement(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'platform_improvement',
            'title' => 'Platform Enhancement: ' . $this->faker->words(3, true),
        ]);
    }

    public function featureRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'feature_request',
            'title' => 'New Feature: ' . $this->faker->words(3, true),
        ]);
    }
}