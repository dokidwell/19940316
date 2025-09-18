<?php

namespace Database\Factories;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointTransactionFactory extends Factory
{
    protected $model = PointTransaction::class;

    public function definition(): array
    {
        $types = [
            'daily_checkin', 'whale_reward', 'governance_vote', 'proposal_creation',
            'artwork_upload', 'market_purchase', 'market_sale', 'referral_bonus',
            'admin_adjustment', 'system_reward', 'penalty', 'transfer'
        ];

        $type = $this->faker->randomElement($types);
        $isPositive = in_array($type, [
            'daily_checkin', 'whale_reward', 'artwork_upload', 'market_sale',
            'referral_bonus', 'admin_adjustment', 'system_reward', 'transfer'
        ]);

        $amount = $this->faker->randomFloat(8, 0.00000001, 1000.00000000);
        if (!$isPositive) {
            $amount = -$amount;
        }

        return [
            'user_id' => User::factory(),
            'amount' => number_format($amount, 8, '.', ''),
            'type' => $type,
            'description' => $this->generateDescription($type),
            'reference_id' => $this->faker->optional(0.6)->uuid(),
            'reference_type' => $this->faker->optional(0.6)->randomElement([
                'App\\Models\\Artwork',
                'App\\Models\\Proposal',
                'App\\Models\\WhaleAccount'
            ]),
            'metadata' => $this->generateMetadata($type),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    private function generateDescription(string $type): string
    {
        return match ($type) {
            'daily_checkin' => '每日簽到獎勵',
            'whale_reward' => '鯨探NFT持有獎勵',
            'governance_vote' => '社區治理投票',
            'proposal_creation' => '創建社區提案',
            'artwork_upload' => '上傳作品獎勵',
            'market_purchase' => '市場購買支出',
            'market_sale' => '市場銷售收入',
            'referral_bonus' => '推薦用戶獎勵',
            'admin_adjustment' => '管理員調整',
            'system_reward' => '系統獎勵',
            'penalty' => '違規處罰',
            'transfer' => '用戶轉帳',
            default => '積分交易'
        };
    }

    private function generateMetadata(string $type): array
    {
        return match ($type) {
            'whale_reward' => [
                'nft_count' => $this->faker->numberBetween(1, 50),
                'rarity_bonus' => $this->faker->randomFloat(2, 1.0, 5.0),
                'streak_days' => $this->faker->numberBetween(1, 365)
            ],
            'governance_vote' => [
                'proposal_id' => $this->faker->uuid(),
                'vote_strength' => $this->faker->numberBetween(1, 100),
                'position' => $this->faker->randomElement(['for', 'against', 'abstain'])
            ],
            'market_purchase' => [
                'artwork_id' => $this->faker->uuid(),
                'seller_id' => $this->faker->uuid(),
                'platform_fee' => $this->faker->randomFloat(8, 0, 50)
            ],
            'transfer' => [
                'from_user_id' => $this->faker->uuid(),
                'to_user_id' => $this->faker->uuid(),
                'transfer_fee' => $this->faker->randomFloat(8, 0, 5)
            ],
            default => [
                'source' => $this->faker->randomElement(['system', 'user', 'admin']),
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent()
            ]
        };
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => abs($attributes['amount']),
            'type' => $this->faker->randomElement([
                'daily_checkin', 'whale_reward', 'artwork_upload', 'system_reward'
            ])
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => -abs($attributes['amount']),
            'type' => $this->faker->randomElement([
                'governance_vote', 'market_purchase', 'penalty', 'transfer'
            ])
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}