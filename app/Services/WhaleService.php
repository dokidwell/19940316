<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhaleAccount;
use App\Models\NftCollection;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhaleService
{
    protected $baseUrl;
    protected $appId;
    protected $privateKey;
    protected $publicKey;
    protected $gatewayUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whale.base_url');
        $this->appId = config('services.whale.app_id');
        $this->privateKey = config('services.whale.private_key');
        $this->publicKey = config('services.whale.public_key');
        $this->gatewayUrl = config('services.whale.gateway_url');
    }

    public function getAuthUrl($userId, $redirectUri = null)
    {
        $redirectUri = $redirectUri ?: config('services.whale.redirect_uri');

        $params = [
            'app_id' => $this->appId,
            'scope' => 'auth_user,user_info,collection_info',
            'redirect_uri' => $redirectUri,
            'state' => base64_encode(json_encode(['user_id' => $userId])),
            'response_type' => 'code',
        ];

        return $this->baseUrl . '/oauth2/authorize?' . http_build_query($params);
    }

    public function exchangeCodeForToken($code, $state)
    {
        try {
            $stateData = json_decode(base64_decode($state), true);

            $response = Http::post($this->gatewayUrl, [
                'method' => 'alipay.system.oauth.token',
                'app_id' => $this->appId,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'charset' => 'UTF-8',
                'sign_type' => 'RSA2',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'version' => '1.0',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['alipay_system_oauth_token_response']['access_token'])) {
                    $tokenData = $data['alipay_system_oauth_token_response'];

                    return [
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'expires_in' => $tokenData['expires_in'] ?? 7200,
                        'user_id' => $stateData['user_id'],
                        'alipay_user_id' => $tokenData['user_id'] ?? null,
                    ];
                }
            }

            throw new \Exception('获取访问令牌失败: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('鲸探Token交换失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getUserInfo($accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->gatewayUrl, [
                'method' => 'alipay.user.info.share',
                'app_id' => $this->appId,
                'charset' => 'UTF-8',
                'sign_type' => 'RSA2',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'version' => '1.0',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['alipay_user_info_share_response'])) {
                    return $data['alipay_user_info_share_response'];
                }
            }

            throw new \Exception('获取用户信息失败: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('获取鲸探用户信息失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getUserCollections($accessToken, $alipayUserId)
    {
        try {
            $cacheKey = "whale_collections_{$alipayUserId}";

            return Cache::remember($cacheKey, 1800, function () use ($accessToken, $alipayUserId) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->post($this->gatewayUrl, [
                    'method' => 'alipay.commerce.ec.collection.query',
                    'app_id' => $this->appId,
                    'charset' => 'UTF-8',
                    'sign_type' => 'RSA2',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'biz_content' => json_encode([
                        'user_id' => $alipayUserId,
                        'page_num' => 1,
                        'page_size' => 100,
                    ]),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['alipay_commerce_ec_collection_query_response']['collection_list'])) {
                        return $data['alipay_commerce_ec_collection_query_response']['collection_list'];
                    }
                }

                throw new \Exception('获取用户藏品失败: ' . $response->body());
            });
        } catch (\Exception $e) {
            Log::error('获取鲸探用户藏品失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function bindUserAccount($userId, $tokenData, $userInfo)
    {
        try {
            $user = User::findOrFail($userId);

            $whaleAccount = WhaleAccount::updateOrCreate([
                'alipay_uid' => $userInfo['user_id'],
            ], [
                'whale_user_id' => $userInfo['user_id'],
                'nickname' => $userInfo['nick_name'] ?? null,
                'avatar_url' => $userInfo['avatar'] ?? null,
                'verification_status' => WhaleAccount::STATUS_VERIFIED,
                'last_sync_at' => now(),
                'api_data' => array_merge($tokenData, $userInfo),
            ]);

            $user->update([
                'whale_account_id' => $whaleAccount->id,
                'is_verified' => true,
            ]);

            $collections = $this->getUserCollections($tokenData['access_token'], $userInfo['user_id']);
            $this->syncUserCollections($whaleAccount, $collections);

            $rewardAmount = $this->calculateBindingReward($collections);
            if ($rewardAmount > 0) {
                $user->addPoints(
                    $rewardAmount,
                    'whale_nft_bonus',
                    "鲸探账户绑定奖励：{$whaleAccount->nft_count}个藏品",
                    $whaleAccount
                );
            }

            SystemLog::logUserAction(
                'whale_account_bind',
                "用户绑定鲸探账户: {$whaleAccount->nickname}",
                [
                    'whale_account_id' => $whaleAccount->id,
                    'nft_count' => $whaleAccount->nft_count,
                    'reward_amount' => $rewardAmount,
                ],
                $userId
            );

            return $whaleAccount;
        } catch (\Exception $e) {
            Log::error('绑定鲸探账户失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncUserCollections($whaleAccount, $collections)
    {
        $whaleAccount->nftCollections()->delete();

        $totalValue = 0;
        $nftCount = 0;

        foreach ($collections as $collection) {
            $value = $this->calculateNftValue($collection);

            $whaleAccount->nftCollections()->create([
                'user_id' => $whaleAccount->users()->first()?->id,
                'whale_collection_id' => $collection['collection_id'],
                'name' => $collection['collection_name'] ?? '未知藏品',
                'description' => $collection['description'] ?? null,
                'image_url' => $collection['image_url'] ?? null,
                'rarity' => $this->determineRarity($collection),
                'value' => $value,
                'acquired_at' => now(),
                'metadata' => $collection,
            ]);

            $totalValue += $value;
            $nftCount += $collection['count'] ?? 1;
        }

        $whaleAccount->update([
            'nft_count' => $nftCount,
            'total_value' => $totalValue,
        ]);

        $whaleAccount->users()->update([
            'whale_nft_count' => $nftCount,
            'verification_level' => min(floor($nftCount / 10) + 1, 5),
        ]);
    }

    protected function calculateNftValue($collection)
    {
        $pricingService = app(WhalePricingService::class);
        return $pricingService->calculateNftValue($collection);
    }

    protected function determineRarity($collection)
    {
        $issuedCount = $collection['total_count'] ?? 1000;

        if ($issuedCount <= 100) return 'legendary';
        if ($issuedCount <= 500) return 'epic';
        if ($issuedCount <= 1000) return 'rare';
        if ($issuedCount <= 5000) return 'uncommon';

        return 'common';
    }

    protected function calculateBindingReward($collections)
    {
        $rewardService = app(WhaleRewardService::class);
        return $rewardService->calculateBindingReward($collections);
    }

    public function refreshUserData($whaleAccount)
    {
        try {
            if (!$whaleAccount->api_data || !isset($whaleAccount->api_data['access_token'])) {
                throw new \Exception('缺少访问令牌');
            }

            $accessToken = $whaleAccount->api_data['access_token'];
            $alipayUserId = $whaleAccount->alipay_uid;

            $userInfo = $this->getUserInfo($accessToken);
            $collections = $this->getUserCollections($accessToken, $alipayUserId);

            $whaleAccount->updateFromWhaleApi(array_merge($userInfo, [
                'nft_collections' => $collections
            ]));

            return $whaleAccount->fresh();
        } catch (\Exception $e) {
            Log::error('刷新鲸探数据失败', [
                'whale_account_id' => $whaleAccount->id,
                'error' => $e->getMessage()
            ]);

            $whaleAccount->update([
                'verification_status' => WhaleAccount::STATUS_FAILED,
            ]);

            throw $e;
        }
    }

    public function syncAllAccounts()
    {
        $accounts = WhaleAccount::where('verification_status', WhaleAccount::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                  ->orWhere('last_sync_at', '<', now()->subHours(24));
            })
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($accounts as $account) {
            try {
                $this->refreshUserData($account);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        SystemLog::logUserAction(
            'whale_account_sync',
            "批量同步鲸探账户完成",
            $results
        );

        return $results;
    }
}