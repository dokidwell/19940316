<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\User;
use App\Models\PointTransaction;
use App\Models\Artwork;
use App\Models\Proposal;
use App\Models\WhaleAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransparencyService
{
    protected $eventTypes = [
        'user_register' => '用户注册',
        'user_login' => '用户登入',
        'whale_account_bind' => '鲸探账户绑定',
        'whale_account_sync' => '鲸探账户同步',
        'whale_nft_reward' => '鲸探NFT空投奖励',
        'point_transaction' => '积分交易',
        'artwork_upload' => '作品上传',
        'artwork_approved' => '作品审核通过',
        'artwork_rejected' => '作品审核拒绝',
        'artwork_sale' => '作品交易',
        'proposal_created' => '提案创建',
        'proposal_approved' => '提案审核通过',
        'proposal_rejected' => '提案审核拒绝',
        'proposal_finalized' => '提案投票结束',
        'vote_cast' => '投票参与',
        'public_pool_adjustment' => '公共积分调整',
        'point_burn' => '积分销毁',
        'whale_pricing_update' => '鲸探定价更新',
        'admin_action' => '管理员操作',
    ];

    public function getPublicStats()
    {
        $cacheKey = 'transparency_public_stats';

        return Cache::remember($cacheKey, 600, function () {
            $totalUsers = User::count();
            $totalPoints = User::sum('points_balance');
            $publicPoolBalance = $this->getPublicPoolBalance();
            $burnedPoints = abs(PointTransaction::where('type', 'point_burn')->sum('amount'));

            $whaleUsers = User::whereNotNull('whale_account_id')->count();
            $totalArtworks = Artwork::count();
            $totalProposals = Proposal::count();

            $todayTransactions = PointTransaction::whereDate('created_at', today())->count();
            $todayNewUsers = User::whereDate('created_at', today())->count();

            return [
                'community_overview' => [
                    'total_users' => $totalUsers,
                    'whale_verified_users' => $whaleUsers,
                    'total_artworks' => $totalArtworks,
                    'total_proposals' => $totalProposals,
                    'today_new_users' => $todayNewUsers,
                ],
                'economic_overview' => [
                    'total_points_in_circulation' => number_format($totalPoints, 8, '.', ''),
                    'public_pool_balance' => number_format($publicPoolBalance, 8, '.', ''),
                    'total_burned_points' => number_format($burnedPoints, 8, '.', ''),
                    'today_transactions' => $todayTransactions,
                ],
                'last_updated' => now(),
            ];
        });
    }

    public function getRecentEvents($page = 1, $perPage = 50, $type = null, $search = null)
    {
        $query = SystemLog::forTransparency();

        if ($type && isset($this->eventTypes[$type])) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('hoho_id', 'like', "%{$search}%");
                  });
            });
        }

        $events = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $events->getCollection()->transform(function ($event) {
            return $this->formatEventForPublic($event);
        });

        return $events;
    }

    protected function formatEventForPublic($event)
    {
        $formatted = [
            'id' => $event->id,
            'type' => $event->type,
            'type_display' => $this->eventTypes[$event->type] ?? $event->type,
            'description' => $event->description,
            'user' => $event->user ? [
                'hoho_id' => $event->user->hoho_id,
                'name' => $this->maskUserName($event->user->name),
            ] : null,
            'created_at' => $event->created_at,
            'formatted_time' => $event->created_at->format('Y-m-d H:i:s'),
            'data' => $this->sanitizeEventData($event->data, $event->type),
        ];

        return $formatted;
    }

    protected function maskUserName($name)
    {
        if (strlen($name) <= 2) {
            return $name;
        }

        $firstChar = mb_substr($name, 0, 1);
        $lastChar = mb_substr($name, -1);
        $middleLength = mb_strlen($name) - 2;

        return $firstChar . str_repeat('*', min($middleLength, 3)) . $lastChar;
    }

    protected function sanitizeEventData($data, $type)
    {
        if (!$data) return null;

        $sensitiveFields = ['password', 'token', 'secret', 'key', 'email', 'phone'];

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                continue;
            }

            if (is_numeric($value) && strpos($key, 'amount') !== false) {
                $sanitized[$key] = number_format($value, 8, '.', '');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeEventData($value, $type);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public function getPointsFlowAnalysis($days = 30)
    {
        $cacheKey = "transparency_points_flow_{$days}";

        return Cache::remember($cacheKey, 1800, function () use ($days) {
            $startDate = now()->subDays($days);

            $incomeSources = PointTransaction::where('amount', '>', 0)
                ->where('created_at', '>=', $startDate)
                ->select('type', DB::raw('SUM(amount) as total'))
                ->groupBy('type')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->type,
                        'type_display' => PointTransaction::TYPES[$item->type] ?? $item->type,
                        'total' => number_format($item->total, 8, '.', ''),
                    ];
                });

            $expenseSinks = PointTransaction::where('amount', '<', 0)
                ->where('created_at', '>=', $startDate)
                ->select('type', DB::raw('SUM(ABS(amount)) as total'))
                ->groupBy('type')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->type,
                        'type_display' => PointTransaction::TYPES[$item->type] ?? $item->type,
                        'total' => number_format($item->total, 8, '.', ''),
                    ];
                });

            $dailyFlow = PointTransaction::where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income'),
                    DB::raw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense'),
                    DB::raw('COUNT(*) as transactions')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'period' => "{$days}天",
                'income_sources' => $incomeSources,
                'expense_sinks' => $expenseSinks,
                'daily_flow' => $dailyFlow,
                'generated_at' => now(),
            ];
        });
    }

    public function getGovernanceActivity($days = 30)
    {
        $startDate = now()->subDays($days);

        $proposals = Proposal::where('created_at', '>=', $startDate)
            ->with(['creator', 'votes'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($proposal) {
                return [
                    'id' => $proposal->id,
                    'title' => $proposal->title,
                    'creator' => [
                        'hoho_id' => $proposal->creator->hoho_id,
                        'name' => $this->maskUserName($proposal->creator->name),
                    ],
                    'category' => $proposal->category_display,
                    'status' => $proposal->status,
                    'vote_count' => $proposal->total_votes,
                    'points_spent' => number_format($proposal->total_points_spent, 8, '.', ''),
                    'result' => $proposal->result,
                    'created_at' => $proposal->created_at,
                ];
            });

        $votingStats = [
            'total_proposals' => $proposals->count(),
            'active_proposals' => $proposals->where('status', 'active')->count(),
            'completed_proposals' => $proposals->where('status', 'ended')->count(),
            'total_votes_cast' => $proposals->sum('vote_count'),
            'total_points_spent_voting' => $proposals->sum('points_spent'),
        ];

        return [
            'proposals' => $proposals,
            'stats' => $votingStats,
            'period' => "{$days}天",
        ];
    }

    public function getMarketplaceActivity($days = 30)
    {
        $startDate = now()->subDays($days);

        $sales = SystemLog::where('type', 'artwork_sale')
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sale) {
                $data = $sale->data;
                return [
                    'artwork_id' => $data['artwork_id'] ?? null,
                    'buyer' => $sale->user ? [
                        'hoho_id' => $sale->user->hoho_id,
                        'name' => $this->maskUserName($sale->user->name),
                    ] : null,
                    'amount' => number_format($data['amount'] ?? 0, 8, '.', ''),
                    'creator_share' => number_format($data['creator_share'] ?? 0, 8, '.', ''),
                    'public_pool_share' => number_format($data['public_pool_share'] ?? 0, 8, '.', ''),
                    'burn_amount' => number_format($data['burn_amount'] ?? 0, 8, '.', ''),
                    'created_at' => $sale->created_at,
                ];
            });

        $marketStats = [
            'total_sales' => $sales->count(),
            'total_volume' => number_format($sales->sum(function ($sale) {
                return (float) str_replace(',', '', $sale['amount']);
            }), 8, '.', ''),
            'total_fees_to_creators' => number_format($sales->sum(function ($sale) {
                return (float) str_replace(',', '', $sale['creator_share']);
            }), 8, '.', ''),
            'total_fees_to_public_pool' => number_format($sales->sum(function ($sale) {
                return (float) str_replace(',', '', $sale['public_pool_share']);
            }), 8, '.', ''),
            'total_burned' => number_format($sales->sum(function ($sale) {
                return (float) str_replace(',', '', $sale['burn_amount']);
            }), 8, '.', ''),
        ];

        return [
            'sales' => $sales,
            'stats' => $marketStats,
            'period' => "{$days}天",
        ];
    }

    public function getWhaleActivity($days = 30)
    {
        $startDate = now()->subDays($days);

        $whaleRewards = SystemLog::where('type', 'whale_nft_reward')
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reward) {
                $data = $reward->data;
                return [
                    'user' => $reward->user ? [
                        'hoho_id' => $reward->user->hoho_id,
                        'name' => $this->maskUserName($reward->user->name),
                    ] : null,
                    'total_reward' => number_format($data['total_reward'] ?? 0, 8, '.', ''),
                    'nft_count' => $data['nft_count'] ?? 0,
                    'created_at' => $reward->created_at,
                ];
            });

        $pricingUpdates = SystemLog::where('type', 'whale_pricing_update')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $whaleStats = [
            'total_rewards_distributed' => number_format($whaleRewards->sum(function ($reward) {
                return (float) str_replace(',', '', $reward['total_reward']);
            }), 8, '.', ''),
            'unique_recipients' => $whaleRewards->pluck('user.hoho_id')->unique()->count(),
            'pricing_updates' => $pricingUpdates->count(),
        ];

        return [
            'rewards' => $whaleRewards->take(50),
            'pricing_updates' => $pricingUpdates->take(20),
            'stats' => $whaleStats,
            'period' => "{$days}天",
        ];
    }

    protected function getPublicPoolBalance()
    {
        $income = PointTransaction::where('type', 'public_pool_income')->sum('amount');
        $expense = PointTransaction::where('type', 'public_pool_expense')->sum('amount');

        return $income + $expense;
    }

    public function search($query, $type = null, $limit = 50)
    {
        $searchQuery = SystemLog::forTransparency();

        if ($type) {
            $searchQuery->where('type', $type);
        }

        $searchQuery->where(function ($q) use ($query) {
            $q->where('description', 'like', "%{$query}%");

            if (is_numeric($query)) {
                $q->orWhere('data->amount', 'like', "%{$query}%")
                  ->orWhere('data->artwork_id', $query)
                  ->orWhere('data->proposal_id', $query);
            }
        });

        $results = $searchQuery->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return $this->formatEventForPublic($event);
            });

        return $results;
    }

    public function getEventTypes()
    {
        return $this->eventTypes;
    }

    public function exportTransparencyData($startDate, $endDate, $format = 'json')
    {
        $events = SystemLog::forTransparency()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($event) {
                return $this->formatEventForPublic($event);
            });

        $stats = $this->getPublicStats();
        $pointsFlow = $this->getPointsFlowAnalysis(30);

        $exportData = [
            'export_info' => [
                'generated_at' => now(),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'total_events' => $events->count(),
            ],
            'system_stats' => $stats,
            'points_flow' => $pointsFlow,
            'events' => $events,
        ];

        if ($format === 'csv') {
            return $this->convertToCsv($exportData);
        }

        return $exportData;
    }

    protected function convertToCsv($data)
    {
        $csv = "Event ID,Type,Description,User,Created At,Data\n";

        foreach ($data['events'] as $event) {
            $csv .= sprintf(
                "%s,%s,\"%s\",%s,%s,\"%s\"\n",
                $event['id'],
                $event['type_display'],
                str_replace('"', '""', $event['description']),
                $event['user'] ? $event['user']['hoho_id'] : '',
                $event['formatted_time'],
                str_replace('"', '""', json_encode($event['data']))
            );
        }

        return $csv;
    }
}