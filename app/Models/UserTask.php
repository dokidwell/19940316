<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'completed_count',
        'last_completed_at',
        'reset_at',
        'progress_data',
        'status',
    ];

    protected $casts = [
        'last_completed_at' => 'datetime',
        'reset_at' => 'datetime',
        'progress_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function canComplete()
    {
        return $this->task->canUserComplete($this->user);
    }

    public function complete()
    {
        if (!$this->canComplete()) {
            throw new \Exception('任务无法完成');
        }

        $this->increment('completed_count');
        $this->update([
            'last_completed_at' => now(),
            'status' => $this->completed_count >= $this->task->max_completions ? 'completed' : 'in_progress',
            'reset_at' => $this->getNextResetTime(),
        ]);

        // 给用户发放奖励
        $this->user->addPoints(
            $this->task->reward_points,
            'task_completion',
            "完成任务: {$this->task->name}",
            $this->task
        );

        return $this;
    }

    private function getNextResetTime()
    {
        return match($this->task->type) {
            Task::TYPE_DAILY => now()->addDay()->startOfDay(),
            Task::TYPE_WEEKLY => now()->addWeek()->startOfWeek(),
            default => null,
        };
    }

    public function getProgressPercentage()
    {
        if ($this->task->max_completions == 0) {
            return 0;
        }

        return min(100, ($this->completed_count / $this->task->max_completions) * 100);
    }

    public function isCompleted()
    {
        return $this->status === 'completed' ||
               $this->completed_count >= $this->task->max_completions;
    }

    public function canReset()
    {
        return $this->reset_at && now()->gte($this->reset_at);
    }
}