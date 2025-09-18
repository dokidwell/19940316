<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PointsService;
use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PointsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PointsService $pointsService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pointsService = app(PointsService::class);
        $this->user = User::factory()->create([
            'points_balance' => '1000.000000'
        ]);
    }

    /**
     * 測試8位精度積分計算
     */
    public function test_eight_decimal_precision_calculation(): void
    {
        $amount = '0.000001';
        $result = $this->pointsService->addPoints(
            $this->user,
            $amount,
            'reward',
            'Testing 8-decimal precision'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('1000.000001', $this->user->fresh()->points_balance);
    }

    /**
     * 測試積分餘額驗證
     */
    public function test_insufficient_balance_validation(): void
    {
        $result = $this->pointsService->deductPoints(
            $this->user,
            '2000.00000000',
            'purchase',
            'Testing insufficient balance'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('余额不足', $result['message']);
        $this->assertEquals('1000.000000', $this->user->fresh()->points_balance);
    }

    /**
     * 測試積分交易原子性
     */
    public function test_points_transaction_atomicity(): void
    {
        DB::beginTransaction();

        try {
            $this->pointsService->deductPoints(
                $this->user,
                '500.00000000',
                'test_atomic',
                'Testing atomic transaction'
            );

            // 模擬錯誤
            throw new \Exception('Simulated error');
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // 確認餘額未改變
        $this->assertEquals('1000.000000', $this->user->fresh()->points_balance);
    }

    /**
     * 測試積分轉帳功能
     */
    public function test_points_transfer(): void
    {
        $recipient = User::factory()->create([
            'points_balance' => '500.00000000'
        ]);

        $result = $this->pointsService->transferPoints(
            $this->user,
            $recipient,
            '100.00000000',
            'test_transfer',
            'Testing points transfer'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('900.000000', $this->user->fresh()->points_balance);
        $this->assertEquals('600.000000', $recipient->fresh()->points_balance);
    }

    /**
     * 測試最小交易額度驗證
     */
    public function test_minimum_transaction_amount(): void
    {
        $result = $this->pointsService->addPoints(
            $this->user,
            '0.000000001', // 小於最小額度
            'reward',
            'Testing minimum amount'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('交易金额过小', $result['message']);
    }

    /**
     * 測試每日獲得上限
     */
    public function test_daily_earning_limit(): void
    {
        // 先獲得接近上限的積分
        $this->pointsService->addPoints(
            $this->user,
            '999.00000000',
            'checkin',
            'Daily checkin reward'
        );

        // 嘗試超過上限
        $result = $this->pointsService->addPoints(
            $this->user,
            '100.00000000',
            'checkin',
            'Another daily checkin'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('每日获得上限', $result['message']);
    }

    /**
     * 測試積分統計計算
     */
    public function test_points_statistics(): void
    {
        // 創建一些交易記錄
        $this->pointsService->addPoints($this->user, '50.000000', 'reward', 'Test reward');
        $this->pointsService->deductPoints($this->user, '25.000000', 'purchase', 'Test purchase');

        $stats = $this->pointsService->getUserStats($this->user->fresh());

        $this->assertArrayHasKey('total_earned', $stats);
        $this->assertArrayHasKey('total_spent', $stats);
        $this->assertArrayHasKey('transaction_count', $stats);
        $this->assertEquals('50.000000', $stats['total_earned']);
        $this->assertEquals('25.000000', $stats['total_spent']);
    }

    /**
     * 測試公共資金池統計
     */
    public function test_public_pool_statistics(): void
    {
        $stats = $this->pointsService->getPublicPoolStats();

        $this->assertArrayHasKey('total_balance', $stats);
        $this->assertArrayHasKey('daily_distribution', $stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertIsNumeric($stats['total_balance']);
    }

    /**
     * 測試透明度報告生成
     */
    public function test_transparency_report_generation(): void
    {
        // 創建一些測試數據
        $this->pointsService->addPoints($this->user, '100.00000000', 'whale_bonus', 'Whale reward');

        $report = $this->pointsService->getTransparencyDashboard();

        $this->assertArrayHasKey('total_transactions', $report);
        $this->assertArrayHasKey('total_users', $report);
        $this->assertArrayHasKey('total_points_circulation', $report);
        $this->assertArrayHasKey('recent_transactions', $report);
    }

    /**
     * 測試積分排行榜
     */
    public function test_points_leaderboard(): void
    {
        // 设置测试用户余额为0，避免影响排行榜
        $this->user->update(['points_balance' => 0]);

        // 創建多個用戶
        $users = User::factory()->count(5)->create();
        foreach ($users as $index => $user) {
            $user->update(['points_balance' => ($index + 1) * 100]);
        }

        $leaderboard = $this->pointsService->getLeaderboard(3);

        $this->assertCount(3, $leaderboard);
        $this->assertEquals('500.000000', $leaderboard[0]->points_balance);
        $this->assertEquals('400.000000', $leaderboard[1]->points_balance);
    }
}