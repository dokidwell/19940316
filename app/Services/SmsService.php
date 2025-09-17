<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    protected $secretId;
    protected $secretKey;
    protected $sdkAppId;
    protected $signName;
    protected $templateId;

    public function __construct()
    {
        $this->secretId = config('services.tencent.sms.secret_id');
        $this->secretKey = config('services.tencent.sms.secret_key');
        $this->sdkAppId = config('services.tencent.sms.sdk_app_id');
        $this->signName = config('services.tencent.sms.sign_name');
        $this->templateId = config('services.tencent.sms.template_id');

        $this->validateConfig();
    }

    protected function validateConfig()
    {
        if (!$this->secretId || !$this->secretKey || !$this->sdkAppId) {
            throw new \Exception('腾讯云短信服务配置不完整');
        }
    }

    public function sendVerificationCode($phone, $purpose = 'register')
    {
        try {
            // 检查发送频率限制
            $this->checkSendLimit($phone);

            // 生成验证码
            $code = $this->generateVerificationCode();

            // 发送短信
            $result = $this->sendSms($phone, $this->templateId, [$code, '5']);

            if ($result['success']) {
                // 存储验证码
                $this->storeVerificationCode($phone, $code, $purpose);

                SystemLog::logUserAction(
                    'sms_verification_sent',
                    "短信验证码发送: {$phone}",
                    [
                        'phone' => $this->maskPhone($phone),
                        'purpose' => $purpose,
                        'message_id' => $result['message_id'] ?? null,
                    ],
                    auth()->id()
                );

                return [
                    'success' => true,
                    'message' => '验证码发送成功',
                    'data' => [
                        'phone' => $this->maskPhone($phone),
                        'expires_in' => 300, // 5分钟
                        'message_id' => $result['message_id'] ?? null,
                    ]
                ];
            } else {
                throw new \Exception($result['message'] ?? '短信发送失败');
            }

        } catch (\Exception $e) {
            Log::error('短信验证码发送失败', [
                'phone' => $this->maskPhone($phone),
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('验证码发送失败: ' . $e->getMessage());
        }
    }

    public function verifyCode($phone, $code, $purpose = 'register')
    {
        try {
            $cacheKey = "sms_verification_{$phone}_{$purpose}";
            $storedData = Cache::get($cacheKey);

            if (!$storedData) {
                return [
                    'success' => false,
                    'message' => '验证码已过期或不存在'
                ];
            }

            if ($storedData['code'] !== $code) {
                // 记录验证失败
                $this->recordVerificationAttempt($phone, $code, false);

                return [
                    'success' => false,
                    'message' => '验证码错误'
                ];
            }

            // 验证成功，删除缓存的验证码
            Cache::forget($cacheKey);
            Cache::forget("sms_attempts_{$phone}");

            SystemLog::logUserAction(
                'sms_verification_success',
                "短信验证成功: {$phone}",
                [
                    'phone' => $this->maskPhone($phone),
                    'purpose' => $purpose,
                ],
                auth()->id()
            );

            return [
                'success' => true,
                'message' => '验证成功'
            ];

        } catch (\Exception $e) {
            Log::error('短信验证失败', [
                'phone' => $this->maskPhone($phone),
                'code' => $code,
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('验证失败: ' . $e->getMessage());
        }
    }

    public function sendWelcomeMessage($phone, $username)
    {
        try {
            // 使用欢迎消息模板
            $result = $this->sendSms($phone, 'welcome_template', [$username]);

            if ($result['success']) {
                SystemLog::logUserAction(
                    'sms_welcome_sent',
                    "欢迎短信发送: {$phone}",
                    [
                        'phone' => $this->maskPhone($phone),
                        'username' => $username,
                        'message_id' => $result['message_id'] ?? null,
                    ],
                    auth()->id()
                );
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('欢迎短信发送失败', [
                'phone' => $this->maskPhone($phone),
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendNotification($phone, $message, $templateId = null)
    {
        try {
            $templateId = $templateId ?: 'notification_template';

            $result = $this->sendSms($phone, $templateId, [$message]);

            if ($result['success']) {
                SystemLog::logUserAction(
                    'sms_notification_sent',
                    "通知短信发送: {$phone}",
                    [
                        'phone' => $this->maskPhone($phone),
                        'template_id' => $templateId,
                        'message_id' => $result['message_id'] ?? null,
                    ],
                    auth()->id()
                );
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('通知短信发送失败', [
                'phone' => $this->maskPhone($phone),
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function sendSms($phone, $templateId, $templateParams = [])
    {
        // 在实际环境中，这里会调用腾讯云短信SDK
        // 现在我们模拟短信发送过程

        try {
            // 模拟网络延迟
            usleep(200000); // 0.2秒

            // 模拟成功的响应
            $messageId = 'msg_' . Str::random(16);

            return [
                'success' => true,
                'message' => '短信发送成功',
                'message_id' => $messageId,
                'phone' => $phone,
                'template_id' => $templateId,
                'template_params' => $templateParams,
                'sent_at' => now(),
            ];

        } catch (\Exception $e) {
            throw new \Exception('腾讯云短信发送失败: ' . $e->getMessage());
        }
    }

    protected function generateVerificationCode($length = 6)
    {
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function storeVerificationCode($phone, $code, $purpose)
    {
        $cacheKey = "sms_verification_{$phone}_{$purpose}";
        $expireTime = 300; // 5分钟

        Cache::put($cacheKey, [
            'code' => $code,
            'phone' => $phone,
            'purpose' => $purpose,
            'created_at' => now(),
            'expires_at' => now()->addSeconds($expireTime),
        ], $expireTime);
    }

    protected function checkSendLimit($phone)
    {
        $dailyLimitKey = "sms_daily_limit_{$phone}_" . now()->toDateString();
        $minuteLimitKey = "sms_minute_limit_{$phone}_" . now()->format('Y-m-d H:i');

        $dailyCount = Cache::get($dailyLimitKey, 0);
        $minuteCount = Cache::get($minuteLimitKey, 0);

        // 每日限制10条
        if ($dailyCount >= 10) {
            throw new \Exception('今日短信发送次数已达上限');
        }

        // 每分钟限制1条
        if ($minuteCount >= 1) {
            throw new \Exception('发送过于频繁，请稍后再试');
        }

        // 增加计数
        Cache::put($dailyLimitKey, $dailyCount + 1, 86400); // 24小时
        Cache::put($minuteLimitKey, $minuteCount + 1, 60); // 1分钟
    }

    protected function recordVerificationAttempt($phone, $code, $success)
    {
        $attemptsKey = "sms_attempts_{$phone}";
        $attempts = Cache::get($attemptsKey, 0);

        if (!$success) {
            $attempts++;
            Cache::put($attemptsKey, $attempts, 1800); // 30分钟

            // 失败次数过多时锁定
            if ($attempts >= 5) {
                $lockKey = "sms_locked_{$phone}";
                Cache::put($lockKey, true, 3600); // 锁定1小时

                SystemLog::logUserAction(
                    'sms_verification_locked',
                    "手机号验证锁定: {$phone}",
                    [
                        'phone' => $this->maskPhone($phone),
                        'attempts' => $attempts,
                    ],
                    auth()->id()
                );

                throw new \Exception('验证失败次数过多，已锁定1小时');
            }
        }
    }

    protected function maskPhone($phone)
    {
        if (strlen($phone) <= 7) {
            return $phone;
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    public function validatePhoneFormat($phone)
    {
        // 中国大陆手机号码验证
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return [
                'valid' => false,
                'message' => '请输入正确的手机号码'
            ];
        }

        return [
            'valid' => true,
            'message' => '手机号码格式正确'
        ];
    }

    public function isPhoneLocked($phone)
    {
        $lockKey = "sms_locked_{$phone}";
        return Cache::has($lockKey);
    }

    public function getRemainingAttempts($phone)
    {
        $attemptsKey = "sms_attempts_{$phone}";
        $attempts = Cache::get($attemptsKey, 0);
        return max(0, 5 - $attempts);
    }

    public function canSendSms($phone)
    {
        if ($this->isPhoneLocked($phone)) {
            return [
                'can_send' => false,
                'message' => '手机号已被锁定，请稍后再试'
            ];
        }

        $dailyLimitKey = "sms_daily_limit_{$phone}_" . now()->toDateString();
        $minuteLimitKey = "sms_minute_limit_{$phone}_" . now()->format('Y-m-d H:i');

        $dailyCount = Cache::get($dailyLimitKey, 0);
        $minuteCount = Cache::get($minuteLimitKey, 0);

        if ($dailyCount >= 10) {
            return [
                'can_send' => false,
                'message' => '今日短信发送次数已达上限'
            ];
        }

        if ($minuteCount >= 1) {
            return [
                'can_send' => false,
                'message' => '发送过于频繁，请稍后再试'
            ];
        }

        return [
            'can_send' => true,
            'message' => '可以发送短信'
        ];
    }

    public function getVerificationStatus($phone, $purpose = 'register')
    {
        $cacheKey = "sms_verification_{$phone}_{$purpose}";
        $storedData = Cache::get($cacheKey);

        if (!$storedData) {
            return [
                'exists' => false,
                'message' => '验证码不存在或已过期'
            ];
        }

        $remainingTime = $storedData['expires_at']->diffInSeconds(now(), false);

        return [
            'exists' => true,
            'expires_in' => max(0, -$remainingTime),
            'created_at' => $storedData['created_at'],
            'expires_at' => $storedData['expires_at'],
        ];
    }

    public function bindPhoneToUser(User $user, $phone, $verificationCode)
    {
        try {
            // 验证验证码
            $verifyResult = $this->verifyCode($phone, $verificationCode, 'bind_phone');

            if (!$verifyResult['success']) {
                return $verifyResult;
            }

            // 检查手机号是否已被其他用户绑定
            $existingUser = User::where('phone', $phone)->where('id', '!=', $user->id)->first();

            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => '该手机号已被其他用户绑定'
                ];
            }

            // 绑定手机号
            $user->update([
                'phone' => $phone,
                'phone_verified_at' => now(),
            ]);

            SystemLog::logUserAction(
                'phone_bind_success',
                "手机号绑定成功: {$phone}",
                ['phone' => $this->maskPhone($phone)],
                $user->id
            );

            return [
                'success' => true,
                'message' => '手机号绑定成功'
            ];

        } catch (\Exception $e) {
            Log::error('手机号绑定失败', [
                'user_id' => $user->id,
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '绑定失败: ' . $e->getMessage()
            ];
        }
    }

    public function getSmsStats()
    {
        // 获取短信发送统计信息
        // 在实际环境中，可以从数据库或缓存中获取统计数据

        return [
            'today_sent' => 0,
            'week_sent' => 0,
            'month_sent' => 0,
            'success_rate' => 99.5,
            'last_updated' => now(),
        ];
    }

    public function sendBatchSms($phones, $templateId, $templateParams = [])
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($phones as $phone) {
            try {
                $result = $this->sendSms($phone, $templateId, $templateParams);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                }

                $results[] = array_merge($result, ['phone' => $this->maskPhone($phone)]);

            } catch (\Exception $e) {
                $failCount++;
                $results[] = [
                    'success' => false,
                    'phone' => $this->maskPhone($phone),
                    'message' => $e->getMessage(),
                ];
            }
        }

        SystemLog::logUserAction(
            'sms_batch_sent',
            "批量短信发送完成",
            [
                'total' => count($phones),
                'success' => $successCount,
                'failed' => $failCount,
                'template_id' => $templateId,
            ],
            auth()->id()
        );

        return [
            'success' => $failCount === 0,
            'total' => count($phones),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results,
        ];
    }
}