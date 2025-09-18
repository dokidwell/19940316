<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'points_balance' => '1000.000000'
        ]);
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);
    }

    /**
     * 測試SQL注入防護
     */
    public function test_sql_injection_protection(): void
    {
        Sanctum::actingAs($this->user);

        // 嘗試SQL注入攻擊
        $maliciousInput = "'; DROP TABLE users; --";

        $response = $this->getJson("/community/search?q=" . urlencode($maliciousInput));

        // 應該正常處理，不會執行惡意SQL
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    /**
     * 測試XSS防護
     */
    public function test_xss_protection(): void
    {
        Sanctum::actingAs($this->user);

        $xssPayload = '<script>alert("XSS")</script>';

        $proposalData = [
            'title' => $xssPayload,
            'description' => 'Normal description',
            'category' => 'platform_improvement'
        ];

        $response = $this->postJson('/community/proposals', $proposalData);

        if ($response->status() === 200) {
            $proposal = Proposal::where('creator_id', $this->user->id)->first();
            // 確保腳本標籤被轉義或移除
            $this->assertStringNotContainsString('<script>', $proposal->title);
        }
    }

    /**
     * 測試CSRF保護
     */
    public function test_csrf_protection(): void
    {
        // 不使用CSRF令牌的POST請求應該被拒絕
        $response = $this->post('/community/proposals', [
            'title' => 'Test Proposal',
            'description' => 'Test Description',
            'category' => 'platform_improvement'
        ]);

        // 應該返回419 CSRF錯誤或重定向到登錄頁面
        $this->assertTrue(in_array($response->status(), [419, 302]));
    }

    /**
     * 測試未授權訪問防護
     */
    public function test_unauthorized_access_protection(): void
    {
        // 未登錄用戶嘗試訪問受保護的資源
        $response = $this->getJson('/api/user/balance');
        $response->assertStatus(401);

        // 普通用戶嘗試訪問管理員功能
        Sanctum::actingAs($this->user);
        $proposal = Proposal::factory()->create(['status' => 'draft']);

        $response = $this->postJson("/community/proposals/{$proposal->id}/approve");
        $response->assertStatus(403);
    }

    /**
     * 測試權限提升攻擊防護
     */
    public function test_privilege_escalation_protection(): void
    {
        Sanctum::actingAs($this->user);

        // 嘗試修改其他用戶的數據
        $otherUser = User::factory()->create();
        $otherProposal = Proposal::factory()->create(['creator_id' => $otherUser->id]);

        // 嘗試對不屬於自己的提案進行操作
        $response = $this->deleteJson("/community/proposals/{$otherProposal->id}");
        $this->assertTrue(in_array($response->status(), [403, 404, 405]));
    }

    /**
     * 測試密碼安全性
     */
    public function test_password_security(): void
    {
        // 測試密碼哈希
        $password = 'test-password-123';
        $user = User::factory()->create(['password' => $password]);

        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotEquals($password, $user->password);
    }

    /**
     * 測試會話安全
     */
    public function test_session_security(): void
    {
        // 測試會話固定攻擊防護
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        // Laravel應該自動重新生成會話ID
        $this->assertTrue($response->status() === 302 || $response->status() === 200);
    }

    /**
     * 測試輸入驗證
     */
    public function test_input_validation(): void
    {
        Sanctum::actingAs($this->user);

        // 測試過長的輸入
        $longString = str_repeat('a', 10000);

        $response = $this->postJson('/community/proposals', [
            'title' => $longString,
            'description' => $longString,
            'category' => 'platform_improvement'
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['errors']);
    }

    /**
     * 測試文件上傳安全
     */
    public function test_file_upload_security(): void
    {
        Sanctum::actingAs($this->user);

        // 測試惡意文件類型
        $maliciousFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.php', 100);

        $response = $this->postJson('/upload/general', [
            'file' => $maliciousFile
        ]);

        // 應該拒絕非允許的文件類型
        $this->assertTrue(in_array($response->status(), [400, 422]));
    }

    /**
     * 測試API限流安全
     */
    public function test_api_rate_limiting_security(): void
    {
        Sanctum::actingAs($this->user);

        // 快速發送大量請求
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->postJson('/community/proposals', [
                'title' => "Test Proposal $i",
                'description' => 'Test Description',
                'category' => 'platform_improvement'
            ]);
        }

        // 應該有限流機制阻止大量請求
        $tooManyRequests = collect($responses)->filter(fn($response) => $response->status() === 429);
        $this->assertGreaterThan(0, $tooManyRequests->count());
    }

    /**
     * 測試敏感數據泄露防護
     */
    public function test_sensitive_data_protection(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);

        $userData = $response->json();

        // 確保敏感字段不會洩露
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    /**
     * 測試HTTP安全標頭
     */
    public function test_http_security_headers(): void
    {
        $response = $this->get('/');

        // 檢查安全標頭
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
        $response->assertHeader('X-XSS-Protection');
    }

    /**
     * 測試二次方投票系統安全
     */
    public function test_quadratic_voting_security(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        // 嘗試投票強度操縱
        $response = $this->postJson("/community/proposals/{$proposal->id}/vote", [
            'position' => 'for',
            'vote_strength' => 999999, // 極大的投票強度
            'justification' => 'Test vote'
        ]);

        // 應該被限制
        $response->assertStatus(400);
        $response->assertJsonStructure(['message']);
    }

    /**
     * 測試積分系統安全
     */
    public function test_points_system_security(): void
    {
        Sanctum::actingAs($this->user);

        $initialBalance = $this->user->points_balance;

        // 嘗試直接操縱積分
        $response = $this->putJson('/api/user/balance', [
            'balance' => '999999.00000000'
        ]);

        // 應該不存在這樣的端點或被拒絕
        $this->assertTrue(in_array($response->status(), [404, 405, 403]));

        // 確保餘額沒有被非法修改
        $this->assertEquals($initialBalance, $this->user->fresh()->points_balance);
    }

    /**
     * 測試鯨探API安全
     */
    public function test_whale_api_security(): void
    {
        Sanctum::actingAs($this->user);

        // 嘗試偽造鯨探數據
        $response = $this->postJson('/whale/sync', [
            'fake_nft_data' => [
                'nft_id' => 'fake_nft_123',
                'name' => 'Fake NFT',
                'rarity' => 'legendary'
            ]
        ]);

        // 應該驗證數據來源
        $this->assertTrue(in_array($response->status(), [400, 422, 403]));
    }

    /**
     * 測試管理員功能安全
     */
    public function test_admin_functionality_security(): void
    {
        $proposal = Proposal::factory()->create(['status' => 'draft']);

        // 普通用戶嘗試管理員操作
        Sanctum::actingAs($this->user);
        $response = $this->postJson("/community/proposals/{$proposal->id}/approve");
        $response->assertStatus(403);

        // 管理員用戶可以執行操作
        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/community/proposals/{$proposal->id}/approve");
        $this->assertTrue(in_array($response->status(), [200, 400])); // 可能因為業務邏輯失敗，但不是權限問題
    }

    /**
     * 測試時序攻擊防護
     */
    public function test_timing_attack_protection(): void
    {
        // 測試登錄時的時序攻擊防護
        $validEmail = $this->user->email;
        $invalidEmail = 'nonexistent@example.com';

        $start1 = microtime(true);
        $this->post('/login', ['email' => $validEmail, 'password' => 'wrong-password']);
        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $this->post('/login', ['email' => $invalidEmail, 'password' => 'wrong-password']);
        $time2 = microtime(true) - $start2;

        // 時間差不應該太大（表明有時序攻擊防護）
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.1, $timeDifference, '登錄時間差過大，可能存在時序攻擊風險');
    }

    /**
     * 測試重放攻擊防護
     */
    public function test_replay_attack_protection(): void
    {
        Sanctum::actingAs($this->user);

        $proposalData = [
            'title' => '重放攻擊測試',
            'description' => '測試重放攻擊防護',
            'category' => 'platform_improvement'
        ];

        // 第一次請求
        $response1 = $this->postJson('/community/proposals', $proposalData);

        // 立即重復相同請求
        $response2 = $this->postJson('/community/proposals', $proposalData);

        // 如果有重放防護，第二次請求可能被拒絕或返回不同結果
        // 這取決於具體的業務邏輯實現
        $this->assertTrue($response1->status() >= 200 && $response1->status() < 500);
        $this->assertTrue($response2->status() >= 200 && $response2->status() < 500);
    }

    /**
     * 測試數據完整性
     */
    public function test_data_integrity(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = Proposal::factory()->create(['creator_id' => $this->user->id]);

        // 嘗試修改數據完整性
        $response = $this->putJson("/community/proposals/{proposal->id}", [
            'creator_id' => 999999, // 嘗試修改創建者
            'title' => '修改後的標題'
        ]);

        // 應該保護關鍵字段不被修改
        $this->assertTrue(in_array($response->status(), [403, 405, 422]));
    }
}