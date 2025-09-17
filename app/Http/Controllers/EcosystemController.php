<?php

namespace App\Http\Controllers;

use App\Services\PointsService;
use App\Services\GovernanceService;
use App\Services\WhaleService;
use App\Models\User;
use App\Models\Proposal;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EcosystemController extends Controller
{
    protected $pointsService;
    protected $governanceService;
    protected $whaleService;

    public function __construct(
        PointsService $pointsService,
        GovernanceService $governanceService,
        WhaleService $whaleService
    ) {
        $this->pointsService = $pointsService;
        $this->governanceService = $governanceService;
        $this->whaleService = $whaleService;
    }

    /**
     * 生態系統總覽頁面
     */
    public function index()
    {
        $stats = $this->getEcosystemStats();
        $recentActivity = $this->getRecentActivity();
        $topContributors = $this->getTopContributors();
        $governanceOverview = $this->getGovernanceOverview();
        $whaleIntegrationStats = $this->getWhaleIntegrationStats();

        return view('ecosystem.index', compact(
            'stats',
            'recentActivity',
            'topContributors',
            'governanceOverview',
            'whaleIntegrationStats'
        ));
    }

    /**
     * 社區治理頁面
     */
    public function governance()
    {
        $activeProposals = $this->governanceService->getActiveProposals(10);
        $recentProposals = $this->governanceService->getProposalHistory(10);
        $governanceStats = $this->governanceService->getGovernanceStats();

        $user = Auth::user();
        $canCreateProposal = $user ? $this->governanceService->canUserCreateProposal($user) : false;

        return view('ecosystem.governance', compact(
            'activeProposals',
            'recentProposals',
            'governanceStats',
            'canCreateProposal'
        ));
    }

    /**
     * 積分透明度頁面
     */
    public function transparency()
    {
        $transparencyData = $this->pointsService->getTransparencyDashboard();
        $systemStats = $this->pointsService->getSystemStats();
        $publicPoolStats = $this->pointsService->getPublicPoolStats();

        return view('ecosystem.transparency', compact(
            'transparencyData',
            'systemStats',
            'publicPoolStats'
        ));
    }

    /**
     * 鯨探集成頁面
     */
    public function whale()
    {
        $whaleStats = $this->getWhaleIntegrationStats();
        $recentRewards = $this->getRecentWhaleRewards();
        $topCollectors = $this->getTopWhaleCollectors();

        $user = Auth::user();
        $userWhaleAccount = $user ? $user->whaleAccount : null;

        return view('ecosystem.whale', compact(
            'whaleStats',
            'recentRewards',
            'topCollectors',
            'userWhaleAccount'
        ));
    }

    /**
     * 任務中心頁面
     */
    public function tasks()
    {
        $tasks = $this->pointsService->getAvailableTasks(Auth::user());
        $userProgress = $this->getUserTaskProgress();
        $leaderboard = $this->pointsService->getLeaderboard(10);

        return view('ecosystem.tasks', compact(
            'tasks',
            'userProgress',
            'leaderboard'
        ));
    }

    /**
     * 開發者API頁面
     */
    public function developers()
    {
        $apiStats = $this->getApiStats();
        $apiDocumentation = $this->getApiDocumentation();

        return view('ecosystem.developers', compact(
            'apiStats',
            'apiDocumentation'
        ));
    }

    /**
     * 獲取生態系統統計數據
     */
    private function getEcosystemStats()
    {
        return [
            'total_users' => User::count(),
            'active_users_24h' => User::where('last_activity_at', '>=', now()->subDay())->count(),
            'total_points_circulating' => User::sum('points_balance'),
            'total_transactions' => PointTransaction::count(),
            'active_proposals' => Proposal::where('status', 'active')->count(),
            'whale_connected_users' => User::whereHas('whaleAccount')->count(),
            'total_governance_participation' => Proposal::withCount('votes')->get()->sum('votes_count'),
        ];
    }

    /**
     * 獲取最近活動
     */
    private function getRecentActivity()
    {
        $recentTransactions = PointTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentProposals = Proposal::with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'transactions' => $recentTransactions,
            'proposals' => $recentProposals,
        ];
    }

    /**
     * 獲取頂級貢獻者
     */
    private function getTopContributors()
    {
        return [
            'by_points' => User::orderBy('points_balance', 'desc')->limit(10)->get(),
            'by_governance' => User::withCount('proposals')
                ->orderBy('proposals_count', 'desc')
                ->limit(10)
                ->get(),
            'by_whale_activity' => User::whereHas('whaleAccount')
                ->withCount('whaleRewards')
                ->orderBy('whale_rewards_count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * 獲取治理概覽
     */
    private function getGovernanceOverview()
    {
        return $this->governanceService->getGovernanceStats();
    }

    /**
     * 獲取鯨探集成統計
     */
    private function getWhaleIntegrationStats()
    {
        return [
            'connected_accounts' => User::whereHas('whaleAccount')->count(),
            'total_collections_synced' => \DB::table('nft_collections')->count(),
            'total_rewards_distributed' => PointTransaction::where('type', 'whale_reward')->sum('amount'),
            'average_daily_rewards' => PointTransaction::where('type', 'whale_reward')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('amount') / 30,
        ];
    }

    /**
     * 獲取用戶任務進度
     */
    private function getUserTaskProgress()
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();

        return [
            'daily_checkin_streak' => $user->daily_checkin_streak ?? 0,
            'total_points_earned' => PointTransaction::where('user_id', $user->id)
                ->where('amount', '>', 0)
                ->sum('amount'),
            'governance_participation' => $user->proposalVotes()->count(),
            'whale_account_connected' => $user->whaleAccount()->exists(),
        ];
    }

    /**
     * 獲取最近鯨探獎勵
     */
    private function getRecentWhaleRewards()
    {
        return PointTransaction::with('user')
            ->where('type', 'whale_reward')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * 獲取頂級鯨探收藏者
     */
    private function getTopWhaleCollectors()
    {
        return User::whereHas('whaleAccount')
            ->withCount('nftCollections')
            ->orderBy('nft_collections_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 獲取API統計數據
     */
    private function getApiStats()
    {
        return [
            'total_endpoints' => 45,
            'daily_requests' => rand(1000, 5000), // 模擬數據
            'average_response_time' => '120ms',
            'uptime' => '99.9%',
        ];
    }

    /**
     * 獲取API文檔
     */
    private function getApiDocumentation()
    {
        return [
            [
                'name' => '用戶管理 API',
                'description' => '用戶註冊、登錄、個人資料管理',
                'endpoint_count' => 8,
                'version' => 'v1',
            ],
            [
                'name' => '積分系統 API',
                'description' => '積分查詢、交易記錄、透明度數據',
                'endpoint_count' => 12,
                'version' => 'v1',
            ],
            [
                'name' => '治理系統 API',
                'description' => '提案創建、投票、治理統計',
                'endpoint_count' => 10,
                'version' => 'v1',
            ],
            [
                'name' => '鯨探集成 API',
                'description' => 'NFT數據同步、獎勵系統、定價',
                'endpoint_count' => 15,
                'version' => 'v1',
            ],
        ];
    }

    /**
     * API: 獲取生態系統統計數據
     */
    public function getStatsApi()
    {
        try {
            $stats = $this->getEcosystemStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取統計數據失敗'
            ], 500);
        }
    }

    /**
     * API: 獲取最近活動
     */
    public function getActivityApi()
    {
        try {
            $activity = $this->getRecentActivity();

            return response()->json([
                'success' => true,
                'data' => $activity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取活動數據失敗'
            ], 500);
        }
    }
}