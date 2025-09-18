<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'type',
        'reward_points',
        'max_completions',
        'conditions',
        'is_active',
        'sort_order',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'reward_points' => 'decimal:8',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_ONCE = 'once';
    const TYPE_PER_ACTION = 'per_action';

    public function userTasks()
    {
        return $this->hasMany(UserTask::class);
    }

    public function getUserProgress($userId)
    {
        return $this->userTasks()->where('user_id', $userId)->first();
    }

    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_at && $now->lt($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return false;
        }

        return true;
    }

    public function canUserComplete($user)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $userTask = $this->getUserProgress($user->id);

        if (!$userTask) {
            return true;
        }

        // 检查是否可以重置
        if ($this->shouldReset($userTask)) {
            $this->resetUserProgress($user->id);
            return true;
        }

        // 检查完成次数限制
        if ($userTask->completed_count >= $this->max_completions) {
            return false;
        }

        return true;
    }

    private function shouldReset($userTask)
    {
        if (!$userTask->reset_at) {
            return false;
        }

        return now()->gt($userTask->reset_at);
    }

    private function resetUserProgress($userId)
    {
        $userTask = $this->userTasks()->where('user_id', $userId)->first();
        if ($userTask) {
            $userTask->update([
                'completed_count' => 0,
                'status' => 'pending',
                'reset_at' => $this->getNextResetTime(),
                'progress_data' => null,
            ]);
        }
    }

    private function getNextResetTime()
    {
        return match($this->type) {
            self::TYPE_DAILY => now()->addDay()->startOfDay(),
            self::TYPE_WEEKLY => now()->addWeek()->startOfWeek(),
            default => null,
        };
    }

    public static function getDefaultTasks()
    {
        return [
            [
                'key' => 'daily_login',
                'name' => '每日登录',
                'description' => '每天登录网站获得奖励',
                'type' => self::TYPE_DAILY,
                'reward_points' => '0.10000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'first_artwork_upload',
                'name' => '首次上传作品',
                'description' => '完成第一次作品上传',
                'type' => self::TYPE_ONCE,
                'reward_points' => '10.00000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'artwork_approved',
                'name' => '作品通过审核',
                'description' => '每当有作品通过审核时获得奖励',
                'type' => self::TYPE_PER_ACTION,
                'reward_points' => '5.00000000',
                'max_completions' => 999999,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'profile_complete',
                'name' => '完善个人资料',
                'description' => '完善头像、昵称等个人信息',
                'type' => self::TYPE_ONCE,
                'reward_points' => '2.00000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'community_interaction',
                'name' => '社区互动',
                'description' => '每日前10次有效互动（浏览、点赞）',
                'type' => self::TYPE_DAILY,
                'reward_points' => '0.05000000',
                'max_completions' => 10,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];
    }
}