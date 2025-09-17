<?php

namespace App\Http\Controllers;

use App\Services\WhaleService;
use App\Services\WhaleRewardService;
use App\Services\WhalePricingService;
use App\Models\User;
use App\Models\WhaleAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WhaleController extends Controller
{
    protected $whaleService;
    protected $rewardService;
    protected $pricingService;

    public function __construct(
        WhaleService $whaleService,
        WhaleRewardService $rewardService,
        WhalePricingService $pricingService
    ) {
        $this->whaleService = $whaleService;
        $this->rewardService = $rewardService;
        $this->pricingService = $pricingService;
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $whaleAccount = $user->whaleAccount;
        $rewardStats = $this->rewardService->getUserRewardStats($user);

        return view('whale.index', compact('user', 'whaleAccount', 'rewardStats'));
    }

    public function bind()
    {
        $user = Auth::user();

        if ($user->whale_account_id) {
            return redirect()->route('whale.index')
                ->with('error', '您已绑定鲸探账户');
        }

        $authUrl = $this->whaleService->getAuthUrl($user->id);

        return view('whale.bind', compact('authUrl'));
    }

    public function redirect()
    {
        $user = Auth::user();

        if ($user->whale_account_id) {
            return redirect()->route('whale.index')
                ->with('error', '您已绑定鲸探账户');
        }

        $authUrl = $this->whaleService->getAuthUrl($user->id);

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');

            if (!$code || !$state) {
                throw new \Exception('授权参数缺失');
            }

            $tokenData = $this->whaleService->exchangeCodeForToken($code, $state);

            $userInfo = $this->whaleService->getUserInfo($tokenData['access_token']);

            $whaleAccount = $this->whaleService->bindUserAccount(
                $tokenData['user_id'],
                $tokenData,
                $userInfo
            );

            return redirect()->route('whale.index')
                ->with('success', '鲸探账户绑定成功！')
                ->with('whale_account', $whaleAccount);

        } catch (\Exception $e) {
            Log::error('鲸探授权回调失败', ['error' => $e->getMessage()]);

            return redirect()->route('whale.bind')
                ->with('error', '鲸探账户绑定失败：' . $e->getMessage());
        }
    }

    public function sync()
    {
        try {
            $user = Auth::user();

            if (!$user->whale_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => '您尚未绑定鲸探账户'
                ], 400);
            }

            $result = $this->rewardService->syncAndRewardUser($user);

            return response()->json([
                'success' => true,
                'message' => '鲸探数据同步成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('鲸探数据同步失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '同步失败：' . $e->getMessage()
            ], 500);
        }
    }

    public function checkin()
    {
        try {
            $user = Auth::user();
            $reward = $this->rewardService->processDailyCheckin($user);

            return response()->json([
                'success' => true,
                'message' => '签到成功',
                'data' => [
                    'reward' => $reward,
                    'balance' => $user->fresh()->points_balance
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function airdrop()
    {
        try {
            $user = Auth::user();

            if (!$user->whale_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => '请先绑定鲸探账户'
                ], 400);
            }

            $reward = $this->rewardService->calculateNftAirdropReward($user);

            if ($reward <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => '暂无可领取的NFT奖励'
                ], 400);
            }

            $user->addPoints(
                $reward,
                'whale_nft_bonus',
                '鲸探NFT空投奖励'
            );

            return response()->json([
                'success' => true,
                'message' => 'NFT空投奖励领取成功',
                'data' => [
                    'reward' => $reward,
                    'balance' => $user->fresh()->points_balance
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('NFT空投奖励失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '领取失败：' . $e->getMessage()
            ], 500);
        }
    }

    public function collections()
    {
        $user = Auth::user();

        if (!$user->whale_account_id) {
            return response()->json([
                'success' => false,
                'message' => '您尚未绑定鲸探账户'
            ], 400);
        }

        $collections = $user->nftCollections()
            ->orderBy('rarity', 'desc')
            ->orderBy('value', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    public function rewardHistory(Request $request)
    {
        $user = Auth::user();
        $days = $request->get('days', 30);

        $history = $this->rewardService->getRewardHistory($user, $days);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function unbind()
    {
        try {
            $user = Auth::user();

            if (!$user->whale_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => '您尚未绑定鲸探账户'
                ], 400);
            }

            $whaleAccount = $user->whaleAccount;

            $user->update([
                'whale_account_id' => null,
                'whale_nft_count' => 0,
                'is_verified' => false,
                'verification_level' => 1,
            ]);

            if ($whaleAccount && $whaleAccount->users()->count() === 0) {
                $whaleAccount->nftCollections()->delete();
                $whaleAccount->delete();
            }

            return response()->json([
                'success' => true,
                'message' => '鲸探账户解绑成功'
            ]);

        } catch (\Exception $e) {
            Log::error('鲸探账户解绑失败', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '解绑失败：' . $e->getMessage()
            ], 500);
        }
    }

    public function stats()
    {
        $user = Auth::user();

        $stats = [
            'user_stats' => $this->rewardService->getUserRewardStats($user),
            'whale_account' => $user->whaleAccount ? [
                'nft_count' => $user->whaleAccount->nft_count,
                'total_value' => $user->whaleAccount->total_value,
                'reward_multiplier' => $user->whaleAccount->calculateRewardMultiplier(),
                'last_sync_at' => $user->whaleAccount->last_sync_at,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function pricing()
    {
        $config = $this->pricingService->getPricingConfig();

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    public function collectionPricing($collectionId)
    {
        $history = $this->pricingService->getCollectionPriceHistory($collectionId);

        return response()->json([
            'success' => true,
            'data' => [
                'collection_id' => $collectionId,
                'price_history' => $history
            ]
        ]);
    }

    public function tasks()
    {
        $user = Auth::user();
        $rewardStats = $this->rewardService->getUserRewardStats($user);

        $tasks = [
            [
                'id' => 'daily_checkin',
                'name' => '每日签到',
                'description' => '每日签到获得积分奖励',
                'reward' => $this->rewardService->calculateDailyCheckinReward($user),
                'completed' => !$rewardStats['can_checkin_today'],
                'type' => 'daily',
                'action_url' => route('whale.checkin'),
            ],
            [
                'id' => 'whale_bind',
                'name' => '绑定鲸探账户',
                'description' => '绑定鲸探账户解锁更多奖励',
                'reward' => 1.00000000,
                'completed' => $user->whale_account_id ? true : false,
                'type' => 'once',
                'action_url' => route('whale.bind'),
            ],
            [
                'id' => 'nft_airdrop',
                'name' => 'NFT空投奖励',
                'description' => '持有鲸探NFT可获得每日空投奖励',
                'reward' => $user->whale_account_id ? $this->rewardService->calculateNftAirdropReward($user) : 0,
                'completed' => !$rewardStats['can_airdrop_today'],
                'type' => 'daily',
                'action_url' => route('whale.airdrop'),
                'requires_whale' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'user_stats' => $rewardStats,
            ]
        ]);
    }
}