<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserService
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function createUser($data)
    {
        $userData = [
            'hoho_id' => $this->generateHohoId(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
            'points_balance' => 0.00000000,
            'total_points_earned' => 0.00000000,
            'total_points_spent' => 0.00000000,
            'verification_level' => 1,
        ];

        $user = User::create($userData);

        SystemLog::logUserAction(
            'user_register',
            "用户注册: {$user->name}",
            [
                'hoho_id' => $user->hoho_id,
                'email' => $user->email,
                'registration_ip' => request()->ip(),
            ],
            $user->id
        );

        return $user;
    }

    public function updateProfile(User $user, $data)
    {
        $updateData = [];

        if (isset($data['name']) && $data['name'] !== $user->name) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['bio'])) {
            $updateData['bio'] = $data['bio'];
        }

        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $updateData['social_links'] = $data['social_links'];
        }

        if (isset($data['avatar']) && $data['avatar'] !== $user->avatar) {
            $updateData['avatar'] = $data['avatar'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);

            SystemLog::logUserAction(
                'user_profile_update',
                "用户更新资料",
                [
                    'updated_fields' => array_keys($updateData),
                    'changes' => $updateData,
                ],
                $user->id
            );
        }

        return $user;
    }

    public function changePassword(User $user, $oldPassword, $newPassword)
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new \Exception('原密码错误');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        SystemLog::logUserAction(
            'user_password_change',
            "用户修改密码",
            ['changed_at' => now()],
            $user->id
        );

        return true;
    }

    public function updateSettings(User $user, $settings)
    {
        $allowedSettings = [
            'notifications_enabled',
            'email_notifications',
            'privacy_level',
            'language',
            'timezone',
            'theme',
        ];

        $currentSettings = $user->settings ?? [];
        $newSettings = $currentSettings;

        $changes = [];
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedSettings)) {
                if (!isset($currentSettings[$key]) || $currentSettings[$key] !== $value) {
                    $changes[$key] = [
                        'old' => $currentSettings[$key] ?? null,
                        'new' => $value,
                    ];
                    $newSettings[$key] = $value;
                }
            }
        }

        if (!empty($changes)) {
            $user->update(['settings' => $newSettings]);

            SystemLog::logUserAction(
                'user_settings_update',
                "用户更新设置",
                ['changes' => $changes],
                $user->id
            );
        }

        return $user;
    }

    public function generateHohoId()
    {
        do {
            $hohoId = 'H' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('hoho_id', $hohoId)->exists());

        return $hohoId;
    }

    public function getUserStats(User $user)
    {
        $pointsStats = $this->pointsService->getUserStats($user);

        $artworkStats = [
            'total_artworks' => $user->artworks()->count(),
            'featured_artworks' => $user->artworks()->where('is_featured', true)->count(),
            'total_views' => $user->artworks()->sum('view_count'),
            'total_likes' => $user->artworks()->sum('like_count'),
            'total_downloads' => $user->artworks()->sum('download_count'),
        ];

        $governanceStats = [
            'proposals_created' => $user->proposals()->count(),
            'votes_cast' => $user->votes()->count(),
            'governance_points_earned' => $user->pointTransactions()
                ->where('type', 'governance_reward')
                ->sum('amount'),
        ];

        $whaleStats = [];
        if ($user->whaleAccount) {
            $whaleStats = [
                'whale_verified' => true,
                'nft_count' => $user->whale_nft_count,
                'verification_level' => $user->verification_level,
                'reward_multiplier' => $user->getWhaleRewardMultiplier(),
                'whale_points_earned' => $user->pointTransactions()
                    ->where('type', 'whale_nft_bonus')
                    ->sum('amount'),
            ];
        } else {
            $whaleStats = ['whale_verified' => false];
        }

        return [
            'basic_info' => [
                'hoho_id' => $user->hoho_id,
                'name' => $user->name,
                'registration_date' => $user->created_at,
                'is_verified' => $user->is_verified,
                'verification_level' => $user->verification_level,
            ],
            'points' => $pointsStats,
            'artworks' => $artworkStats,
            'governance' => $governanceStats,
            'whale' => $whaleStats,
        ];
    }

    public function getUserRanking(User $user)
    {
        $pointsRank = User::where('points_balance', '>', $user->points_balance)->count() + 1;
        $totalUsers = User::count();

        $artworkRank = User::whereHas('artworks')
            ->withCount('artworks')
            ->having('artworks_count', '>', $user->artworks()->count())
            ->count() + 1;

        $whaleRank = null;
        if ($user->whale_account_id) {
            $whaleRank = User::whereNotNull('whale_account_id')
                ->where('whale_nft_count', '>', $user->whale_nft_count)
                ->count() + 1;
        }

        return [
            'points_rank' => $pointsRank,
            'artwork_rank' => $artworkRank,
            'whale_rank' => $whaleRank,
            'total_users' => $totalUsers,
            'percentile' => [
                'points' => round((1 - ($pointsRank - 1) / $totalUsers) * 100, 1),
                'artworks' => round((1 - ($artworkRank - 1) / $totalUsers) * 100, 1),
            ],
        ];
    }

    public function getActivityHistory(User $user, $days = 30)
    {
        $startDate = now()->subDays($days);

        $activities = SystemLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'type' => $log->type,
                    'type_display' => SystemLog::TYPES[$log->type] ?? $log->type,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'data' => $log->formatted_data,
                ];
            });

        $dailyActivity = SystemLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'activities' => $activities,
            'daily_activity' => $dailyActivity,
            'period' => "{$days}天",
            'total_activities' => $activities->count(),
        ];
    }

    public function getUserAchievements(User $user)
    {
        $achievements = [];

        if ($user->is_verified) {
            $achievements[] = [
                'id' => 'whale_verified',
                'name' => '鲸探认证',
                'description' => '成功绑定鲸探账户',
                'icon' => 'whale',
                'earned_at' => $user->whaleAccount?->created_at,
            ];
        }

        if ($user->verification_level >= 3) {
            $achievements[] = [
                'id' => 'whale_collector',
                'name' => '鲸探收藏家',
                'description' => '拥有多个鲸探NFT',
                'icon' => 'collection',
                'earned_at' => $user->whaleAccount?->last_sync_at,
            ];
        }

        if ($user->points_balance >= 1000) {
            $achievements[] = [
                'id' => 'points_millionaire',
                'name' => '积分富豪',
                'description' => '积分余额达到1000',
                'icon' => 'money',
                'earned_at' => $user->pointTransactions()
                    ->where('balance_after', '>=', 1000)
                    ->first()?->created_at,
            ];
        }

        if ($user->artworks()->count() >= 10) {
            $achievements[] = [
                'id' => 'prolific_artist',
                'name' => '多产艺术家',
                'description' => '发布10个以上作品',
                'icon' => 'art',
                'earned_at' => $user->artworks()->skip(9)->first()?->created_at,
            ];
        }

        if ($user->proposals()->count() >= 5) {
            $achievements[] = [
                'id' => 'community_leader',
                'name' => '社区领袖',
                'description' => '发起5个以上提案',
                'icon' => 'leadership',
                'earned_at' => $user->proposals()->skip(4)->first()?->created_at,
            ];
        }

        return $achievements;
    }

    public function deactivateUser(User $user, $reason = '用户注销')
    {
        $user->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        SystemLog::logUserAction(
            'user_deactivate',
            "用户账户停用",
            ['reason' => $reason],
            $user->id
        );

        return true;
    }

    public function reactivateUser(User $user)
    {
        $user->update([
            'is_active' => true,
            'deactivated_at' => null,
        ]);

        SystemLog::logUserAction(
            'user_reactivate',
            "用户账户重新激活",
            [],
            $user->id
        );

        return true;
    }

    public function searchUsers($query, $limit = 20)
    {
        return User::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('hoho_id', 'like', "%{$query}%");
            })
            ->select(['id', 'hoho_id', 'name', 'avatar', 'is_verified', 'verification_level'])
            ->limit($limit)
            ->get();
    }

    public function getLeaderboard($type = 'points', $limit = 50)
    {
        $cacheKey = "leaderboard_{$type}_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($type, $limit) {
            switch ($type) {
                case 'points':
                    return User::where('is_active', true)
                        ->where('points_balance', '>', 0)
                        ->orderBy('points_balance', 'desc')
                        ->limit($limit)
                        ->get(['hoho_id', 'name', 'points_balance', 'avatar', 'verification_level'])
                        ->map(function ($user, $index) {
                            return [
                                'rank' => $index + 1,
                                'hoho_id' => $user->hoho_id,
                                'name' => $user->name,
                                'value' => number_format($user->points_balance, 8, '.', ''),
                                'avatar' => $user->avatar,
                                'verification_level' => $user->verification_level,
                            ];
                        });

                case 'artworks':
                    return User::where('is_active', true)
                        ->withCount('artworks')
                        ->having('artworks_count', '>', 0)
                        ->orderBy('artworks_count', 'desc')
                        ->limit($limit)
                        ->get(['hoho_id', 'name', 'avatar', 'verification_level'])
                        ->map(function ($user, $index) {
                            return [
                                'rank' => $index + 1,
                                'hoho_id' => $user->hoho_id,
                                'name' => $user->name,
                                'value' => $user->artworks_count,
                                'avatar' => $user->avatar,
                                'verification_level' => $user->verification_level,
                            ];
                        });

                case 'whale_nft':
                    return User::where('is_active', true)
                        ->whereNotNull('whale_account_id')
                        ->where('whale_nft_count', '>', 0)
                        ->orderBy('whale_nft_count', 'desc')
                        ->limit($limit)
                        ->get(['hoho_id', 'name', 'whale_nft_count', 'avatar', 'verification_level'])
                        ->map(function ($user, $index) {
                            return [
                                'rank' => $index + 1,
                                'hoho_id' => $user->hoho_id,
                                'name' => $user->name,
                                'value' => $user->whale_nft_count,
                                'avatar' => $user->avatar,
                                'verification_level' => $user->verification_level,
                            ];
                        });

                default:
                    return collect();
            }
        });
    }

    public function getUserProfile(User $user, $includePrivate = false)
    {
        $profile = [
            'hoho_id' => $user->hoho_id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'is_verified' => $user->is_verified,
            'verification_level' => $user->verification_level,
            'social_links' => $user->social_links,
            'created_at' => $user->created_at,
            'points_balance' => number_format($user->points_balance, 8, '.', ''),
            'whale_verified' => $user->whale_account_id ? true : false,
        ];

        if ($includePrivate) {
            $profile['email'] = $user->email;
            $profile['phone'] = $user->phone;
            $profile['settings'] = $user->settings;
            $profile['stats'] = $this->getUserStats($user);
            $profile['ranking'] = $this->getUserRanking($user);
            $profile['achievements'] = $this->getUserAchievements($user);
        }

        return $profile;
    }
}