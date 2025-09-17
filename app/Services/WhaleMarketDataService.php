<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhaleMarketDataService
{
    protected $marketApiUrl;
    protected $apiKey;
    protected $cacheTimeout;

    public function __construct()
    {
        $this->marketApiUrl = config('services.whale.market_api_url');
        $this->apiKey = config('services.whale.market_api_key');
        $this->cacheTimeout = config('services.whale.market_cache_timeout', 1800);
    }

    public function getFloorPrice($collectionId)
    {
        if (!$collectionId) return 0;

        $cacheKey = "whale_floor_price_{$collectionId}";

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($collectionId) {
            try {
                $response = $this->makeMarketApiRequest('collection/floor-price', [
                    'collection_id' => $collectionId,
                ]);

                return $response['floor_price'] ?? $this->getEstimatedFloorPrice($collectionId);

            } catch (\Exception $e) {
                Log::warning('获取地板价失败，使用估算价格', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);

                return $this->getEstimatedFloorPrice($collectionId);
            }
        });
    }

    public function getMarketCap($collectionId)
    {
        if (!$collectionId) return 0;

        $cacheKey = "whale_market_cap_{$collectionId}";

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($collectionId) {
            try {
                $response = $this->makeMarketApiRequest('collection/market-cap', [
                    'collection_id' => $collectionId,
                ]);

                return $response['market_cap'] ?? 0;

            } catch (\Exception $e) {
                Log::warning('获取市值失败', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);

                return 0;
            }
        });
    }

    public function getTradingVolume24h($collectionId)
    {
        if (!$collectionId) return 0;

        $cacheKey = "whale_volume_24h_{$collectionId}";

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($collectionId) {
            try {
                $response = $this->makeMarketApiRequest('collection/volume', [
                    'collection_id' => $collectionId,
                    'period' => '24h',
                ]);

                return $response['volume'] ?? 0;

            } catch (\Exception $e) {
                Log::warning('获取24小时交易量失败', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);

                return 0;
            }
        });
    }

    public function getHolderCount($collectionId)
    {
        if (!$collectionId) return 0;

        $cacheKey = "whale_holder_count_{$collectionId}";

        return Cache::remember($cacheKey, $this->cacheTimeout * 2, function () use ($collectionId) {
            try {
                $response = $this->makeMarketApiRequest('collection/holders', [
                    'collection_id' => $collectionId,
                ]);

                return $response['holder_count'] ?? 0;

            } catch (\Exception $e) {
                Log::warning('获取持有者数量失败', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);

                return 0;
            }
        });
    }

    public function getCollectionStats($collectionId)
    {
        if (!$collectionId) return [];

        $cacheKey = "whale_collection_stats_{$collectionId}";

        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($collectionId) {
            try {
                $response = $this->makeMarketApiRequest('collection/stats', [
                    'collection_id' => $collectionId,
                ]);

                return [
                    'floor_price' => $response['floor_price'] ?? 0,
                    'market_cap' => $response['market_cap'] ?? 0,
                    'volume_24h' => $response['volume_24h'] ?? 0,
                    'volume_7d' => $response['volume_7d'] ?? 0,
                    'volume_30d' => $response['volume_30d'] ?? 0,
                    'holder_count' => $response['holder_count'] ?? 0,
                    'listed_count' => $response['listed_count'] ?? 0,
                    'total_supply' => $response['total_supply'] ?? 0,
                    'avg_price_24h' => $response['avg_price_24h'] ?? 0,
                    'price_change_24h' => $response['price_change_24h'] ?? 0,
                    'last_updated' => now(),
                ];

            } catch (\Exception $e) {
                Log::warning('获取藏品统计失败', [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage()
                ]);

                return $this->getEstimatedStats($collectionId);
            }
        });
    }

    public function getBatchCollectionStats($collectionIds)
    {
        $stats = [];

        foreach (array_chunk($collectionIds, 10) as $batch) {
            try {
                $response = $this->makeMarketApiRequest('collection/batch-stats', [
                    'collection_ids' => $batch,
                ]);

                if (isset($response['collections'])) {
                    foreach ($response['collections'] as $collectionData) {
                        $stats[$collectionData['collection_id']] = $collectionData;
                    }
                }

            } catch (\Exception $e) {
                Log::warning('批量获取藏品统计失败', [
                    'collection_ids' => $batch,
                    'error' => $e->getMessage()
                ]);

                foreach ($batch as $collectionId) {
                    $stats[$collectionId] = $this->getEstimatedStats($collectionId);
                }
            }

            usleep(100000);
        }

        return $stats;
    }

    protected function makeMarketApiRequest($endpoint, $params = [])
    {
        if (!$this->marketApiUrl) {
            throw new \Exception('未配置市场API地址');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->get($this->marketApiUrl . '/' . $endpoint, $params);

        if (!$response->successful()) {
            throw new \Exception('市场API请求失败: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();

        if (!$data || (isset($data['success']) && !$data['success'])) {
            throw new \Exception('市场API返回错误: ' . ($data['message'] ?? '未知错误'));
        }

        return $data['data'] ?? $data;
    }

    protected function getEstimatedFloorPrice($collectionId)
    {
        $hohoCollections = [
            'hoho_genesis_001' => 99.00,
            'hoho_genesis_002' => 59.00,
            'hoho_special_001' => 0.00,
            'hoho_collab_001' => 0.00,
            'hoho_collab_002' => 0.00,
            'hoho_collab_003' => 0.00,
            'hoho_collab_004' => 0.00,
        ];

        return $hohoCollections[$collectionId] ?? 0;
    }

    protected function getEstimatedStats($collectionId)
    {
        $floorPrice = $this->getEstimatedFloorPrice($collectionId);

        return [
            'floor_price' => $floorPrice,
            'market_cap' => $floorPrice * 1000,
            'volume_24h' => 0,
            'volume_7d' => 0,
            'volume_30d' => 0,
            'holder_count' => 0,
            'listed_count' => 0,
            'total_supply' => 1000,
            'avg_price_24h' => $floorPrice,
            'price_change_24h' => 0,
            'last_updated' => now(),
            'estimated' => true,
        ];
    }

    public function refreshAllMarketData()
    {
        $hohoCollectionIds = [
            'hoho_genesis_001',
            'hoho_genesis_002',
            'hoho_special_001',
            'hoho_collab_001',
            'hoho_collab_002',
            'hoho_collab_003',
            'hoho_collab_004',
        ];

        $results = [
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($hohoCollectionIds as $collectionId) {
            try {
                Cache::forget("whale_floor_price_{$collectionId}");
                Cache::forget("whale_market_cap_{$collectionId}");
                Cache::forget("whale_volume_24h_{$collectionId}");
                Cache::forget("whale_holder_count_{$collectionId}");
                Cache::forget("whale_collection_stats_{$collectionId}");

                $this->getCollectionStats($collectionId);

                $results['updated']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'collection_id' => $collectionId,
                    'error' => $e->getMessage(),
                ];
            }

            usleep(200000);
        }

        Log::info('市场数据刷新完成', $results);

        return $results;
    }

    public function getMarketTrends($collectionId, $period = '7d')
    {
        $cacheKey = "whale_market_trends_{$collectionId}_{$period}";

        return Cache::remember($cacheKey, 3600, function () use ($collectionId, $period) {
            try {
                $response = $this->makeMarketApiRequest('collection/trends', [
                    'collection_id' => $collectionId,
                    'period' => $period,
                ]);

                return $response['trends'] ?? [];

            } catch (\Exception $e) {
                Log::warning('获取市场趋势失败', [
                    'collection_id' => $collectionId,
                    'period' => $period,
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    public function clearMarketDataCache($collectionId = null)
    {
        if ($collectionId) {
            $cacheKeys = [
                "whale_floor_price_{$collectionId}",
                "whale_market_cap_{$collectionId}",
                "whale_volume_24h_{$collectionId}",
                "whale_holder_count_{$collectionId}",
                "whale_collection_stats_{$collectionId}",
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        } else {
            Cache::flush();
        }

        return true;
    }
}