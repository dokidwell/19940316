<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TaskCenterService
{
    protected $whaleRewardService;
    protected $pointsService;

    public function __construct(WhaleRewardService $whaleRewardService, PointsService $pointsService)
    {
        $this->whaleRewardService = $whaleRewardService;
        $this->pointsService = $pointsService;
    }

    public function getUserTasks(User $user)
    {
        $tasks = [];

        // 每日任务
        $tasks = array_merge($tasks, $this->getDailyTasks($user));

        // 一次性任务
        $tasks = array_merge($tasks, $this->getOnetimeTasks($user));

        // 持续任务
        $tasks = array_merge($tasks, $this->getOngoingTasks($user));

        // 鲸探专属任务
        if ($user->whale_account_id) {
            $tasks = array_merge($tasks, $this->getWhaleTasks($user));
        }

        return $this->sortTasks($tasks);
    }

    protected function getDailyTasks(User $user)
    {
        $tasks = [];

        // 每日签到
        $canCheckin = !Cache::has("daily_checkin_{$user->id}_" . now()->toDateString());
        $checkinReward = $this->whaleRewardService->calculateDailyCheckinReward($user);

        $tasks[] = [
            'id' => 'daily_checkin',
            'type' => 'daily',
            'category' => '基础任务',
            'name' => '每日签到',
            'description' => '每日签到获得积分奖励',
            'reward' => $this->pointsService->formatAmount($checkinReward),
            'progress' => $canCheckin ? 0 : 1,
            'max_progress' => 1,
            'completed' => !$canCheckin,
            'can_complete' => $canCheckin,
            'action_type' => 'api_call',
            'action_url' => route('whale.checkin'),
            'icon' => 'calendar-check',
            'difficulty' => 'easy',
            'estimated_time' => '1分钟',
            'tips' => $user->whale_account_id
                ? '绑定鲸探账户可获得更多签到奖励'
                : '绑定鲸探账户后签到奖励将增加10倍',
        ];

        // NFT空投奖励（需要鲸探账户）
        if ($user->whale_account_id) {
            $canAirdrop = !Cache::has("whale_airdrop_cooldown_{$user->id}");
            $airdropReward = $this->whaleRewardService->calculateNftAirdropReward($user);

            $tasks[] = [
                'id' => 'nft_airdrop',
                'type' => 'daily',
                'category' => '鲸探任务',
                'name' => 'NFT空投奖励',
                'description' => '持有鲸探NFT可获得每日空投奖励',
                'reward' => $this->pointsService->formatAmount($airdropReward),
                'progress' => $canAirdrop && $airdropReward > 0 ? 0 : 1,
                'max_progress' => 1,
                'completed' => !$canAirdrop || $airdropReward <= 0,
                'can_complete' => $canAirdrop && $airdropReward > 0,
                'action_type' => 'api_call',
                'action_url' => route('whale.airdrop'),
                'icon' => 'gift',
                'difficulty' => 'easy',
                'estimated_time' => '1分钟',
                'tips' => '拥有更多稀有NFT可获得更高的空投奖励',
                'requirements' => ['whale_account_verified', 'own_nft'],
            ];
        }

        return $tasks;
    }

    protected function getOnetimeTasks(User $user)
    {
        $tasks = [];

        // 绑定鲸探账户
        if (!$user->whale_account_id) {
            $tasks[] = [
                'id' => 'bind_whale_account',
                'type' => 'onetime',
                'category' => '账户设置',
                'name' => '绑定鲸探账户',
                'description' => '绑定鲸探账户解锁更多奖励和功能',
                'reward' => '1.00000000',
                'progress' => 0,
                'max_progress' => 1,
                'completed' => false,
                'can_complete' => true,
                'action_type' => 'redirect',
                'action_url' => route('whale.bind'),
                'icon' => 'link',
                'difficulty' => 'medium',
                'estimated_time' => '5分钟',
                'tips' => '绑定后可享受所有签到、创作、交互的奖励倍数加成',
                'benefits' => [
                    '签到奖励增加10倍',
                    '解锁NFT空投奖励',
                    '所有行为奖励获得倍数加成',
                    '提升验证等级'
                ],
            ];
        }

        // 完善个人资料
        $profileComplete = $this->checkProfileCompletion($user);
        if ($profileComplete['completion_rate'] < 100) {
            $tasks[] = [
                'id' => 'complete_profile',
                'type' => 'onetime',
                'category' => '账户设置',
                'name' => '完善个人资料',
                'description' => '完善头像、简介等个人信息',
                'reward' => '0.50000000',
                'progress' => $profileComplete['completion_rate'],
                'max_progress' => 100,
                'completed' => $profileComplete['completion_rate'] >= 100,
                'can_complete' => $profileComplete['completion_rate'] < 100,
                'action_type' => 'redirect',
                'action_url' => route('profile.index'),
                'icon' => 'user-edit',
                'difficulty' => 'easy',
                'estimated_time' => '3分钟',
                'tips' => '完整的个人资料有助于建立社区信任',
                'missing_fields' => $profileComplete['missing_fields'],
            ];
        }

        return $tasks;
    }

    protected function getOngoingTasks(User $user)
    {
        $tasks = [];

        // 创作任务
        $artworkCount = $user->artworks()->count();
        $nextMilestone = $this->getNextMilestone($artworkCount, [1, 5, 10, 25, 50, 100]);

        if ($nextMilestone) {
            $tasks[] = [
                'id' => 'create_artworks',
                'type' => 'ongoing',
                'category' => '创作任务',
                'name' => "发布{$nextMilestone}个作品",
                'description' => '通过创作和分享作品获得积分奖励',
                'reward' => $this->calculateMilestoneReward($nextMilestone),
                'progress' => $artworkCount,
                'max_progress' => $nextMilestone,
                'completed' => $artworkCount >= $nextMilestone,
                'can_complete' => true,
                'action_type' => 'redirect',
                'action_url' => route('create.index'),
                'icon' => 'palette',
                'difficulty' => 'medium',
                'estimated_time' => '创作时间根据作品复杂度而定',
                'tips' => '高质量的原创作品更容易获得社区认可',
            ];
        }

        // 社区互动任务
        $interactionCount = $user->pointTransactions()
            ->whereIn('type', ['like_reward', 'comment_reward', 'share_reward'])
            ->count();
        $interactionMilestone = $this->getNextMilestone($interactionCount, [10, 50, 100, 500, 1000]);

        if ($interactionMilestone) {
            $tasks[] = [
                'id' => 'community_interaction',
                'type' => 'ongoing',
                'category' => '社区任务',
                'name' => "完成{$interactionMilestone}次社区互动",
                'description' => '通过点赞、评论、分享与社区成员互动',
                'reward' => $this->calculateMilestoneReward($interactionMilestone, 0.1),
                'progress' => $interactionCount,
                'max_progress' => $interactionMilestone,
                'completed' => $interactionCount >= $interactionMilestone,
                'can_complete' => true,
                'action_type' => 'redirect',
                'action_url' => route('artworks.index'),
                'icon' => 'heart',
                'difficulty' => 'easy',
                'estimated_time' => '每次互动约1分钟',
                'tips' => '积极的社区参与有助于发现优秀作品',
            ];
        }

        // 治理参与任务
        if ($user->canCreateProposal()) {
            $proposalCount = $user->proposals()->count();
            $voteCount = $user->votes()->count();

            if ($proposalCount < 1) {
                $tasks[] = [
                    'id' => 'create_first_proposal',
                    'type' => 'ongoing',
                    'category' => '治理任务',
                    'name' => '发起第一个提案',
                    'description' => '参与社区治理，发起你的第一个提案',
                    'reward' => '10.00000000',
                    'progress' => 0,
                    'max_progress' => 1,
                    'completed' => false,
                    'can_complete' => true,
                    'action_type' => 'redirect',
                    'action_url' => route('community.proposals'),
                    'icon' => 'megaphone',
                    'difficulty' => 'hard',
                    'estimated_time' => '30分钟',
                    'tips' => '好的提案需要详细的说明和合理的论证',
                    'requirements' => ['verified_user', 'min_points_1000'],
                ];
            }

            $voteMilestone = $this->getNextMilestone($voteCount, [1, 5, 10, 25, 50]);
            if ($voteMilestone) {
                $tasks[] = [
                    'id' => 'participate_voting',
                    'type' => 'ongoing',
                    'category' => '治理任务',
                    'name' => "参与{$voteMilestone}次投票",
                    'description' => '行使你的投票权，参与社区决策',
                    'reward' => $this->calculateMilestoneReward($voteMilestone, 0.5),
                    'progress' => $voteCount,
                    'max_progress' => $voteMilestone,
                    'completed' => $voteCount >= $voteMilestone,
                    'can_complete' => true,
                    'action_type' => 'redirect',
                    'action_url' => route('community.proposals'),
                    'icon' => 'vote-yea',
                    'difficulty' => 'medium',
                    'estimated_time' => '每次投票约5分钟',
                    'tips' => '仔细阅读提案内容，理性投票',
                ];
            }
        }

        return $tasks;
    }

    protected function getWhaleTasks(User $user)
    {
        $tasks = [];

        // 使用鲸探NFT作为头像
        if (!$this->isUsingWhaleAvatar($user)) {
            $tasks[] = [
                'id' => 'use_whale_avatar',
                'type' => 'onetime',
                'category' => '鲸探任务',
                'name' => '使用鲸探NFT作为头像',
                'description' => '设置鲸探NFT作为头像，签到奖励再增加10倍',
                'reward' => '0.10000000',
                'progress' => 0,
                'max_progress' => 1,
                'completed' => false,
                'can_complete' => true,
                'action_type' => 'redirect',
                'action_url' => route('profile.index'),
                'icon' => 'image',
                'difficulty' => 'easy',
                'estimated_time' => '2分钟',
                'tips' => '展示你的鲸探NFT收藏，获得更高的签到奖励',
                'requirements' => ['whale_account_verified', 'own_nft'],
            ];
        }

        // 同步鲸探数据
        $whaleAccount = $user->whaleAccount;
        if ($whaleAccount && $whaleAccount->needsSync()) {
            $tasks[] = [
                'id' => 'sync_whale_data',
                'type' => 'maintenance',
                'category' => '鲸探任务',
                'name' => '同步鲸探数据',
                'description' => '同步最新的鲸探NFT数据，确保奖励计算准确',
                'reward' => '0.05000000',
                'progress' => 0,
                'max_progress' => 1,
                'completed' => false,
                'can_complete' => true,
                'action_type' => 'api_call',
                'action_url' => route('whale.sync'),
                'icon' => 'sync',
                'difficulty' => 'easy',
                'estimated_time' => '1分钟',
                'tips' => '定期同步数据可确保你获得最新的NFT奖励',
                'requirements' => ['whale_account_verified'],
            ];
        }

        return $tasks;
    }

    protected function checkProfileCompletion(User $user)
    {
        $fields = [
            'avatar' => $user->avatar ? 25 : 0,
            'bio' => $user->bio ? 25 : 0,
            'social_links' => ($user->social_links && count($user->social_links) > 0) ? 25 : 0,
            'whale_verified' => $user->whale_account_id ? 25 : 0,
        ];

        $completionRate = array_sum($fields);
        $missingFields = [];

        foreach ($fields as $field => $score) {
            if ($score === 0) {
                $missingFields[] = $this->getFieldDisplayName($field);
            }
        }

        return [
            'completion_rate' => $completionRate,
            'missing_fields' => $missingFields,
        ];
    }

    protected function getFieldDisplayName($field)
    {
        $names = [
            'avatar' => '头像',
            'bio' => '个人简介',
            'social_links' => '社交链接',
            'whale_verified' => '鲸探认证',
        ];

        return $names[$field] ?? $field;
    }

    protected function getNextMilestone($current, $milestones)
    {
        foreach ($milestones as $milestone) {
            if ($current < $milestone) {
                return $milestone;
            }
        }
        return null;
    }

    protected function calculateMilestoneReward($milestone, $baseReward = 1.0)
    {
        $reward = $milestone * $baseReward;
        return $this->pointsService->formatAmount($reward);
    }

    protected function isUsingWhaleAvatar(User $user)
    {
        return $user->avatar && (
            strpos($user->avatar, 'whale_nft_') === 0 ||
            strpos($user->avatar, 'jingtan_') === 0
        );
    }

    protected function sortTasks($tasks)
    {
        // 按优先级排序：可完成 > 进行中 > 已完成
        usort($tasks, function ($a, $b) {
            // 先按是否可完成排序
            if ($a['can_complete'] !== $b['can_complete']) {
                return $b['can_complete'] <=> $a['can_complete'];
            }

            // 再按是否完成排序
            if ($a['completed'] !== $b['completed']) {
                return $a['completed'] <=> $b['completed'];
            }

            // 最后按类型排序：daily > onetime > ongoing
            $typeOrder = ['daily' => 1, 'onetime' => 2, 'ongoing' => 3, 'maintenance' => 4];
            return ($typeOrder[$a['type']] ?? 5) <=> ($typeOrder[$b['type']] ?? 5);
        });

        return $tasks;
    }

    public function getTaskStats(User $user)
    {
        $tasks = $this->getUserTasks($user);

        $stats = [
            'total_tasks' => count($tasks),
            'completed_tasks' => count(array_filter($tasks, fn($task) => $task['completed'])),
            'available_tasks' => count(array_filter($tasks, fn($task) => $task['can_complete'] && !$task['completed'])),
            'total_potential_reward' => 0,
            'daily_potential_reward' => 0,
        ];

        foreach ($tasks as $task) {
            if (!$task['completed']) {
                $reward = (float) str_replace(',', '', $task['reward']);
                $stats['total_potential_reward'] += $reward;

                if ($task['type'] === 'daily') {
                    $stats['daily_potential_reward'] += $reward;
                }
            }
        }

        $stats['total_potential_reward'] = $this->pointsService->formatAmount($stats['total_potential_reward']);
        $stats['daily_potential_reward'] = $this->pointsService->formatAmount($stats['daily_potential_reward']);
        $stats['completion_rate'] = $stats['total_tasks'] > 0
            ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1)
            : 0;

        return $stats;
    }

    public function getTaskCategories()
    {
        return [
            '基础任务' => '每日签到等基础活动',
            '账户设置' => '完善个人资料和账户设置',
            '创作任务' => '发布和分享原创作品',
            '社区任务' => '与社区成员互动',
            '治理任务' => '参与社区治理和投票',
            '鲸探任务' => '鲸探NFT相关任务',
        ];
    }

    public function getTaskDifficulties()
    {
        return [
            'easy' => '简单（1-5分钟）',
            'medium' => '中等（5-30分钟）',
            'hard' => '困难（30分钟以上）',
        ];
    }
}