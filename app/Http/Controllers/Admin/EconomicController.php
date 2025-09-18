<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EconomicConfig;
use App\Models\Task;
use App\Models\ConsumptionScenario;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EconomicController extends Controller
{
    public function index()
    {
        $economicOverview = $this->getEconomicOverview();
        $recentChanges = $this->getRecentConfigChanges();

        return view('admin.economic.index', compact('economicOverview', 'recentChanges'));
    }

    public function tasks()
    {
        $tasks = Task::orderBy('sort_order')->get();
        return view('admin.economic.tasks', compact('tasks'));
    }

    public function updateTaskReward(Request $request, Task $task)
    {
        $validated = $request->validate([
            'reward_points' => 'required|numeric|min:0|max:999.99999999',
            'change_reason' => 'required|string|min:10|max:500',
            'effective_immediately' => 'boolean'
        ]);

        DB::transaction(function () use ($task, $validated) {
            // 记录配置变更
            EconomicConfig::create([
                'config_key' => "task_reward_{$task->key}",
                'config_name' => "任务奖励: {$task->name}",
                'description' => "任务 {$task->name} 的积分奖励",
                'config_type' => 'task_reward',
                'config_value' => $validated['reward_points'],
                'is_active' => true,
                'effective_at' => $validated['effective_immediately'] ? now() : now()->addHours(24),
                'updated_by' => auth()->id(),
                'change_reason' => $validated['change_reason'],
            ]);

            // 如果立即生效，更新任务奖励
            if ($validated['effective_immediately']) {
                $task->update(['reward_points' => $validated['reward_points']]);
            }

            // 创建公示通知
            $this->createPublicNotice(
                '任务奖励调整公告',
                "任务「{$task->name}」的奖励将调整为 {$validated['reward_points']} 积分",
                $validated['change_reason'],
                $validated['effective_immediately'] ? now() : now()->addHours(24)
            );
        });

        return response()->json([
            'success' => true,
            'message' => $validated['effective_immediately'] ? '任务奖励已立即更新' : '任务奖励将在24小时后生效'
        ]);
    }

    public function consumptionScenarios()
    {
        $scenarios = ConsumptionScenario::orderBy('category')->orderBy('sort_order')->get();
        return view('admin.economic.consumption', compact('scenarios'));
    }

    public function updateConsumptionPrice(Request $request, ConsumptionScenario $scenario)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0|max:999.99999999',
            'change_reason' => 'required|string|min:10|max:500',
            'effective_immediately' => 'boolean'
        ]);

        DB::transaction(function () use ($scenario, $validated) {
            EconomicConfig::create([
                'config_key' => "consumption_price_{$scenario->scenario_key}",
                'config_name' => "消费价格: {$scenario->name}",
                'description' => "消费场景 {$scenario->name} 的价格",
                'config_type' => 'consumption_price',
                'config_value' => $validated['price'],
                'is_active' => true,
                'effective_at' => $validated['effective_immediately'] ? now() : now()->addHours(24),
                'updated_by' => auth()->id(),
                'change_reason' => $validated['change_reason'],
            ]);

            if ($validated['effective_immediately']) {
                $scenario->update(['price' => $validated['price']]);
            }

            $this->createPublicNotice(
                '消费价格调整公告',
                "「{$scenario->name}」的价格将调整为 {$validated['price']} 积分",
                $validated['change_reason'],
                $validated['effective_immediately'] ? now() : now()->addHours(24)
            );
        });

        return response()->json([
            'success' => true,
            'message' => $validated['effective_immediately'] ? '消费价格已立即更新' : '消费价格将在24小时后生效'
        ]);
    }

    public function toggleTask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'change_reason' => 'required|string|min:10|max:500',
        ]);

        $task->update(['is_active' => $validated['is_active']]);

        $this->createPublicNotice(
            '任务状态调整公告',
            "任务「{$task->name}」已" . ($validated['is_active'] ? '启用' : '禁用'),
            $validated['change_reason'],
            now()
        );

        return response()->json([
            'success' => true,
            'message' => '任务状态已更新'
        ]);
    }

    public function toggleConsumptionScenario(Request $request, ConsumptionScenario $scenario)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'change_reason' => 'required|string|min:10|max:500',
        ]);

        $scenario->update(['is_active' => $validated['is_active']]);

        $this->createPublicNotice(
            '消费功能调整公告',
            "消费功能「{$scenario->name}」已" . ($validated['is_active'] ? '启用' : '禁用'),
            $validated['change_reason'],
            now()
        );

        return response()->json([
            'success' => true,
            'message' => '消费功能状态已更新'
        ]);
    }

    public function airdrop(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:all_users,specific_users,user_group',
            'user_ids' => 'required_if:target_type,specific_users|array',
            'user_ids.*' => 'exists:users,id',
            'amount' => 'required|numeric|min:0.00000001|max:999.99999999',
            'reason' => 'required|string|min:10|max:500',
        ]);

        $targetUsers = $this->getTargetUsers($validated['target_type'], $validated['user_ids'] ?? []);

        DB::transaction(function () use ($targetUsers, $validated) {
            foreach ($targetUsers as $user) {
                $user->addPoints(
                    $validated['amount'],
                    'admin_airdrop',
                    "管理员空投: {$validated['reason']}",
                    null
                );
            }

            $this->createPublicNotice(
                '积分空投公告',
                "管理员向 " . $targetUsers->count() . " 位用户空投了 {$validated['amount']} 积分",
                $validated['reason'],
                now()
            );
        });

        return response()->json([
            'success' => true,
            'message' => "成功向 {$targetUsers->count()} 位用户空投积分"
        ]);
    }

    public function economicStats()
    {
        $stats = [
            'total_points_in_circulation' => User::sum('points_balance'),
            'total_points_earned' => User::sum('total_points_earned'),
            'total_points_spent' => User::sum('total_points_spent'),
            'daily_transactions' => PointTransaction::whereDate('created_at', today())->count(),
            'weekly_transactions' => PointTransaction::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'top_earners' => User::orderBy('total_points_earned', 'desc')->limit(10)->get(),
            'recent_transactions' => PointTransaction::with('user')->latest()->limit(20)->get(),
        ];

        return response()->json($stats);
    }

    private function getEconomicOverview()
    {
        return [
            'total_circulation' => User::sum('points_balance'),
            'total_earned' => User::sum('total_points_earned'),
            'total_spent' => User::sum('total_points_spent'),
            'active_users_today' => User::whereHas('pointTransactions', function ($query) {
                $query->whereDate('created_at', today());
            })->count(),
            'transactions_today' => PointTransaction::whereDate('created_at', today())->count(),
        ];
    }

    private function getRecentConfigChanges()
    {
        return EconomicConfig::with(['updatedBy' => function($query) {
                $query->select('id', 'nickname', 'email');
            }])
            ->latest()
            ->limit(10)
            ->get();
    }

    private function createPublicNotice($title, $content, $reason, $effectiveDate)
    {
        // 这里可以创建公示通知，可以存储到数据库或发送通知
        // 暂时记录到系统日志
        logger()->info('经济配置变更公示', [
            'title' => $title,
            'content' => $content,
            'reason' => $reason,
            'effective_date' => $effectiveDate,
            'admin_id' => auth()->id(),
        ]);
    }

    private function getTargetUsers($targetType, $userIds)
    {
        return match($targetType) {
            'all_users' => User::all(),
            'specific_users' => User::whereIn('id', $userIds)->get(),
            'user_group' => User::where('role', 'member')->get(), // 示例：普通用户组
            default => collect([])
        };
    }
}