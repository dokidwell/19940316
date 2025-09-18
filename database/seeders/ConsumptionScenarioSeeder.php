<?php

namespace Database\Seeders;

use App\Models\ConsumptionScenario;
use Illuminate\Database\Seeder;

class ConsumptionScenarioSeeder extends Seeder
{
    public function run()
    {
        $defaultScenarios = [
            // 治理类消费
            [
                'scenario_key' => 'create_proposal',
                'name' => '创建治理提案',
                'description' => '消费积分创建社区治理提案',
                'category' => 'governance',
                'price' => '100.00000000',
                'is_active' => true,
                'sort_order' => 1,
                'requirements' => ['min_account_age_days' => 7],
                'effects' => ['enable_proposal_creation' => true],
            ],
            [
                'scenario_key' => 'boost_voting_power',
                'name' => '投票权重加成',
                'description' => '临时提升投票权重50%',
                'category' => 'governance',
                'price' => '10.00000000',
                'duration_hours' => 24,
                'daily_limit' => 3,
                'is_active' => true,
                'sort_order' => 2,
                'effects' => ['voting_power_multiplier' => 1.5],
            ],

            // 高级功能类
            [
                'scenario_key' => 'premium_avatar',
                'name' => '高级头像框',
                'description' => '购买特殊头像框，展示个性',
                'category' => 'premium',
                'price' => '25.00000000',
                'duration_hours' => 720, // 30天
                'is_active' => true,
                'sort_order' => 3,
                'effects' => ['avatar_frame' => 'premium'],
            ],
            [
                'scenario_key' => 'username_highlight',
                'name' => '用户名高亮',
                'description' => '让你的用户名在评论中高亮显示',
                'category' => 'premium',
                'price' => '15.00000000',
                'duration_hours' => 168, // 7天
                'is_active' => true,
                'sort_order' => 4,
                'effects' => ['username_highlight' => true],
            ],
            [
                'scenario_key' => 'custom_badge',
                'name' => '自定义徽章',
                'description' => '设置个人专属徽章',
                'category' => 'premium',
                'price' => '20.00000000',
                'duration_hours' => 720, // 30天
                'is_active' => true,
                'sort_order' => 5,
                'effects' => ['custom_badge' => true],
            ],

            // 推广类消费
            [
                'scenario_key' => 'featured_artwork',
                'name' => '作品置顶展示',
                'description' => '将作品置顶展示在首页',
                'category' => 'promotion',
                'price' => '50.00000000',
                'duration_hours' => 24,
                'daily_limit' => 5,
                'is_active' => true,
                'sort_order' => 6,
                'requirements' => ['has_approved_artwork' => true],
                'effects' => ['featured_position' => 'homepage'],
            ],
            [
                'scenario_key' => 'artwork_promotion',
                'name' => '作品推广位',
                'description' => '在推荐位展示作品，增加曝光',
                'category' => 'promotion',
                'price' => '30.00000000',
                'duration_hours' => 72,
                'is_active' => true,
                'sort_order' => 7,
                'effects' => ['promotion_boost' => true],
            ],

            // 实用工具类
            [
                'scenario_key' => 'priority_review',
                'name' => '作品优先审核',
                'description' => '上传的作品获得优先审核',
                'category' => 'utility',
                'price' => '5.00000000',
                'is_active' => true,
                'sort_order' => 8,
                'effects' => ['priority_review' => true],
            ],
            [
                'scenario_key' => 'bulk_upload',
                'name' => '批量上传权限',
                'description' => '解锁批量上传作品功能',
                'category' => 'utility',
                'price' => '35.00000000',
                'duration_hours' => 168, // 7天
                'is_active' => false, // 暂时禁用
                'sort_order' => 9,
                'effects' => ['bulk_upload_enabled' => true],
            ],
        ];

        foreach ($defaultScenarios as $scenarioData) {
            ConsumptionScenario::updateOrCreate(
                ['scenario_key' => $scenarioData['scenario_key']],
                $scenarioData
            );
        }
    }
}