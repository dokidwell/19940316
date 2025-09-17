<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointTransaction;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PointsService
{
    const PRECISION = 8;
    const MIN_TRANSACTION = 0.00000001;
    const MAX_DAILY_EARNING = 1000.00000000;

    protected $transactionTypes = [
        'view_reward' => '浏览奖励',
        'like_reward' => '点赞奖励',
        'download_reward' => '下载奖励',
        'share_reward' => '分享奖励',
        'comment_reward' => '评论奖励',
        'daily_checkin' => '每日签到',
        'whale_nft_bonus' => '鲸探NFT奖励',
        'creation_reward' => '创作奖励',
        'sale_revenue' => '销售收入',
        'artwork_purchase' => '作品购买',
        'proposal_creation' => '提案创建',
        'proposal_vote' => '提案投票',
        'governance_reward' => '治理奖励',
        'system_adjustment' => '系统调整',
        'admin_grant' => '管理员发放',
        'referral_bonus' => '推荐奖励',
        'public_pool_income' => '公共池收入',
        'public_pool_expense' => '公共池支出',
        'point_burn' => '积分销毁',
    ];

    public function getUserBalance(User $user)
    {
        return number_format($user->points_balance, self::PRECISION, '.', '');
    }

    public function getUserStats(User $user)
    {
        $totalEarned = $user->total_points_earned ?: 0;
        $totalSpent = $user->total_points_spent ?: 0;
        $currentBalance = $user->points_balance ?: 0;

        $todayEarnings = PointTransaction::where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->whereDate('created_at', today())
            ->sum('amount');

        $recentTransactions = PointTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'type_display' => $this->transactionTypes[$transaction->type] ?? $transaction->type,
                    'amount' => number_format($transaction->amount, self::PRECISION, '.', ''),
                    'balance_after' => number_format($transaction->balance_after, self::PRECISION, '.', ''),
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'is_income' => $transaction->amount > 0,
                ];
            });

        return [
            'current_balance' => number_format($currentBalance, self::PRECISION, '.', ''),
            'total_earned' => number_format($totalEarned, self::PRECISION, '.', ''),
            'total_spent' => number_format($totalSpent, self::PRECISION, '.', ''),
            'today_earnings' => number_format($todayEarnings, self::PRECISION, '.', ''),
            'recent_transactions' => $recentTransactions,
            'whale_multiplier' => $user->getWhaleRewardMultiplier(),
            'verification_level' => $user->verification_level ?? 1,
        ];
    }

    public function getTransactionHistory(User $user, $page = 1, $perPage = 50, $type = null)
    {
        $query = PointTransaction::where('user_id', $user->id);

        if ($type && isset($this->transactionTypes[$type])) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $transactions->getCollection()->transform(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_display' => $this->transactionTypes[$transaction->type] ?? $transaction->type,
                'amount' => number_format($transaction->amount, self::PRECISION, '.', ''),
                'balance_after' => number_format($transaction->balance_after, self::PRECISION, '.', ''),
                'description' => $transaction->description,
                'created_at' => $transaction->created_at,
                'formatted_date' => $transaction->created_at->format('Y-m-d H:i:s'),
                'is_income' => $transaction->amount > 0,
                'related_type' => $transaction->related_type,
                'related_id' => $transaction->related_id,
                'metadata' => $transaction->metadata,
            ];
        });

        return $transactions;
    }

    public function addPoints(User $user, $amount, $type, $description = null, $relatedModel = null, $metadata = [])
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('奖励金额必须大于0');
        }

        if ($amount < self::MIN_TRANSACTION) {
            throw new \InvalidArgumentException('交易金额过小');
        }

        $amount = round($amount, self::PRECISION);

        return DB::transaction(function () use ($user, $amount, $type, $description, $relatedModel, $metadata) {
            $user = $user->lockForUpdate()->find($user->id);

            $newBalance = $user->points_balance + $amount;

            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'related_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_id' => $relatedModel ? $relatedModel->id : null,
                'metadata' => $metadata,
            ]);

            $user->update([
                'points_balance' => $newBalance,
                'total_points_earned' => $user->total_points_earned + $amount,
            ]);

            $this->logPointsOperation('add', $user, $amount, $type, $description);

            return $transaction;
        });
    }

    public function subtractPoints(User $user, $amount, $type, $description = null, $relatedModel = null, $metadata = [])
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('扣除金额必须大于0');
        }

        if ($amount < self::MIN_TRANSACTION) {
            throw new \InvalidArgumentException('交易金额过小');
        }

        $amount = round($amount, self::PRECISION);

        return DB::transaction(function () use ($user, $amount, $type, $description, $relatedModel, $metadata) {
            $user = $user->lockForUpdate()->find($user->id);

            if ($user->points_balance < $amount) {
                throw new \Exception('积分余额不足');
            }

            $newBalance = $user->points_balance - $amount;

            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'related_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_id' => $relatedModel ? $relatedModel->id : null,
                'metadata' => $metadata,
            ]);

            $user->update([
                'points_balance' => $newBalance,
                'total_points_spent' => $user->total_points_spent + $amount,
            ]);

            $this->logPointsOperation('subtract', $user, $amount, $type, $description);

            return $transaction;
        });
    }

    public function transferPoints(User $fromUser, User $toUser, $amount, $description = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('转账金额必须大于0');
        }

        if ($amount < self::MIN_TRANSACTION) {
            throw new \InvalidArgumentException('转账金额过小');
        }

        $amount = round($amount, self::PRECISION);

        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description) {
            $this->subtractPoints($fromUser, $amount, 'transfer_out', $description, $toUser);
            $this->addPoints($toUser, $amount, 'transfer_in', $description, $fromUser);

            SystemLog::logUserAction(
                'points_transfer',
                "积分转账: {$amount}",
                [
                    'from_user_id' => $fromUser->id,
                    'to_user_id' => $toUser->id,
                    'amount' => $amount,
                    'description' => $description,
                ],
                $fromUser->id
            );
        });
    }

    public function burnPoints($amount, $reason = '系统销毁', $userId = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('销毁金额必须大于0');
        }

        $amount = round($amount, self::PRECISION);

        $burnTransaction = PointTransaction::create([
            'user_id' => $userId,
            'type' => 'point_burn',
            'amount' => -$amount,
            'balance_after' => 0,
            'description' => $reason,
        ]);

        SystemLog::logUserAction(
            'point_burn',
            "积分销毁: {$amount}",
            [
                'amount' => $amount,
                'reason' => $reason,
                'user_id' => $userId,
            ],
            $userId
        );

        return $burnTransaction;
    }

    public function getSystemStats()
    {
        $cacheKey = 'points_system_stats';

        return Cache::remember($cacheKey, 600, function () {
            $totalUsers = User::count();
            $totalPointsInCirculation = User::sum('points_balance');
            $totalPointsEarned = User::sum('total_points_earned');
            $totalPointsSpent = User::sum('total_points_spent');
            $totalBurned = PointTransaction::where('type', 'point_burn')->sum('amount');

            $publicPoolBalance = $this->getPublicPoolBalance();

            $topHolders = User::where('points_balance', '>', 0)
                ->orderBy('points_balance', 'desc')
                ->limit(10)
                ->get(['id', 'hoho_id', 'name', 'points_balance'])
                ->map(function ($user) {
                    return [
                        'hoho_id' => $user->hoho_id,
                        'name' => $user->name,
                        'balance' => number_format($user->points_balance, self::PRECISION, '.', ''),
                    ];
                });

            $dailyStats = $this->getDailyStats();

            return [
                'total_users' => $totalUsers,
                'total_points_in_circulation' => number_format($totalPointsInCirculation, self::PRECISION, '.', ''),
                'total_points_earned' => number_format($totalPointsEarned, self::PRECISION, '.', ''),
                'total_points_spent' => number_format($totalPointsSpent, self::PRECISION, '.', ''),
                'total_points_burned' => number_format(abs($totalBurned), self::PRECISION, '.', ''),
                'public_pool_balance' => number_format($publicPoolBalance, self::PRECISION, '.', ''),
                'top_holders' => $topHolders,
                'daily_stats' => $dailyStats,
            ];
        });
    }

    public function getPublicPoolBalance()
    {
        $income = PointTransaction::where('type', 'public_pool_income')->sum('amount');
        $expense = PointTransaction::where('type', 'public_pool_expense')->sum('amount');

        return $income + $expense;
    }

    public function addToPublicPool($amount, $reason = '公共池收入')
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('金额必须大于0');
        }

        $amount = round($amount, self::PRECISION);

        $transaction = PointTransaction::create([
            'user_id' => null,
            'type' => 'public_pool_income',
            'amount' => $amount,
            'balance_after' => $this->getPublicPoolBalance() + $amount,
            'description' => $reason,
        ]);

        SystemLog::logUserAction(
            'public_pool_adjustment',
            "公共池增加: {$amount}",
            [
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $this->getPublicPoolBalance(),
            ]
        );

        return $transaction;
    }

    public function subtractFromPublicPool($amount, $reason = '公共池支出')
    {
        $currentBalance = $this->getPublicPoolBalance();

        if ($currentBalance < $amount) {
            throw new \Exception('公共池余额不足');
        }

        $amount = round($amount, self::PRECISION);

        $transaction = PointTransaction::create([
            'user_id' => null,
            'type' => 'public_pool_expense',
            'amount' => -$amount,
            'balance_after' => $currentBalance - $amount,
            'description' => $reason,
        ]);

        SystemLog::logUserAction(
            'public_pool_adjustment',
            "公共池减少: {$amount}",
            [
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $this->getPublicPoolBalance(),
            ]
        );

        return $transaction;
    }

    protected function getDailyStats()
    {
        $today = today();

        return [
            'transactions_today' => PointTransaction::whereDate('created_at', $today)->count(),
            'points_earned_today' => PointTransaction::whereDate('created_at', $today)
                ->where('amount', '>', 0)
                ->sum('amount'),
            'points_spent_today' => PointTransaction::whereDate('created_at', $today)
                ->where('amount', '<', 0)
                ->sum('amount'),
            'active_users_today' => PointTransaction::whereDate('created_at', $today)
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    protected function logPointsOperation($operation, User $user, $amount, $type, $description)
    {
        SystemLog::logUserAction(
            'point_transaction',
            "{$operation}: {$amount}积分",
            [
                'operation' => $operation,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'balance_after' => $user->fresh()->points_balance,
            ],
            $user->id
        );
    }

    public function getTransactionTypes()
    {
        return $this->transactionTypes;
    }

    public function validateAmount($amount)
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('金额必须是数字');
        }

        if ($amount < self::MIN_TRANSACTION) {
            throw new \InvalidArgumentException('金额不能小于最小交易额度');
        }

        if ($amount > self::MAX_DAILY_EARNING) {
            throw new \InvalidArgumentException('金额超过每日最大限额');
        }

        return round($amount, self::PRECISION);
    }

    public function formatAmount($amount)
    {
        return number_format($amount, self::PRECISION, '.', '');
    }

    public function getRecentActivity($limit = 100)
    {
        return PointTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => $transaction->user ? [
                        'hoho_id' => $transaction->user->hoho_id,
                        'name' => $transaction->user->name,
                    ] : null,
                    'type' => $transaction->type,
                    'type_display' => $this->transactionTypes[$transaction->type] ?? $transaction->type,
                    'amount' => $this->formatAmount($transaction->amount),
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'is_income' => $transaction->amount > 0,
                ];
            });
    }
}