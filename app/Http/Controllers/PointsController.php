<?php

namespace App\Http\Controllers;

use App\Services\PointsService;
use App\Services\TransparencyService;
use App\Services\TaskCenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointsController extends Controller
{
    protected $pointsService;
    protected $transparencyService;
    protected $taskCenterService;

    public function __construct(
        PointsService $pointsService,
        TransparencyService $transparencyService,
        TaskCenterService $taskCenterService
    ) {
        $this->pointsService = $pointsService;
        $this->transparencyService = $transparencyService;
        $this->taskCenterService = $taskCenterService;
        $this->middleware('auth');
    }

    public function wallet()
    {
        $user = Auth::user();
        $stats = $this->pointsService->getUserStats($user);
        $systemStats = $this->pointsService->getSystemStats();
        $taskStats = $this->taskCenterService->getTaskStats($user);

        return view('points.wallet', compact('user', 'stats', 'systemStats', 'taskStats'));
    }

    public function balance()
    {
        $user = Auth::user();
        $balance = $this->pointsService->getUserBalance($user);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'formatted_balance' => number_format($balance, 8, '.', ','),
            ]
        ]);
    }

    public function stats()
    {
        $user = Auth::user();
        $stats = $this->pointsService->getUserStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $type = $request->get('type');

        $history = $this->pointsService->getTransactionHistory($user, $page, $perPage, $type);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        }

        return view('points.history', compact('history', 'user'));
    }

    public function systemStats()
    {
        $stats = $this->pointsService->getSystemStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function transparency(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $type = $request->get('type');
        $search = $request->get('search');

        $publicStats = $this->transparencyService->getPublicStats();
        $recentEvents = $this->transparencyService->getRecentEvents($page, $perPage, $type, $search);
        $pointsFlow = $this->transparencyService->getPointsFlowAnalysis();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'public_stats' => $publicStats,
                    'recent_events' => $recentEvents,
                    'points_flow' => $pointsFlow,
                ]
            ]);
        }

        $eventTypes = $this->transparencyService->getEventTypes();

        return view('points.transparency', compact(
            'publicStats',
            'recentEvents',
            'pointsFlow',
            'eventTypes'
        ));
    }

    public function transparencySearch(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type');
        $limit = $request->get('limit', 50);

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => '搜索关键词不能为空'
            ], 400);
        }

        $results = $this->transparencyService->search($query, $type, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'type' => $type,
                'results' => $results,
                'total' => $results->count(),
            ]
        ]);
    }

    public function transparencyExport(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $format = $request->get('format', 'json');

        try {
            $data = $this->transparencyService->exportTransparencyData($startDate, $endDate, $format);

            if ($format === 'csv') {
                return response($data)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="transparency_export_' . now()->format('Y-m-d') . '.csv"');
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '导出失败：' . $e->getMessage()
            ], 500);
        }
    }

    public function governanceActivity(Request $request)
    {
        $days = $request->get('days', 30);
        $activity = $this->transparencyService->getGovernanceActivity($days);

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    public function marketplaceActivity(Request $request)
    {
        $days = $request->get('days', 30);
        $activity = $this->transparencyService->getMarketplaceActivity($days);

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    public function whaleActivity(Request $request)
    {
        $days = $request->get('days', 30);
        $activity = $this->transparencyService->getWhaleActivity($days);

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    public function tasks()
    {
        $user = Auth::user();
        $tasks = $this->taskCenterService->getUserTasks($user);
        $taskStats = $this->taskCenterService->getTaskStats($user);
        $categories = $this->taskCenterService->getTaskCategories();

        return view('points.tasks', compact('tasks', 'taskStats', 'categories', 'user'));
    }

    public function tasksApi()
    {
        $user = Auth::user();
        $tasks = $this->taskCenterService->getUserTasks($user);
        $taskStats = $this->taskCenterService->getTaskStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'stats' => $taskStats,
            ]
        ]);
    }

    public function recentActivity(Request $request)
    {
        $limit = $request->get('limit', 100);
        $activity = $this->pointsService->getRecentActivity($limit);

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    public function validateAmount(Request $request)
    {
        $amount = $request->get('amount');

        try {
            $validatedAmount = $this->pointsService->validateAmount($amount);

            return response()->json([
                'success' => true,
                'data' => [
                    'original_amount' => $amount,
                    'validated_amount' => $validatedAmount,
                    'formatted_amount' => $this->pointsService->formatAmount($validatedAmount),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function transactionTypes()
    {
        $types = $this->pointsService->getTransactionTypes();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function publicPoolStats()
    {
        $balance = $this->pointsService->getPublicPoolBalance();
        $systemStats = $this->pointsService->getSystemStats();

        return response()->json([
            'success' => true,
            'data' => [
                'public_pool_balance' => $this->pointsService->formatAmount($balance),
                'total_burned' => $systemStats['total_points_burned'],
                'total_in_circulation' => $systemStats['total_points_in_circulation'],
            ]
        ]);
    }

    public function leaderboard(Request $request)
    {
        $type = $request->get('type', 'points');
        $limit = $request->get('limit', 50);

        $userService = app(\App\Services\UserService::class);
        $leaderboard = $userService->getLeaderboard($type, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'leaderboard' => $leaderboard,
            ]
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $userStats = $this->pointsService->getUserStats($user);
        $systemStats = $this->pointsService->getSystemStats();
        $taskStats = $this->taskCenterService->getTaskStats($user);
        $publicStats = $this->transparencyService->getPublicStats();

        return view('points.dashboard', compact(
            'user',
            'userStats',
            'systemStats',
            'taskStats',
            'publicStats'
        ));
    }
}