<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $defaultTasks = [
            [
                'key' => 'daily_login',
                'name' => '每日登录',
                'description' => '每天登录网站获得奖励，保持活跃度',
                'type' => Task::TYPE_DAILY,
                'reward_points' => '0.10000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'first_artwork_upload',
                'name' => '首次上传作品',
                'description' => '完成第一次作品上传，开启创作之旅',
                'type' => Task::TYPE_ONCE,
                'reward_points' => '10.00000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'artwork_approved',
                'name' => '作品通过审核',
                'description' => '每当有作品通过审核时获得奖励',
                'type' => Task::TYPE_PER_ACTION,
                'reward_points' => '5.00000000',
                'max_completions' => 999999,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'profile_complete',
                'name' => '完善个人资料',
                'description' => '完善头像、昵称等个人信息',
                'type' => Task::TYPE_ONCE,
                'reward_points' => '2.00000000',
                'max_completions' => 1,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'community_interaction',
                'name' => '社区互动',
                'description' => '每日前10次有效互动（浏览、点赞）',
                'type' => Task::TYPE_DAILY,
                'reward_points' => '0.05000000',
                'max_completions' => 10,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'governance_participation',
                'name' => '参与社区治理',
                'description' => '参与治理提案投票，影响社区发展',
                'type' => Task::TYPE_PER_ACTION,
                'reward_points' => '1.00000000',
                'max_completions' => 999999,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'key' => 'weekly_check_in',
                'name' => '每周签到',
                'description' => '每周至少登录5天，获得额外奖励',
                'type' => Task::TYPE_WEEKLY,
                'reward_points' => '1.00000000',
                'max_completions' => 1,
                'conditions' => ['login_days' => 5],
                'is_active' => false, // 暂时禁用，后续可开启
                'sort_order' => 7,
            ],
        ];

        foreach ($defaultTasks as $taskData) {
            Task::updateOrCreate(
                ['key' => $taskData['key']],
                $taskData
            );
        }
    }
}