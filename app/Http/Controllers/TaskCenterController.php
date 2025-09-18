<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\ConsumptionScenario;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskCenterController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->middleware('auth');
        $this->taskService = $taskService;
    }

    public function index()
    {
        $user = Auth::user();
        $availableTasks = $this->taskService->getUserAvailableTasks($user);
        $taskStats = $this->taskService->getUserTaskStats($user);

        return view('task-center.index', compact('availableTasks', 'taskStats'));
    }

    public function completeTask(Request $request)
    {
        $validated = $request->validate([
            'task_key' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $userTask = $this->taskService->completeTask($user, $validated['task_key']);

            return response()->json([
                'success' => true,
                'message' => '任务完成成功',
                'reward' => $userTask->task->reward_points,
                'new_balance' => $user->fresh()->points_balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function consumptions()
    {
        $user = Auth::user();
        $scenarios = ConsumptionScenario::where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        $userConsumptions = $user->consumptions()
            ->where('status', 'active')
            ->with('consumptionScenario')
            ->get();

        return view('task-center.consumptions', compact('scenarios', 'userConsumptions'));
    }

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'scenario_key' => 'required|string',
            'quantity' => 'integer|min:1|max:10',
        ]);

        $scenario = ConsumptionScenario::where('scenario_key', $validated['scenario_key'])
            ->where('is_active', true)
            ->first();

        if (!$scenario) {
            return response()->json([
                'success' => false,
                'message' => '消费场景不存在或已禁用',
            ], 404);
        }

        $user = Auth::user();
        $quantity = $validated['quantity'] ?? 1;
        $totalCost = $scenario->price * $quantity;

        // 检查用户余额
        if ($user->points_balance < $totalCost) {
            return response()->json([
                'success' => false,
                'message' => '积分余额不足',
                'required' => $totalCost,
                'current' => $user->points_balance,
            ], 400);
        }

        // 检查每日限制
        if ($scenario->daily_limit) {
            $todayPurchases = $user->consumptions()
                ->where('consumption_scenario_id', $scenario->id)
                ->whereDate('purchased_at', today())
                ->count();

            if ($todayPurchases + $quantity > $scenario->daily_limit) {
                return response()->json([
                    'success' => false,
                    'message' => "每日限购 {$scenario->daily_limit} 次，今日已购买 {$todayPurchases} 次",
                ], 400);
            }
        }

        // 检查购买要求
        if (!$this->checkPurchaseRequirements($user, $scenario)) {
            return response()->json([
                'success' => false,
                'message' => '不满足购买要求',
            ], 400);
        }

        try {
            DB::transaction(function () use ($user, $scenario, $quantity, $totalCost) {
                // 扣除积分
                $user->subtractPoints(
                    $totalCost,
                    'consumption',
                    "购买: {$scenario->name}",
                    $scenario
                );

                // 创建消费记录
                for ($i = 0; $i < $quantity; $i++) {
                    $user->consumptions()->create([
                        'consumption_scenario_id' => $scenario->id,
                        'amount_paid' => $scenario->price,
                        'purchased_at' => now(),
                        'expires_at' => $scenario->duration_hours ?
                            now()->addHours($scenario->duration_hours) : null,
                        'status' => 'active',
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => '购买成功',
                'new_balance' => $user->fresh()->points_balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '购买失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function myConsumptions()
    {
        $user = Auth::user();
        $consumptions = $user->consumptions()
            ->with('consumptionScenario')
            ->orderBy('purchased_at', 'desc')
            ->paginate(20);

        return view('task-center.my-consumptions', compact('consumptions'));
    }

    private function checkPurchaseRequirements($user, $scenario)
    {
        if (!$scenario->requirements) {
            return true;
        }

        $requirements = $scenario->requirements;

        // 检查账户年龄
        if (isset($requirements['min_account_age_days'])) {
            $accountAge = $user->created_at->diffInDays();
            if ($accountAge < $requirements['min_account_age_days']) {
                return false;
            }
        }

        // 检查是否有已审核作品
        if (isset($requirements['has_approved_artwork']) && $requirements['has_approved_artwork']) {
            $hasApprovedArtwork = $user->artworks()
                ->where('status', 'approved')
                ->exists();
            if (!$hasApprovedArtwork) {
                return false;
            }
        }

        return true;
    }
}