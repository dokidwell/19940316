<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WhaleService;
use App\Services\WhalePricingService;
use App\Models\User;
use App\Models\WhaleAccount;
use App\Models\NftCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WhaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WhaleService $whaleService;
    protected WhalePricingService $pricingService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->whaleService = app(WhaleService::class);
        $this->pricingService = app(WhalePricingService::class);
        $this->user = User::factory()->create();
    }

    /**
     * 測試鯨探賬戶綁定
     */
    public function test_whale_account_binding(): void
    {
        $mockData = [
            'alipay_user_id' => 'test_user_123',
            'nick_name' => 'TestUser',
            'avatar' => 'https://example.com/avatar.jpg'
        ];

        Http::fake([
            'openapi.alipay.com/*' => Http::response($mockData, 200)
        ]);

        $result = $this->whaleService->bindAccount($this->user, 'test_auth_code');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('whale_accounts', [
            'user_id' => $this->user->id,
            'alipay_user_id' => 'test_user_123'
        ]);
    }

    /**
     * 測試NFT數據同步
     */
    public function test_nft_data_sync(): void
    {
        // 創建鯨探賬戶
        $whaleAccount = WhaleAccount::factory()->create([
            'user_id' => $this->user->id,
            'alipay_user_id' => 'test_user_123'
        ]);

        $mockNftData = [
            'nft_list' => [
                [
                    'nft_id' => 'nft_123',
                    'name' => 'Test NFT',
                    'image_url' => 'https://example.com/nft.jpg',
                    'collection_id' => 'collection_456',
                    'rarity' => 'rare',
                    'acquisition_date' => '2024-01-01'
                ]
            ]
        ];

        Http::fake([
            'openapi.alipay.com/*' => Http::response($mockNftData, 200)
        ]);

        $result = $this->whaleService->syncNftData($this->user);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('nft_collections', [
            'user_id' => $this->user->id,
            'nft_id' => 'nft_123',
            'name' => 'Test NFT'
        ]);
    }

    /**
     * 測試動態定價計算
     */
    public function test_dynamic_pricing_calculation(): void
    {
        // 創建NFT收藏記錄
        $nftCollection = NftCollection::factory()->create([
            'user_id' => $this->user->id,
            'rarity' => 'legendary',
            'acquisition_date' => now()->subDays(30),
            'last_valued_at' => now()->subDays(7)
        ]);

        $pricing = $this->pricingService->calculateNftValue($nftCollection);

        $this->assertArrayHasKey('base_value', $pricing);
        $this->assertArrayHasKey('rarity_multiplier', $pricing);
        $this->assertArrayHasKey('time_multiplier', $pricing);
        $this->assertArrayHasKey('final_value', $pricing);
        $this->assertGreaterThan(0, $pricing['final_value']);
    }

    /**
     * 測試每日簽到獎勵
     */
    public function test_daily_checkin_reward(): void
    {
        WhaleAccount::factory()->create([
            'user_id' => $this->user->id,
            'last_checkin_at' => now()->subDay()
        ]);

        NftCollection::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $initialBalance = $this->user->points_balance;
        $result = $this->whaleService->processCheckinReward($this->user);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan($initialBalance, $this->user->fresh()->points_balance);
    }

    /**
     * 測試重複簽到防護
     */
    public function test_duplicate_checkin_prevention(): void
    {
        WhaleAccount::factory()->create([
            'user_id' => $this->user->id,
            'last_checkin_at' => now() // 今天已簽到
        ]);

        $result = $this->whaleService->processCheckinReward($this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('已經簽到', $result['message']);
    }

    /**
     * 測試API限流處理
     */
    public function test_api_rate_limiting(): void
    {
        // 模擬達到限流
        Cache::put("whale_api_limit:{$this->user->id}", 60, now()->addMinutes(5));

        $result = $this->whaleService->syncNftData($this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('請求過於頻繁', $result['message']);
    }

    /**
     * 測試NFT稀有度分類
     */
    public function test_nft_rarity_classification(): void
    {
        $rarities = ['common', 'uncommon', 'rare', 'epic', 'legendary'];

        foreach ($rarities as $rarity) {
            $multiplier = $this->pricingService->getRarityMultiplier($rarity);
            $this->assertGreaterThan(0, $multiplier);
        }

        // 驗證稀有度遞增
        $commonMultiplier = $this->pricingService->getRarityMultiplier('common');
        $legendaryMultiplier = $this->pricingService->getRarityMultiplier('legendary');
        $this->assertGreaterThan($commonMultiplier, $legendaryMultiplier);
    }

    /**
     * 測試市場波動保護
     */
    public function test_market_volatility_protection(): void
    {
        $nftCollection = NftCollection::factory()->create([
            'user_id' => $this->user->id,
            'rarity' => 'rare',
            'last_valued_at' => now()->subHours(2)
        ]);

        // 測試短時間內多次估值
        $pricing1 = $this->pricingService->calculateNftValue($nftCollection);
        $pricing2 = $this->pricingService->calculateNftValue($nftCollection);

        // 應該有波動保護機制
        $volatilityDiff = abs($pricing1['final_value'] - $pricing2['final_value']);
        $maxAllowedDiff = $pricing1['final_value'] * 0.1; // 10% 最大波動

        $this->assertLessThanOrEqual($maxAllowedDiff, $volatilityDiff);
    }

    /**
     * 測試批量NFT處理
     */
    public function test_batch_nft_processing(): void
    {
        $whaleAccount = WhaleAccount::factory()->create([
            'user_id' => $this->user->id
        ]);

        $mockBatchData = [
            'nft_list' => array_fill(0, 50, [
                'nft_id' => 'nft_' . rand(1000, 9999),
                'name' => 'Batch NFT',
                'image_url' => 'https://example.com/nft.jpg',
                'collection_id' => 'collection_batch',
                'rarity' => 'common',
                'acquisition_date' => '2024-01-01'
            ])
        ];

        Http::fake([
            'openapi.alipay.com/*' => Http::response($mockBatchData, 200)
        ]);

        $result = $this->whaleService->syncNftData($this->user);

        $this->assertTrue($result['success']);
        $this->assertEquals(50, NftCollection::where('user_id', $this->user->id)->count());
    }

    /**
     * 測試賬戶解綁功能
     */
    public function test_whale_account_unbinding(): void
    {
        $whaleAccount = WhaleAccount::factory()->create([
            'user_id' => $this->user->id
        ]);

        NftCollection::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $result = $this->whaleService->unbindAccount($this->user);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('whale_accounts', [
            'user_id' => $this->user->id
        ]);
        $this->assertEquals(0, NftCollection::where('user_id', $this->user->id)->count());
    }
}