<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhaleAccount;
use App\Models\NftCollection;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhaleRewardService
{
    protected $pricingService;
    protected $rewardConfig;

    public function __construct(WhalePricingService $pricingService)
    {
        $this->pricingService = $pricingService;
        $this->rewardConfig = $this->loadRewardConfig();
    }

    protected function loadRewardConfig()
    {
        return [
            'daily_checkin' => [
                'unbound' => 0.00010000,
                'bound' => 0.00100000,
                'with_avatar' => 0.01000000,
            ],
            'binding_bonus' => [
                'base_reward' => 1.00000000,
                'per_nft_bonus' => 0.10000000,
                'rarity_multipliers' => [
                    'common' => 1.0,
                    'uncommon' => 1.2,
                    'rare' => 1.5,
                    'epic' => 2.0,
                    'legendary' => 3.0,
                    'mythic' => 5.0,
                ],
            ],
            'nft_airdrop' => [
                'enabled' => true,
                'max_daily_per_user' => 50.00000000,
                'cooldown_hours' => 24,
            ],
            'artwork_interaction' => [
                'view' => 0.10000000,
                'like' => 0.50000000,
                'download' => 2.00000000,
                'share' => 1.00000000,
                'comment' => 0.30000000,
            ],
        ];
    }

    public function calculateBindingReward($collections)
    {
        try {
            $baseReward = $this->rewardConfig['binding_bonus']['base_reward'];
            $totalBonus = 0;

            foreach ($collections as $collection) {
                $rarity = $this->determineCollectionRarity($collection);
                $rarityMultiplier = $this->rewardConfig['binding_bonus']['rarity_multipliers'][$rarity] ?? 1.0;
                $nftBonus = $this->rewardConfig['binding_bonus']['per_nft_bonus'] * $rarityMultiplier;

                $count = $collection['count'] ?? 1;
                $totalBonus += $nftBonus * $count;
            }

            $totalReward = $baseReward + $totalBonus;

            return round($totalReward, 8);

        } catch (\Exception $e) {
            Log::error('计算绑定奖励失败', ['error' => $e->getMessage()]);
            return $this->rewardConfig['binding_bonus']['base_reward'];
        }
    }

    public function calculateDailyCheckinReward(User $user)
    {
        if ($user->whale_account_id && $user->whaleAccount) {
            $whaleAccount = $user->whaleAccount;

            if ($this->isUsingWhaleAvatar($user)) {
                return $this->rewardConfig['daily_checkin']['with_avatar'];
            }

            return $this->rewardConfig['daily_checkin']['bound'];
        }

        return $this->rewardConfig['daily_checkin']['unbound'];
    }

    public function calculateNftAirdropReward(User $user)
    {
        if (!$this->rewardConfig['nft_airdrop']['enabled']) {
            return 0;
        }

        if (!$user->whale_account_id || !$user->whaleAccount) {
            return 0;
        }

        $cacheKey = "whale_airdrop_cooldown_{$user->id}";
        if (Cache::has($cacheKey)) {
            return 0;
        }

        $collections = $user->nftCollections;
        if ($collections->isEmpty()) {
            return 0;
        }

        $totalReward = 0;
        $rewardDetails = [];

        foreach ($collections as $nft) {
            $nftReward = $this->pricingService->calculateNftValue($nft->metadata);

            $totalReward += $nftReward;
            $rewardDetails[] = [
                'nft_id' => $nft->id,
                'name' => $nft->name,
                'rarity' => $nft->rarity,
                'reward' => $nftReward,
            ];
        }

        $maxDaily = $this->rewardConfig['nft_airdrop']['max_daily_per_user'];
        if ($totalReward > $maxDaily) {
            $scaleFactor = $maxDaily / $totalReward;
            $totalReward = $maxDaily;

            foreach ($rewardDetails as &$detail) {
                $detail['reward'] *= $scaleFactor;
                $detail['scaled'] = true;
            }
        }

        Cache::put($cacheKey, true, $this->rewardConfig['nft_airdrop']['cooldown_hours'] * 3600);

        $this->logAirdropReward($user, $totalReward, $rewardDetails);

        return round($totalReward, 8);
    }

    public function processArtworkInteractionReward(User $creator, $interactionType, $artwork)
    {
        if (!isset($this->rewardConfig['artwork_interaction'][$interactionType])) {
            return 0;
        }

        $baseReward = $this->rewardConfig['artwork_interaction'][$interactionType];

        $multiplier = $creator->getWhaleRewardMultiplier();

        $finalReward = $baseReward * $multiplier;

        $creator->addPoints(
            $finalReward,
            $interactionType . '_reward',
            "作品《{$artwork->title}》获得{$interactionType}奖励",
            $artwork
        );

        return $finalReward;
    }

    public function processDailyCheckin(User $user)
    {
        $cacheKey = "daily_checkin_{$user->id}_" . now()->toDateString();

        if (Cache::has($cacheKey)) {
            throw new \Exception('今日已签到');
        }

        $reward = $this->calculateDailyCheckinReward($user);

        $user->addPoints(
            $reward,
            'daily_checkin',
            '每日签到奖励'
        );

        Cache::put($cacheKey, true, 86400);

        SystemLog::logUserAction(
            'daily_checkin',
            "用户每日签到",
            [
                'reward' => $reward,
                'whale_bound' => $user->whale_account_id ? true : false,
                'using_whale_avatar' => $this->isUsingWhaleAvatar($user),
            ],
            $user->id
        );

        return $reward;
    }

    public function processBatchNftAirdrop($userIds = null)
    {
        $query = User::whereNotNull('whale_account_id')
            ->whereHas('whaleAccount', function ($q) {
                $q->where('verification_status', 'verified');
            });

        if ($userIds) {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();

        $results = [
            'processed' => 0,
            'total_rewards' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($users as $user) {
            try {
                $reward = $this->calculateNftAirdropReward($user);

                if ($reward > 0) {
                    $user->addPoints(
                        $reward,
                        'whale_nft_bonus',
                        '鲸探NFT每日空投奖励'
                    );

                    $results['processed']++;
                    $results['total_rewards'] += $reward;
                } else {
                    $results['skipped']++;
                }

            } catch (\Exception $e) {
                Log::error('NFT空投奖励失败', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                $results['errors']++;
            }
        }

        SystemLog::logUserAction(
            'whale_nft_batch_airdrop',
            "批量NFT空投奖励完成",
            $results
        );

        return $results;
    }

    public function syncAndRewardUser(User $user)
    {
        if (!$user->whale_account_id || !$user->whaleAccount) {
            throw new \Exception('用户未绑定鲸探账户');
        }

        $whaleService = app(WhaleService::class);

        $whaleAccount = $whaleService->refreshUserData($user->whaleAccount);

        $airdropReward = $this->calculateNftAirdropReward($user);

        if ($airdropReward > 0) {
            $user->addPoints(
                $airdropReward,
                'whale_nft_bonus',
                '鲸探NFT同步奖励'
            );
        }

        return [
            'whale_account' => $whaleAccount,
            'airdrop_reward' => $airdropReward,
            'nft_count' => $whaleAccount->nft_count,
            'total_value' => $whaleAccount->total_value,
        ];
    }

    protected function determineCollectionRarity($collection)
    {
        $totalSupply = $collection['total_count'] ?? 1000;

        if ($totalSupply <= 100) return 'legendary';
        if ($totalSupply <= 500) return 'epic';
        if ($totalSupply <= 1000) return 'rare';
        if ($totalSupply <= 5000) return 'uncommon';

        return 'common';
    }

    protected function isUsingWhaleAvatar(User $user)
    {
        if (!$user->avatar || !$user->whale_account_id) {
            return false;
        }

        return strpos($user->avatar, 'whale_nft_') === 0 ||
               strpos($user->avatar, 'jingtan_') === 0;
    }

    protected function logAirdropReward(User $user, $totalReward, $rewardDetails)
    {
        SystemLog::logUserAction(
            'whale_nft_reward',
            "鲸探NFT空投奖励",
            [
                'total_reward' => $totalReward,
                'nft_count' => count($rewardDetails),
                'details' => $rewardDetails,
            ],
            $user->id
        );
    }

    public function getRewardHistory(User $user, $days = 30)
    {
        return $user->pointTransactions()
            ->whereIn('type', [
                'daily_checkin',
                'whale_nft_bonus',
                'view_reward',
                'like_reward',
                'download_reward',
                'share_reward',
                'comment_reward',
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserRewardStats(User $user)
    {
        $stats = [
            'total_whale_rewards' => $user->pointTransactions()
                ->where('type', 'whale_nft_bonus')
                ->sum('amount'),
            'total_checkin_rewards' => $user->pointTransactions()
                ->where('type', 'daily_checkin')
                ->sum('amount'),
            'total_interaction_rewards' => $user->pointTransactions()
                ->whereIn('type', ['view_reward', 'like_reward', 'download_reward', 'share_reward', 'comment_reward'])
                ->sum('amount'),
            'whale_multiplier' => $user->getWhaleRewardMultiplier(),
            'can_airdrop_today' => !Cache::has("whale_airdrop_cooldown_{$user->id}"),
            'can_checkin_today' => !Cache::has("daily_checkin_{$user->id}_" . now()->toDateString()),
        ];

        $stats['total_rewards'] = $stats['total_whale_rewards'] +
                                  $stats['total_checkin_rewards'] +
                                  $stats['total_interaction_rewards'];

        return $stats;
    }

    public function updateRewardConfig($newConfig)
    {
        $this->rewardConfig = array_merge($this->rewardConfig, $newConfig);

        Cache::put('whale_reward_config', $this->rewardConfig, 86400);

        SystemLog::logUserAction(
            'whale_reward_config_update',
            '鲸探奖励配置已更新',
            ['new_config' => $newConfig]
        );

        return true;
    }

    public function getRewardConfig()
    {
        return $this->rewardConfig;
    }
}