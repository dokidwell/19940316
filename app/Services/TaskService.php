<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function getUserAvailableTasks($user)
    {
        $tasks = Task::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $userTasks = UserTask::where('user_id', $user->id)
            ->with('task')
            ->get()
            ->keyBy('task_id');

        return $tasks->map(function ($task) use ($userTasks, $user) {
            $userTask = $userTasks->get($task->id);

            return [
                'task' => $task,
                'user_progress' => $userTask,
                'can_complete' => $task->canUserComplete($user),
                'is_completed' => $userTask ? $userTask->isCompleted() : false,
                'progress_percentage' => $userTask ? $userTask->getProgressPercentage() : 0,
                'can_reset' => $userTask ? $userTask->canReset() : false,
            ];
        });
    }

    public function completeTask($user, $taskKey, $additionalData = [])
    {
        $task = Task::where('key', $taskKey)->where('is_active', true)->first();

        if (!$task) {
            throw new \Exception("任务不存在或已禁用: {$taskKey}");
        }

        if (!$task->canUserComplete($user)) {
            throw new \Exception("用户无法完成此任务");
        }

        return DB::transaction(function () use ($user, $task, $additionalData) {
            $userTask = UserTask::firstOrCreate(
                ['user_id' => $user->id, 'task_id' => $task->id],
                [
                    'completed_count' => 0,
                    'status' => 'pending',
                    'reset_at' => $this->getNextResetTime($task),
                ]
            );

            // 检查是否需要重置
            if ($userTask->canReset()) {
                $userTask->update([
                    'completed_count' => 0,
                    'status' => 'pending',
                    'reset_at' => $this->getNextResetTime($task),
                    'progress_data' => null,
                ]);
            }

            // 完成任务
            $userTask->complete();

            return $userTask;
        });
    }

    public function triggerTaskCompletion($user, $event, $data = [])
    {
        $taskMappings = [
            'user_login' => 'daily_login',
            'artwork_uploaded' => 'first_artwork_upload',
            'artwork_approved' => 'artwork_approved',
            'profile_updated' => 'profile_complete',
            'community_interaction' => 'community_interaction',
        ];

        if (!isset($taskMappings[$event])) {
            return null;
        }

        $taskKey = $taskMappings[$event];

        try {
            return $this->completeTask($user, $taskKey, $data);
        } catch (\Exception $e) {
            // 任务完成失败，记录日志但不影响主流程
            logger()->warning("任务完成失败: {$e->getMessage()}", [
                'user_id' => $user->id,
                'task_key' => $taskKey,
                'event' => $event,
                'data' => $data,
            ]);
            return null;
        }
    }

    public function getUserTaskStats($user)
    {
        $userTasks = UserTask::where('user_id', $user->id)->with('task')->get();

        $completedToday = $userTasks->filter(function ($userTask) {
            return $userTask->last_completed_at &&
                   $userTask->last_completed_at->isToday();
        })->count();

        $totalCompleted = $userTasks->sum('completed_count');

        $totalRewardsEarned = $userTasks->sum(function ($userTask) {
            return $userTask->completed_count * $userTask->task->reward_points;
        });

        return [
            'completed_today' => $completedToday,
            'total_completed' => $totalCompleted,
            'total_rewards_earned' => $totalRewardsEarned,
            'active_tasks' => $userTasks->where('status', 'in_progress')->count(),
        ];
    }

    private function getNextResetTime($task)
    {
        return match($task->type) {
            Task::TYPE_DAILY => now()->addDay()->startOfDay(),
            Task::TYPE_WEEKLY => now()->addWeek()->startOfWeek(),
            default => null,
        };
    }

    public function initializeDefaultTasks()
    {
        $defaultTasks = Task::getDefaultTasks();

        foreach ($defaultTasks as $taskData) {
            Task::updateOrCreate(
                ['key' => $taskData['key']],
                $taskData
            );
        }
    }

    public function resetDailyTasks()
    {
        $dailyTasks = Task::where('type', Task::TYPE_DAILY)->pluck('id');

        UserTask::whereIn('task_id', $dailyTasks)
            ->where('reset_at', '<=', now())
            ->update([
                'completed_count' => 0,
                'status' => 'pending',
                'reset_at' => now()->addDay()->startOfDay(),
                'progress_data' => null,
            ]);
    }

    public function resetWeeklyTasks()
    {
        $weeklyTasks = Task::where('type', Task::TYPE_WEEKLY)->pluck('id');

        UserTask::whereIn('task_id', $weeklyTasks)
            ->where('reset_at', '<=', now())
            ->update([
                'completed_count' => 0,
                'status' => 'pending',
                'reset_at' => now()->addWeek()->startOfWeek(),
                'progress_data' => null,
            ]);
    }
}