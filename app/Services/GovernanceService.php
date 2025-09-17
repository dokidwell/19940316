<?php

namespace App\Services;

use App\Models\User;
use App\Models\Proposal;
use App\Models\ProposalVote;
use App\Models\SystemLog;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GovernanceService
{
    protected $pointsService;

    const MIN_POINTS_TO_CREATE_PROPOSAL = 1000.00000000;
    const APPROVAL_THRESHOLD = 0.667; // 66.7%
    const PROPOSAL_CREATOR_REWARD_RATE = 0.10; // 10%

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    public function createProposal(User $user, $data)
    {
        try {
            // 验证用户权限
            $this->validateProposalCreationPermission($user);

            return DB::transaction(function () use ($user, $data) {
                // 扣除创建提案的积分
                $user->subtractPoints(
                    self::MIN_POINTS_TO_CREATE_PROPOSAL,
                    'proposal_creation',
                    "创建提案：{$data['title']}"
                );

                // 创建提案
                $proposal = Proposal::create([
                    'creator_id' => $user->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'category' => $data['category'] ?? 'other',
                    'voting_start_at' => $data['voting_start_at'] ?? now()->addHours(1),
                    'voting_end_at' => $data['voting_end_at'] ?? now()->addDays(7),
                    'min_points_to_vote' => $data['min_points_to_vote'] ?? 1.00000000,
                    'status' => Proposal::STATUS_DRAFT,
                    'metadata' => $data['metadata'] ?? [],
                ]);

                SystemLog::logUserAction(
                    'proposal_created',
                    "创建提案: {$proposal->title}",
                    [
                        'proposal_id' => $proposal->id,
                        'category' => $proposal->category,
                        'points_cost' => self::MIN_POINTS_TO_CREATE_PROPOSAL,
                    ],
                    $user->id
                );

                return $proposal;
            });

        } catch (\Exception $e) {
            throw new \Exception('创建提案失败: ' . $e->getMessage());
        }
    }

    public function approveProposal(Proposal $proposal, User $admin)
    {
        try {
            if ($proposal->status !== Proposal::STATUS_DRAFT) {
                throw new \Exception('只有草稿状态的提案可以审核');
            }

            $proposal->update([
                'status' => Proposal::STATUS_ACTIVE,
            ]);

            // 给创建者奖励公共积分的0.01%
            $publicPoolBalance = $this->pointsService->getPublicPoolBalance();
            $reward = $publicPoolBalance * 0.0001; // 0.01%

            if ($reward > 0) {
                $this->pointsService->subtractFromPublicPool($reward, '提案审核奖励');
                $proposal->creator->addPoints(
                    $reward,
                    'governance_reward',
                    "提案《{$proposal->title}》审核通过奖励"
                );
            }

            SystemLog::logUserAction(
                'proposal_approved',
                "提案审核通过: {$proposal->title}",
                [
                    'proposal_id' => $proposal->id,
                    'creator_id' => $proposal->creator_id,
                    'reward' => $reward,
                ],
                $admin->id
            );

            return $proposal;

        } catch (\Exception $e) {
            throw new \Exception('提案审核失败: ' . $e->getMessage());
        }
    }

    public function rejectProposal(Proposal $proposal, User $admin, $reason = '')
    {
        try {
            if ($proposal->status !== Proposal::STATUS_DRAFT) {
                throw new \Exception('只有草稿状态的提案可以审核');
            }

            $proposal->update([
                'status' => Proposal::STATUS_CANCELLED,
                'metadata' => array_merge($proposal->metadata ?? [], [
                    'rejection_reason' => $reason,
                    'rejected_by' => $admin->id,
                    'rejected_at' => now(),
                ]),
            ]);

            // 返还创建提案的积分
            $proposal->creator->addPoints(
                self::MIN_POINTS_TO_CREATE_PROPOSAL,
                'proposal_refund',
                "提案《{$proposal->title}》被拒绝，积分返还"
            );

            SystemLog::logUserAction(
                'proposal_rejected',
                "提案审核拒绝: {$proposal->title}",
                [
                    'proposal_id' => $proposal->id,
                    'creator_id' => $proposal->creator_id,
                    'reason' => $reason,
                    'refund' => self::MIN_POINTS_TO_CREATE_PROPOSAL,
                ],
                $admin->id
            );

            return $proposal;

        } catch (\Exception $e) {
            throw new \Exception('提案拒绝失败: ' . $e->getMessage());
        }
    }

    public function castVote(Proposal $proposal, User $user, $position, $voteStrength = 1, $justification = '')
    {
        try {
            // 验证投票权限
            $this->validateVotingPermission($proposal, $user, $voteStrength);

            return DB::transaction(function () use ($proposal, $user, $position, $voteStrength, $justification) {
                // 计算二次方投票成本
                $quadraticCost = $voteStrength * $voteStrength;

                // 扣除积分
                $user->subtractPoints(
                    $quadraticCost,
                    'proposal_vote',
                    "投票: {$proposal->title}",
                    $proposal
                );

                // 创建投票记录
                $vote = ProposalVote::create([
                    'proposal_id' => $proposal->id,
                    'user_id' => $user->id,
                    'position' => $position,
                    'vote_strength' => $voteStrength,
                    'points_cost' => $quadraticCost,
                    'justification' => $justification,
                ]);

                // 更新提案投票统计
                $this->updateProposalVoteCounts($proposal);

                SystemLog::logUserAction(
                    'vote_cast',
                    "投票: {$proposal->title}",
                    [
                        'proposal_id' => $proposal->id,
                        'position' => $position,
                        'vote_strength' => $voteStrength,
                        'points_cost' => $quadraticCost,
                    ],
                    $user->id
                );

                return $vote;
            });

        } catch (\Exception $e) {
            throw new \Exception('投票失败: ' . $e->getMessage());
        }
    }

    public function finalizeProposal(Proposal $proposal)
    {
        try {
            if ($proposal->status !== Proposal::STATUS_ACTIVE) {
                throw new \Exception('只有活跃状态的提案可以结束投票');
            }

            if (!$proposal->hasEnded()) {
                throw new \Exception('投票尚未结束');
            }

            return DB::transaction(function () use ($proposal) {
                // 更新投票统计
                $this->updateProposalVoteCounts($proposal);

                // 计算结果
                $totalVotes = $proposal->vote_count_for + $proposal->vote_count_against;
                $approvalRate = $totalVotes > 0 ? $proposal->vote_count_for / $totalVotes : 0;

                if ($approvalRate >= self::APPROVAL_THRESHOLD) {
                    $proposal->result = Proposal::RESULT_APPROVED;
                } elseif ($proposal->vote_count_against > $proposal->vote_count_for) {
                    $proposal->result = Proposal::RESULT_REJECTED;
                } else {
                    $proposal->result = Proposal::RESULT_TIED;
                }

                $proposal->status = Proposal::STATUS_ENDED;
                $proposal->save();

                // 处理奖励
                $this->processProposalRewards($proposal);

                SystemLog::logUserAction(
                    'proposal_finalized',
                    "提案投票结束: {$proposal->title}",
                    [
                        'proposal_id' => $proposal->id,
                        'result' => $proposal->result,
                        'approval_rate' => $approvalRate,
                        'total_votes' => $totalVotes,
                        'total_points_spent' => $proposal->total_points_spent,
                    ]
                );

                return $proposal;
            });

        } catch (\Exception $e) {
            throw new \Exception('结束投票失败: ' . $e->getMessage());
        }
    }

    protected function processProposalRewards(Proposal $proposal)
    {
        $totalPointsSpent = $proposal->total_points_spent;

        if ($totalPointsSpent > 0 && $proposal->result === Proposal::RESULT_APPROVED) {
            // 计算创建者奖励 (总投票积分的10%)
            $creatorReward = $totalPointsSpent * self::PROPOSAL_CREATOR_REWARD_RATE;

            // 检查公共积分池余额
            $publicPoolBalance = $this->pointsService->getPublicPoolBalance();

            if ($publicPoolBalance >= $creatorReward) {
                // 公共积分充足，给予全额奖励
                $this->pointsService->subtractFromPublicPool($creatorReward, '提案通过奖励');
                $actualReward = $creatorReward;
            } else {
                // 公共积分不足，给予全部公共积分
                $this->pointsService->subtractFromPublicPool($publicPoolBalance, '提案通过奖励');
                $actualReward = $publicPoolBalance;
            }

            if ($actualReward > 0) {
                $proposal->creator->addPoints(
                    $actualReward,
                    'governance_reward',
                    "提案《{$proposal->title}》通过奖励",
                    $proposal
                );
            }

            // 更新提案元数据
            $proposal->update([
                'metadata' => array_merge($proposal->metadata ?? [], [
                    'creator_reward' => $actualReward,
                    'total_points_spent' => $totalPointsSpent,
                    'reward_calculated_at' => now(),
                ]),
            ]);
        }
    }

    protected function updateProposalVoteCounts(Proposal $proposal)
    {
        $votes = $proposal->votes()->get();

        $forVotes = $votes->where('position', ProposalVote::POSITION_FOR);
        $againstVotes = $votes->where('position', ProposalVote::POSITION_AGAINST);
        $abstainVotes = $votes->where('position', ProposalVote::POSITION_ABSTAIN);

        $proposal->update([
            'vote_count_for' => $forVotes->sum('vote_strength'),
            'vote_count_against' => $againstVotes->sum('vote_strength'),
            'vote_count_abstain' => $abstainVotes->sum('vote_strength'),
            'points_spent_for' => $forVotes->sum('points_cost'),
            'points_spent_against' => $againstVotes->sum('points_cost'),
            'points_spent_abstain' => $abstainVotes->sum('points_cost'),
        ]);
    }

    protected function validateProposalCreationPermission(User $user)
    {
        // 检查积分余额
        if ($user->points_balance < self::MIN_POINTS_TO_CREATE_PROPOSAL) {
            throw new \Exception('积分不足，至少需要 ' . number_format(self::MIN_POINTS_TO_CREATE_PROPOSAL, 8) . ' 积分');
        }

        // 检查是否使用鲸探NFT作为头像
        if (!$this->isUsingWhaleAvatar($user)) {
            throw new \Exception('请先设置鲸探NFT作为头像');
        }

        // 检查是否绑定鲸探账户
        if (!$user->whale_account_id) {
            throw new \Exception('请先绑定鲸探账户');
        }

        // 检查是否拥有NFT
        if ($user->whale_nft_count <= 0) {
            throw new \Exception('需要拥有鲸探NFT才能创建提案');
        }
    }

    protected function validateVotingPermission(Proposal $proposal, User $user, $voteStrength)
    {
        // 检查提案状态
        if (!$proposal->isActive()) {
            throw new \Exception('提案不在投票期间');
        }

        // 检查是否已经投票
        if ($proposal->votes()->where('user_id', $user->id)->exists()) {
            throw new \Exception('您已经投过票了');
        }

        // 检查积分要求
        if ($user->points_balance < $proposal->min_points_to_vote) {
            throw new \Exception('积分不足，无法参与投票');
        }

        // 检查二次方投票成本
        $quadraticCost = $voteStrength * $voteStrength;
        if ($user->points_balance < $quadraticCost) {
            throw new \Exception('积分不足以支付投票成本');
        }

        // 检查投票强度限制
        $maxVoteStrength = config('services.governance.voting.max_vote_strength', 100);
        if ($voteStrength > $maxVoteStrength) {
            throw new \Exception("投票强度不能超过 {$maxVoteStrength}");
        }
    }

    protected function isUsingWhaleAvatar(User $user)
    {
        return $user->avatar && (
            strpos($user->avatar, 'whale_nft_') === 0 ||
            strpos($user->avatar, 'jingtan_') === 0
        );
    }

    public function getActiveProposals($limit = 20)
    {
        return Proposal::where('status', Proposal::STATUS_ACTIVE)
            ->with(['creator', 'votes'])
            ->orderBy('voting_end_at', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getProposalHistory($limit = 50)
    {
        return Proposal::where('status', Proposal::STATUS_ENDED)
            ->with(['creator', 'votes'])
            ->orderBy('voting_end_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserVotingHistory(User $user, $limit = 50)
    {
        return ProposalVote::where('user_id', $user->id)
            ->with(['proposal'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getGovernanceStats()
    {
        $cacheKey = 'governance_stats';

        return Cache::remember($cacheKey, 600, function () {
            return [
                'total_proposals' => Proposal::count(),
                'active_proposals' => Proposal::where('status', Proposal::STATUS_ACTIVE)->count(),
                'ended_proposals' => Proposal::where('status', Proposal::STATUS_ENDED)->count(),
                'approved_proposals' => Proposal::where('result', Proposal::RESULT_APPROVED)->count(),
                'total_votes' => ProposalVote::count(),
                'total_points_spent_voting' => ProposalVote::sum('points_cost'),
                'unique_voters' => ProposalVote::distinct('user_id')->count('user_id'),
                'unique_proposal_creators' => Proposal::distinct('creator_id')->count('creator_id'),
                'average_voting_participation' => $this->calculateAverageVotingParticipation(),
            ];
        });
    }

    protected function calculateAverageVotingParticipation()
    {
        $endedProposals = Proposal::where('status', Proposal::STATUS_ENDED)->get();

        if ($endedProposals->isEmpty()) {
            return 0;
        }

        $totalParticipation = 0;
        $totalEligibleUsers = User::where('points_balance', '>=', 1.00000000)->count();

        foreach ($endedProposals as $proposal) {
            $uniqueVoters = $proposal->votes()->distinct('user_id')->count('user_id');
            $participation = $totalEligibleUsers > 0 ? ($uniqueVoters / $totalEligibleUsers) * 100 : 0;
            $totalParticipation += $participation;
        }

        return $endedProposals->count() > 0 ? $totalParticipation / $endedProposals->count() : 0;
    }

    public function getVotingPowerDistribution(Proposal $proposal)
    {
        $votes = $proposal->votes()->with('user')->get();

        $distribution = [
            'by_position' => [
                'for' => ['count' => 0, 'strength' => 0, 'points' => 0],
                'against' => ['count' => 0, 'strength' => 0, 'points' => 0],
                'abstain' => ['count' => 0, 'strength' => 0, 'points' => 0],
            ],
            'by_user_level' => [
                'whale_users' => ['count' => 0, 'strength' => 0],
                'regular_users' => ['count' => 0, 'strength' => 0],
            ],
            'vote_strength_histogram' => [],
        ];

        foreach ($votes as $vote) {
            $position = $vote->position;
            $distribution['by_position'][$position]['count']++;
            $distribution['by_position'][$position]['strength'] += $vote->vote_strength;
            $distribution['by_position'][$position]['points'] += $vote->points_cost;

            if ($vote->user->whale_account_id) {
                $distribution['by_user_level']['whale_users']['count']++;
                $distribution['by_user_level']['whale_users']['strength'] += $vote->vote_strength;
            } else {
                $distribution['by_user_level']['regular_users']['count']++;
                $distribution['by_user_level']['regular_users']['strength'] += $vote->vote_strength;
            }

            $strengthBucket = min(floor($vote->vote_strength / 5) * 5, 50);
            $distribution['vote_strength_histogram'][$strengthBucket] =
                ($distribution['vote_strength_histogram'][$strengthBucket] ?? 0) + 1;
        }

        return $distribution;
    }

    public function canUserCreateProposal(User $user)
    {
        try {
            $this->validateProposalCreationPermission($user);
            return ['can_create' => true, 'message' => '可以创建提案'];
        } catch (\Exception $e) {
            return ['can_create' => false, 'message' => $e->getMessage()];
        }
    }

    public function canUserVoteOnProposal(Proposal $proposal, User $user)
    {
        try {
            $this->validateVotingPermission($proposal, $user, 1);
            return ['can_vote' => true, 'message' => '可以投票'];
        } catch (\Exception $e) {
            return ['can_vote' => false, 'message' => $e->getMessage()];
        }
    }

    public function calculateVotingCost($voteStrength)
    {
        return $voteStrength * $voteStrength;
    }

    public function getMaxVoteStrengthForUser(User $user)
    {
        $balance = $user->points_balance;

        // 计算用户余额能承担的最大投票强度
        $maxStrength = floor(sqrt($balance));

        // 应用系统限制
        $systemMax = config('services.governance.voting.max_vote_strength', 100);

        return min($maxStrength, $systemMax);
    }
}