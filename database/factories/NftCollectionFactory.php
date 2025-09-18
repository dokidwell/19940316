<?php

namespace Database\Factories;

use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NftCollectionFactory extends Factory
{
    protected $model = NftCollection::class;

    public function definition(): array
    {
        $rarities = ['common', 'uncommon', 'rare', 'epic', 'legendary'];
        $categories = ['art', 'collectibles', 'gaming', 'sports', 'music', 'photography', 'virtual_worlds'];

        return [
            'user_id' => User::factory(),
            'nft_id' => 'nft_' . $this->faker->uuid(),
            'name' => $this->faker->words(2, true) . ' #' . $this->faker->numberBetween(1, 9999),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'image_url' => $this->faker->imageUrl(512, 512, 'abstract'),
            'thumbnail_url' => $this->faker->imageUrl(200, 200, 'abstract'),
            'collection_id' => 'collection_' . $this->faker->randomNumber(6),
            'collection_name' => $this->faker->words(2, true) . ' Collection',
            'token_id' => $this->faker->randomNumber(8),
            'contract_address' => '0x' . $this->faker->regexify('[0-9a-f]{40}'),
            'blockchain' => $this->faker->randomElement(['ethereum', 'polygon', 'binance_smart_chain']),
            'rarity' => $this->faker->randomElement($rarities),
            'rarity_rank' => $this->faker->optional(0.7)->numberBetween(1, 10000),
            'category' => $this->faker->randomElement($categories),
            'acquisition_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'acquisition_price' => $this->faker->optional(0.6)->randomFloat(8, 0.001, 10),
            'current_floor_price' => $this->faker->optional(0.5)->randomFloat(8, 0.001, 20),
            'last_sale_price' => $this->faker->optional(0.4)->randomFloat(8, 0.001, 15),
            'estimated_value' => $this->faker->randomFloat(8, 0.001, 25),
            'last_valued_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'is_favorited' => $this->faker->boolean(20),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'attributes' => $this->generateNftAttributes(),
            'metadata' => [
                'source_platform' => $this->faker->randomElement(['opensea', 'rarible', 'superrare', 'foundation']),
                'mint_date' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                'creator_royalty' => $this->faker->randomFloat(2, 0, 10),
                'is_animated' => $this->faker->boolean(15),
                'file_format' => $this->faker->randomElement(['jpg', 'png', 'gif', 'mp4', 'svg']),
                'file_size_mb' => $this->faker->randomFloat(2, 0.1, 50)
            ],
            'synced_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    private function generateNftAttributes(): array
    {
        $attributeCount = $this->faker->numberBetween(2, 8);
        $attributes = [];

        $possibleTraits = [
            'Background' => ['Blue', 'Red', 'Green', 'Purple', 'Gold', 'Silver', 'Rainbow'],
            'Eyes' => ['Normal', 'Laser', 'Glowing', 'Closed', 'Winking', 'Cybernetic'],
            'Mouth' => ['Smile', 'Frown', 'Open', 'Tongue Out', 'Surprised'],
            'Accessory' => ['Hat', 'Glasses', 'Earrings', 'Necklace', 'None'],
            'Clothing' => ['T-Shirt', 'Hoodie', 'Suit', 'Dress', 'Armor', 'None'],
            'Special' => ['Glow', 'Sparkles', 'Shadow', 'Transparent', 'Holographic']
        ];

        $selectedTraits = $this->faker->randomElements(array_keys($possibleTraits), $attributeCount);

        foreach ($selectedTraits as $traitType) {
            $attributes[] = [
                'trait_type' => $traitType,
                'value' => $this->faker->randomElement($possibleTraits[$traitType]),
                'rarity' => $this->faker->randomFloat(2, 0.1, 50.0)
            ];
        }

        return $attributes;
    }

    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'common',
            'rarity_rank' => $this->faker->numberBetween(5000, 10000),
            'estimated_value' => $this->faker->randomFloat(8, 0.001, 1),
        ]);
    }

    public function rare(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'rare',
            'rarity_rank' => $this->faker->numberBetween(1000, 3000),
            'estimated_value' => $this->faker->randomFloat(8, 1, 10),
        ]);
    }

    public function legendary(): static
    {
        return $this->state(fn (array $attributes) => [
            'rarity' => 'legendary',
            'rarity_rank' => $this->faker->numberBetween(1, 100),
            'estimated_value' => $this->faker->randomFloat(8, 10, 100),
        ]);
    }

    public function recentlyAcquired(): static
    {
        return $this->state(fn (array $attributes) => [
            'acquisition_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'synced_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function valuable(): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_value' => $this->faker->randomFloat(8, 5, 50),
            'current_floor_price' => $this->faker->randomFloat(8, 3, 30),
            'rarity' => $this->faker->randomElement(['rare', 'epic', 'legendary']),
        ]);
    }

    public function favorited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorited' => true,
            'view_count' => $this->faker->numberBetween(100, 1000),
        ]);
    }
}