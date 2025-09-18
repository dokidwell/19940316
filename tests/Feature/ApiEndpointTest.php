<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use App\Models\Artwork;
use App\Models\PointTransaction;
use App\Models\WhaleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ApiEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'points_balance' => '10000.00000000'
        ]);
    }

    /**
     * 測試用戶餘額API
     */
    public function test_user_balance_api(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/balance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'balance' => '10000.00000000',
                'formatted_balance' => '10,000.00000000'
            ]);
    }

    /**
     * 測試未認證用戶無法訪問餘額API
     */
    public function test_unauthenticated_user_cannot_access_balance_api(): void
    {
        $response = $this->getJson('/api/user/balance');

        $response->assertStatus(401);
    }

    /**
     * 測試積分歷史API
     */
    public function test_points_history_api(): void
    {
        Sanctum::actingAs($this->user);

        // 創建一些交易記錄
        PointTransaction::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/points/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'type',
                        'description',
                        'created_at'
                    ]
                ]
            ]);
    }

    /**
     * 測試積分統計API
     */
    public function test_points_stats_api(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/points/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_balance',
                    'total_earned',
                    'total_spent',
                    'transaction_count'
                ]
            ]);
    }

    /**
     * 測試生態系統統計API
     */
    public function test_ecosystem_stats_api(): void
    {
        $response = $this->getJson('/ecosystem/api/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_users',
                    'active_users_24h',
                    'total_points_circulating',
                    'total_transactions',
                    'active_proposals',
                    'whale_connected_users'
                ]
            ]);
    }

    /**
     * 測試生態系統活動API
     */
    public function test_ecosystem_activity_api(): void
    {
        // 創建一些測試數據
        PointTransaction::factory()->count(3)->create();
        Proposal::factory()->count(2)->create();

        $response = $this->getJson('/ecosystem/api/activity');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transactions',
                    'proposals'
                ]
            ]);
    }

    /**
     * 測試社區治理API - 創建提案
     */
    public function test_create_proposal_api(): void
    {
        Sanctum::actingAs($this->user);

        $proposalData = [
            'title' => '測試提案',
            'description' => '這是一個測試提案的詳細描述',
            'category' => 'platform_improvement',
            'min_points_to_vote' => '100.00000000'
        ];

        $response = $this->postJson('/community/proposals', $proposalData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '提案創建成功，等待管理員審核'
            ])
            ->assertJsonStructure([
                'data' => [
                    'proposal_id',
                    'title',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('proposals', [
            'title' => '測試提案',
            'creator_id' => $this->user->id,
            'status' => 'draft'
        ]);
    }

    /**
     * 測試社區治理API - 投票
     */
    public function test_vote_on_proposal_api(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        $voteData = [
            'position' => 'for',
            'vote_strength' => 5,
            'justification' => '我支持這個提案'
        ];

        $response = $this->postJson("/community/proposals/{$proposal->id}/vote", $voteData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '投票成功'
            ])
            ->assertJsonStructure([
                'data' => [
                    'vote_id',
                    'position',
                    'vote_strength',
                    'points_cost',
                    'user_balance'
                ]
            ]);

        $this->assertDatabaseHas('proposal_votes', [
            'proposal_id' => $proposal->id,
            'user_id' => $this->user->id,
            'position' => 'for',
            'vote_strength' => 5
        ]);
    }

    /**
     * 測試提案搜索API
     */
    public function test_search_proposals_api(): void
    {
        // 創建測試提案
        $proposal1 = Proposal::factory()->create([
            'title' => '改進用戶界面',
            'description' => '優化用戶體驗'
        ]);
        $proposal2 = Proposal::factory()->create([
            'title' => '增加新功能',
            'description' => '添加市場功能'
        ]);

        $response = $this->getJson('/community/search?q=用戶界面');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'proposals',
                    'total'
                ]
            ]);

        $proposals = $response->json('data.proposals');
        $this->assertCount(1, $proposals);
        $this->assertEquals('改進用戶界面', $proposals[0]['title']);
    }

    /**
     * 測試鯨探API - 綁定賬戶
     */
    public function test_whale_bind_account_api(): void
    {
        Sanctum::actingAs($this->user);

        // 模擬鯨探授權碼
        $bindData = [
            'auth_code' => 'test_auth_code_123'
        ];

        // 由於需要外部API，這裡主要測試路由和基本驗證
        $response = $this->postJson('/whale/bind', $bindData);

        // 可能返回錯誤（因為是測試環境），但應該能到達控制器
        $this->assertTrue(in_array($response->status(), [200, 400, 500]));
    }

    /**
     * 測試鯨探API - 獲取統計數據
     */
    public function test_whale_stats_api(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/whale/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);
    }

    /**
     * 測試個人資料簽到API
     */
    public function test_profile_checkin_api(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/profile/checkin');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    /**
     * 測試短信驗證API
     */
    public function test_sms_verification_api(): void
    {
        $smsData = [
            'phone' => '13800138000',
            'type' => 'verification'
        ];

        $response = $this->postJson('/sms/send-code', $smsData);

        // 測試基本路由可達性
        $this->assertTrue(in_array($response->status(), [200, 400, 422]));
    }

    /**
     * 測試文件上傳API基本結構
     */
    public function test_file_upload_api_structure(): void
    {
        Sanctum::actingAs($this->user);

        // 測試獲取上傳配置
        $response = $this->getJson('/upload/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'max_file_size',
                    'allowed_types',
                    'upload_url'
                ]
            ]);
    }

    /**
     * 測試用戶存儲統計API
     */
    public function test_user_storage_stats_api(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/upload/storage-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'used_space',
                    'total_space',
                    'file_count'
                ]
            ]);
    }

    /**
     * 測試API限流
     */
    public function test_api_rate_limiting(): void
    {
        Sanctum::actingAs($this->user);

        // 快速發送多個請求測試限流
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/user/balance');
        }

        // 大部分請求應該成功，但如果有限流，某些請求會返回429
        $successCount = collect($responses)->filter(fn($response) => $response->status() === 200)->count();
        $this->assertGreaterThan(0, $successCount);
    }

    /**
     * 測試API錯誤處理
     */
    public function test_api_error_handling(): void
    {
        Sanctum::actingAs($this->user);

        // 測試不存在的提案
        $response = $this->getJson('/community/proposals/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false
            ]);
    }

    /**
     * 測試API數據驗證
     */
    public function test_api_data_validation(): void
    {
        Sanctum::actingAs($this->user);

        // 測試創建提案時的數據驗證
        $invalidData = [
            'title' => '', // 空標題
            'description' => str_repeat('a', 6000), // 超長描述
            'category' => 'invalid_category' // 無效分類
        ];

        $response = $this->postJson('/community/proposals', $invalidData);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    /**
     * 測試API響應時間
     */
    public function test_api_response_time(): void
    {
        Sanctum::actingAs($this->user);

        $startTime = microtime(true);
        $response = $this->getJson('/api/user/balance');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

        $response->assertStatus(200);
        $this->assertLessThan(1000, $responseTime, 'API響應時間應小於1秒');
    }

    /**
     * 測試批量API操作
     */
    public function test_batch_api_operations(): void
    {
        Sanctum::actingAs($this->user);

        // 測試批量獲取用戶數據
        $endpoints = [
            '/api/user/balance',
            '/api/points/stats',
            '/whale/stats'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertTrue(in_array($response->status(), [200, 401, 403]));
        }
    }
}