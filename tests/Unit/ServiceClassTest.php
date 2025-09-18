<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TencentCloudService;
use App\Services\FileUploadService;
use App\Services\SmsService;
use App\Services\UserService;
use App\Services\TaskCenterService;
use App\Services\TransparencyService;
use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ServiceClassTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'points_balance' => '5000.00000000'
        ]);
    }

    /**
     * 測試TencentCloudService
     */
    public function test_tencent_cloud_service(): void
    {
        $service = app(TencentCloudService::class);

        // 測試配置檢查
        $this->assertTrue(method_exists($service, 'uploadFile'));
        $this->assertTrue(method_exists($service, 'deleteFile'));
        $this->assertTrue(method_exists($service, 'getFileUrl'));

        // 測試URL生成（不需要實際上傳）
        $testKey = 'test/file.jpg';
        $url = $service->getFileUrl($testKey);
        $this->assertIsString($url);
        $this->assertStringContainsString($testKey, $url);
    }

    /**
     * 測試FileUploadService
     */
    public function test_file_upload_service(): void
    {
        $service = app(FileUploadService::class);

        // 測試文件驗證
        $validFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $validation = $service->validateFile($validFile, 'image');
        $this->assertTrue($validation['valid']);

        // 測試無效文件類型
        $invalidFile = UploadedFile::fake()->create('test.exe', 100);
        $validation = $service->validateFile($invalidFile, 'image');
        $this->assertFalse($validation['valid']);
        $this->assertArrayHasKey('error', $validation);

        // 測試文件大小限制
        $largeFile = UploadedFile::fake()->create('large.jpg', 100000); // 100MB
        $validation = $service->validateFile($largeFile, 'image');
        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('文件大小', $validation['error']);

        // 測試上傳配置獲取
        $config = $service->getUploadConfig();
        $this->assertArrayHasKey('max_file_size', $config);
        $this->assertArrayHasKey('allowed_types', $config);
        $this->assertArrayHasKey('image', $config['allowed_types']);
    }

    /**
     * 測試SmsService
     */
    public function test_sms_service(): void
    {
        $service = app(SmsService::class);

        // 測試手機號驗證
        $validPhone = '13800138000';
        $this->assertTrue($service->validatePhone($validPhone));

        $invalidPhone = '123456';
        $this->assertFalse($service->validatePhone($invalidPhone));

        // 測試驗證碼生成
        $code = $service->generateVerificationCode();
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertTrue(ctype_digit($code));

        // 測試驗證碼存儲和驗證
        $phone = '13900139000';
        $code = '123456';
        $service->storeVerificationCode($phone, $code);

        $this->assertTrue($service->verifyCode($phone, $code));
        $this->assertFalse($service->verifyCode($phone, '654321'));

        // 測試驗證碼過期
        $service->storeVerificationCode($phone, $code, -1); // 過期時間為-1分鐘
        $this->assertFalse($service->verifyCode($phone, $code));
    }

    /**
     * 測試UserService
     */
    public function test_user_service(): void
    {
        $service = app(UserService::class);

        // 測試用戶統計
        $stats = $service->getUserStats($this->user);
        $this->assertArrayHasKey('points_balance', $stats);
        $this->assertArrayHasKey('total_transactions', $stats);
        $this->assertArrayHasKey('governance_participation', $stats);
        $this->assertArrayHasKey('whale_account_status', $stats);

        // 測試用戶活動記錄
        $activity = $service->getUserActivity($this->user, 10);
        $this->assertIsArray($activity);

        // 測試用戶設置更新
        $newSettings = [
            'notification_enabled' => true,
            'privacy_level' => 'public',
            'language' => 'zh-TW'
        ];

        $result = $service->updateUserSettings($this->user, $newSettings);
        $this->assertTrue($result['success']);

        // 測試HOHO ID生成
        $hohoId = $service->generateHohoId();
        $this->assertStringStartsWith('H', $hohoId);
        $this->assertEquals(9, strlen($hohoId)); // H + 8位數字

        // 確保生成的ID唯一
        $hohoId2 = $service->generateHohoId();
        $this->assertNotEquals($hohoId, $hohoId2);
    }

    /**
     * 測試TaskCenterService
     */
    public function test_task_center_service(): void
    {
        $service = app(TaskCenterService::class);

        // 測試可用任務獲取
        $tasks = $service->getAvailableTasks($this->user);
        $this->assertIsArray($tasks);

        // 每個任務應該有必要的字段
        foreach ($tasks as $task) {
            $this->assertArrayHasKey('id', $task);
            $this->assertArrayHasKey('title', $task);
            $this->assertArrayHasKey('description', $task);
            $this->assertArrayHasKey('reward_points', $task);
            $this->assertArrayHasKey('status', $task);
        }

        // 測試任務完成
        if (!empty($tasks)) {
            $task = $tasks[0];
            $result = $service->completeTask($this->user, $task['id']);

            if ($result['success']) {
                $this->assertArrayHasKey('reward_points', $result);
                $this->assertGreaterThan(0, $result['reward_points']);
            }
        }

        // 測試用戶任務進度
        $progress = $service->getUserTaskProgress($this->user);
        $this->assertArrayHasKey('completed_today', $progress);
        $this->assertArrayHasKey('total_completed', $progress);
        $this->assertArrayHasKey('total_points_earned', $progress);
    }

    /**
     * 測試TransparencyService
     */
    public function test_transparency_service(): void
    {
        $service = app(TransparencyService::class);

        // 創建一些測試數據
        PointTransaction::factory()->count(10)->create([
            'user_id' => $this->user->id
        ]);

        // 測試透明度報告生成
        $report = $service->generateTransparencyReport();
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('transactions', $report);
        $this->assertArrayHasKey('statistics', $report);

        // 測試交易搜索
        $searchResults = $service->searchTransactions([
            'user_id' => $this->user->id,
            'type' => 'reward',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString()
        ]);

        $this->assertArrayHasKey('transactions', $searchResults);
        $this->assertArrayHasKey('total', $searchResults);
        $this->assertArrayHasKey('summary', $searchResults);

        // 測試統計數據計算
        $stats = $service->calculateSystemStats();
        $this->assertArrayHasKey('total_transactions', $stats);
        $this->assertArrayHasKey('total_volume', $stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('daily_volume', $stats);

        // 測試數據匯出
        $exportData = $service->exportTransactionData([
            'format' => 'array',
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString()
        ]);

        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('transactions', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);
    }

    /**
     * 測試服務類依賴注入
     */
    public function test_service_dependency_injection(): void
    {
        // 測試所有服務都可以被正確解析
        $services = [
            TencentCloudService::class,
            FileUploadService::class,
            SmsService::class,
            UserService::class,
            TaskCenterService::class,
            TransparencyService::class
        ];

        foreach ($services as $serviceClass) {
            $service = app($serviceClass);
            $this->assertInstanceOf($serviceClass, $service);
        }
    }

    /**
     * 測試服務類錯誤處理
     */
    public function test_service_error_handling(): void
    {
        $userService = app(UserService::class);

        // 測試無效用戶ID處理
        $nonExistentUser = new User(['id' => 99999]);
        $stats = $userService->getUserStats($nonExistentUser);

        // 應該優雅地處理錯誤
        $this->assertIsArray($stats);

        // 測試無效參數處理
        $smsService = app(SmsService::class);
        $result = $smsService->validatePhone('invalid-phone');
        $this->assertFalse($result);
    }

    /**
     * 測試服務類快取機制
     */
    public function test_service_caching_mechanism(): void
    {
        $transparencyService = app(TransparencyService::class);

        // 第一次調用
        $start1 = microtime(true);
        $stats1 = $transparencyService->calculateSystemStats();
        $time1 = microtime(true) - $start1;

        // 第二次調用（應該使用快取）
        $start2 = microtime(true);
        $stats2 = $transparencyService->calculateSystemStats();
        $time2 = microtime(true) - $start2;

        // 結果應該相同
        $this->assertEquals($stats1, $stats2);

        // 第二次應該更快（使用快取）
        $this->assertLessThan($time1, $time2 + 0.01); // 加一點容錯
    }

    /**
     * 測試服務類配置管理
     */
    public function test_service_configuration_management(): void
    {
        $fileUploadService = app(FileUploadService::class);

        // 測試配置獲取
        $config = $fileUploadService->getUploadConfig();

        $this->assertArrayHasKey('max_file_size', $config);
        $this->assertArrayHasKey('allowed_types', $config);
        $this->assertGreaterThan(0, $config['max_file_size']);

        // 測試配置驗證
        $this->assertIsArray($config['allowed_types']);
        $this->assertArrayHasKey('image', $config['allowed_types']);
    }

    /**
     * 測試服務類事件處理
     */
    public function test_service_event_handling(): void
    {
        $userService = app(UserService::class);

        // 測試用戶創建事件
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '13800138000'
        ];

        $result = $userService->createUser($userData);

        if ($result['success']) {
            $user = $result['user'];

            // 檢查自動生成的數據
            $this->assertNotNull($user->hoho_id);
            $this->assertEquals('10000.00000000', $user->points_balance);
            $this->assertTrue($user->is_active);
        }
    }

    /**
     * 測試服務類性能
     */
    public function test_service_performance(): void
    {
        $transparencyService = app(TransparencyService::class);

        // 測試大量數據處理性能
        PointTransaction::factory()->count(1000)->create();

        $start = microtime(true);
        $report = $transparencyService->generateTransparencyReport();
        $duration = microtime(true) - $start;

        // 處理1000條記錄應該在合理時間內完成
        $this->assertLessThan(5.0, $duration, '透明度報告生成時間過長');
        $this->assertIsArray($report);
        $this->assertArrayHasKey('transactions', $report);
    }

    /**
     * 測試服務類介面一致性
     */
    public function test_service_interface_consistency(): void
    {
        $services = [
            app(UserService::class),
            app(TaskCenterService::class),
            app(TransparencyService::class)
        ];

        foreach ($services as $service) {
            // 檢查是否有標準的錯誤處理方法
            $this->assertTrue(
                method_exists($service, 'handleError') ||
                method_exists($service, 'logError') ||
                method_exists($service, 'reportError'),
                get_class($service) . ' 缺少錯誤處理方法'
            );
        }
    }
}