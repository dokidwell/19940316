<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhalePricingService
{
    protected $pricingConfig;
    protected $marketDataService;

    public function __construct()
    {
        $this->pricingConfig = $this->loadPricingConfig();
        $this->marketDataService = app(WhaleMarketDataService::class);
    }

    protected function loadPricingConfig()
    {
        return Cache::remember('whale_pricing_config', 3600, function () {
            return [
                'base_multipliers' => [
                    'common' => 1.0,
                    'uncommon' => 1.2,
                    'rare' => 1.5,
                    'epic' => 2.0,
                    'legendary' => 3.0,
                    'mythic' => 5.0,
                ],
                'time_decay_factor' => 0.8,
                'floor_price_weight' => 0.4,
                'original_price_weight' => 0.3,
                'rarity_weight' => 0.2,
                'time_weight' => 0.1,
                'max_daily_reward' => 100.00000000,
                'min_reward' => 0.00010000,
                'volatility_protection' => [
                    'max_increase_rate' => 1.5,
                    'max_decrease_rate' => 0.5,
                    'smoothing_window' => 7,
                ],
                'hoho_collections' => [
                    'collection_ids' => [
                        'hoho_genesis_001',
                        'hoho_genesis_002',
                        'hoho_special_001',
                        'hoho_collab_001',
                        'hoho_collab_002',
                        'hoho_collab_003',
                        'hoho_collab_004',
                    ],
                    'premium_multiplier' => 2.0,
                ],
            ];
        });
    }

    public function calculateNftValue($collection)
    {
        try {
            $collectionId = $collection['collection_id'] ?? null;
            $isHohoCollection = $this->isHohoCollection($collectionId);

            $metrics = $this->gatherCollectionMetrics($collection);

            $baseValue = $this->calculateBaseValue($metrics);

            $rarityMultiplier = $this->getRarityMultiplier($metrics['rarity']);

            $timeDecayFactor = $this->calculateTimeDecayFactor($metrics['issue_date']);

            $marketMultiplier = $this->calculateMarketMultiplier($metrics);

            $volatilityAdjustment = $this->applyVolatilityProtection($collectionId, $baseValue);

            $finalValue = $baseValue *
                         $rarityMultiplier *
                         $timeDecayFactor *
                         $marketMultiplier *
                         $volatilityAdjustment;

            if ($isHohoCollection) {
                $finalValue *= $this->pricingConfig['hoho_collections']['premium_multiplier'];
            }

            $finalValue = max($this->pricingConfig['min_reward'], $finalValue);
            $finalValue = min($this->pricingConfig['max_daily_reward'], $finalValue);

            $this->logPricingCalculation($collectionId, [
                'base_value' => $baseValue,
                'rarity_multiplier' => $rarityMultiplier,
                'time_decay_factor' => $timeDecayFactor,
                'market_multiplier' => $marketMultiplier,
                'volatility_adjustment' => $volatilityAdjustment,
                'is_hoho_collection' => $isHohoCollection,
                'final_value' => $finalValue,
                'metrics' => $metrics,
            ]);

            return round($finalValue, 8);

        } catch (\Exception $e) {
            Log::error('NFT价值计算失败', [
                'collection' => $collection,
                'error' => $e->getMessage()
            ]);

            return $this->pricingConfig['min_reward'];
        }
    }

    protected function gatherCollectionMetrics($collection)
    {
        $collectionId = $collection['collection_id'] ?? null;

        return [
            'collection_id' => $collectionId,
            'name' => $collection['collection_name'] ?? '未知藏品',
            'original_price' => $this->parsePrice($collection['original_price'] ?? 0),
            'current_floor_price' => $this->marketDataService->getFloorPrice($collectionId),
            'total_supply' => $collection['total_count'] ?? 1000,
            'issue_date' => $this->parseDate($collection['issue_time'] ?? now()),
            'rarity' => $this->determineRarity($collection),
            'market_cap' => $this->marketDataService->getMarketCap($collectionId),
            'trading_volume_24h' => $this->marketDataService->getTradingVolume24h($collectionId),
            'holder_count' => $this->marketDataService->getHolderCount($collectionId),
        ];
    }

    protected function calculateBaseValue($metrics)
    {
        $floorPriceComponent = $metrics['current_floor_price'] * $this->pricingConfig['floor_price_weight'];
        $originalPriceComponent = $metrics['original_price'] * $this->pricingConfig['original_price_weight'];

        $scarcityFactor = max(0.1, 1000 / max(1, $metrics['total_supply']));

        return ($floorPriceComponent + $originalPriceComponent) * $scarcityFactor * 0.01;
    }

    protected function getRarityMultiplier($rarity)
    {
        return $this->pricingConfig['base_multipliers'][$rarity] ?? 1.0;
    }

    protected function calculateTimeDecayFactor($issueDate)
    {
        $daysSinceIssue = now()->diffInDays($issueDate);

        $decayRate = $this->pricingConfig['time_decay_factor'];
        $weightFactor = $this->pricingConfig['time_weight'];

        $baseFactor = 1.0;
        if ($daysSinceIssue > 30) {
            $monthsSinceIssue = $daysSinceIssue / 30;
            $baseFactor = pow($decayRate, $monthsSinceIssue * $weightFactor);
        }

        return max(0.5, $baseFactor);
    }

    protected function calculateMarketMultiplier($metrics)
    {
        $volumeMultiplier = 1.0;
        if ($metrics['trading_volume_24h'] > 0) {
            $volumeMultiplier = min(2.0, 1.0 + ($metrics['trading_volume_24h'] / 10000));
        }

        $holderMultiplier = 1.0;
        if ($metrics['holder_count'] > 0) {
            $holderMultiplier = min(1.5, 1.0 + ($metrics['holder_count'] / 1000 * 0.1));
        }

        return ($volumeMultiplier + $holderMultiplier) / 2;
    }

    protected function applyVolatilityProtection($collectionId, $currentValue)
    {
        if (!$collectionId) return 1.0;

        $cacheKey = "pricing_history_{$collectionId}";
        $history = Cache::get($cacheKey, []);

        if (empty($history)) {
            $history[] = [
                'value' => $currentValue,
                'date' => now()->toDateString(),
            ];
            Cache::put($cacheKey, $history, 86400 * 30);
            return 1.0;
        }

        $smoothingWindow = $this->pricingConfig['volatility_protection']['smoothing_window'];
        $recentHistory = array_slice($history, -$smoothingWindow);
        $avgRecentValue = array_sum(array_column($recentHistory, 'value')) / count($recentHistory);

        $lastValue = end($history)['value'];
        $changeRate = $lastValue > 0 ? $currentValue / $lastValue : 1.0;

        $maxIncrease = $this->pricingConfig['volatility_protection']['max_increase_rate'];
        $maxDecrease = $this->pricingConfig['volatility_protection']['max_decrease_rate'];

        if ($changeRate > $maxIncrease) {
            $adjustmentFactor = $maxIncrease / $changeRate;
        } elseif ($changeRate < $maxDecrease) {
            $adjustmentFactor = $maxDecrease / $changeRate;
        } else {
            $adjustmentFactor = 1.0;
        }

        $history[] = [
            'value' => $currentValue * $adjustmentFactor,
            'date' => now()->toDateString(),
        ];

        $history = array_slice($history, -30);
        Cache::put($cacheKey, $history, 86400 * 30);

        return $adjustmentFactor;
    }

    protected function isHohoCollection($collectionId)
    {
        return in_array($collectionId, $this->pricingConfig['hoho_collections']['collection_ids']);
    }

    protected function determineRarity($collection)
    {
        $totalSupply = $collection['total_count'] ?? 1000;

        if ($totalSupply <= 100) return 'legendary';
        if ($totalSupply <= 500) return 'epic';
        if ($totalSupply <= 1000) return 'rare';
        if ($totalSupply <= 5000) return 'uncommon';

        return 'common';
    }

    protected function parsePrice($priceString)
    {
        if (is_numeric($priceString)) {
            return (float) $priceString;
        }

        $price = preg_replace('/[^\d.]/', '', $priceString);
        return (float) $price ?: 0;
    }

    protected function parseDate($dateString)
    {
        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }

    protected function logPricingCalculation($collectionId, $data)
    {
        SystemLog::logUserAction(
            'whale_pricing_calculation',
            "藏品定价计算: {$collectionId}",
            $data
        );
    }

    public function updatePricingConfig($newConfig)
    {
        try {
            $this->validatePricingConfig($newConfig);

            $this->pricingConfig = array_merge($this->pricingConfig, $newConfig);

            Cache::put('whale_pricing_config', $this->pricingConfig, 3600);

            SystemLog::logUserAction(
                'whale_pricing_update',
                '鲸探定价配置已更新',
                [
                    'old_config' => Cache::get('whale_pricing_config_backup'),
                    'new_config' => $newConfig,
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error('更新定价配置失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function validatePricingConfig($config)
    {
        $requiredKeys = ['base_multipliers', 'volatility_protection'];

        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("缺少必需的配置项: {$key}");
            }
        }

        if (isset($config['max_daily_reward']) && $config['max_daily_reward'] <= 0) {
            throw new \InvalidArgumentException('每日最大奖励必须大于0');
        }

        if (isset($config['min_reward']) && $config['min_reward'] < 0) {
            throw new \InvalidArgumentException('最小奖励不能小于0');
        }
    }

    public function getPricingConfig()
    {
        return $this->pricingConfig;
    }

    public function getCollectionPriceHistory($collectionId, $days = 30)
    {
        $cacheKey = "pricing_history_{$collectionId}";
        $history = Cache::get($cacheKey, []);

        return array_slice($history, -$days);
    }

    public function calculateBatchRewards($collections)
    {
        $totalReward = 0;
        $rewards = [];

        foreach ($collections as $collection) {
            $reward = $this->calculateNftValue($collection);
            $rewards[] = [
                'collection_id' => $collection['collection_id'] ?? null,
                'name' => $collection['collection_name'] ?? '未知藏品',
                'reward' => $reward,
            ];
            $totalReward += $reward;
        }

        if ($totalReward > $this->pricingConfig['max_daily_reward']) {
            $scaleFactor = $this->pricingConfig['max_daily_reward'] / $totalReward;
            foreach ($rewards as &$reward) {
                $reward['reward'] *= $scaleFactor;
                $reward['scaled'] = true;
            }
            $totalReward = $this->pricingConfig['max_daily_reward'];
        }

        return [
            'individual_rewards' => $rewards,
            'total_reward' => round($totalReward, 8),
            'scaled' => $totalReward >= $this->pricingConfig['max_daily_reward'],
        ];
    }
}