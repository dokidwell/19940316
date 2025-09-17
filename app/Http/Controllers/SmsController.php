<?php

namespace App\Http\Controllers;

use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'purpose' => 'sometimes|string|in:register,login,bind_phone,reset_password,change_phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $phone = $request->get('phone');
            $purpose = $request->get('purpose', 'register');

            // 验证手机号格式
            $phoneValidation = $this->smsService->validatePhoneFormat($phone);
            if (!$phoneValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $phoneValidation['message']
                ], 400);
            }

            // 检查是否可以发送短信
            $canSend = $this->smsService->canSendSms($phone);
            if (!$canSend['can_send']) {
                return response()->json([
                    'success' => false,
                    'message' => $canSend['message']
                ], 429);
            }

            // 特殊验证逻辑
            if ($purpose === 'register') {
                // 注册时检查手机号是否已存在
                if (\App\Models\User::where('phone', $phone)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => '该手机号已被注册'
                    ], 400);
                }
            } elseif ($purpose === 'login') {
                // 登录时检查手机号是否存在
                if (!\App\Models\User::where('phone', $phone)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => '该手机号尚未注册'
                    ], 400);
                }
            } elseif (in_array($purpose, ['bind_phone', 'change_phone'])) {
                // 绑定或更换手机号时需要登录
                if (!Auth::check()) {
                    return response()->json([
                        'success' => false,
                        'message' => '请先登录'
                    ], 401);
                }

                if ($purpose === 'bind_phone' && Auth::user()->phone) {
                    return response()->json([
                        'success' => false,
                        'message' => '您已绑定手机号'
                    ], 400);
                }
            }

            $result = $this->smsService->sendVerificationCode($phone, $purpose);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:6',
            'purpose' => 'sometimes|string|in:register,login,bind_phone,reset_password,change_phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $phone = $request->get('phone');
            $code = $request->get('code');
            $purpose = $request->get('purpose', 'register');

            $result = $this->smsService->verifyCode($phone, $code, $purpose);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function bindPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '请先登录'
                ], 401);
            }

            if ($user->phone) {
                return response()->json([
                    'success' => false,
                    'message' => '您已绑定手机号'
                ], 400);
            }

            $phone = $request->get('phone');
            $code = $request->get('code');

            $result = $this->smsService->bindPhoneToUser($user, $phone, $code);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function unbindPhone(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '请先登录'
                ], 401);
            }

            if (!$user->phone) {
                return response()->json([
                    'success' => false,
                    'message' => '您尚未绑定手机号'
                ], 400);
            }

            $user->update([
                'phone' => null,
                'phone_verified_at' => null,
            ]);

            \App\Models\SystemLog::logUserAction(
                'phone_unbind',
                "手机号解绑",
                ['previous_phone' => substr($user->phone, 0, 3) . '****' . substr($user->phone, -4)],
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => '手机号解绑成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getVerificationStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'purpose' => 'sometimes|string|in:register,login,bind_phone,reset_password,change_phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $phone = $request->get('phone');
            $purpose = $request->get('purpose', 'register');

            $status = $this->smsService->getVerificationStatus($phone, $purpose);
            $canSend = $this->smsService->canSendSms($phone);
            $remainingAttempts = $this->smsService->getRemainingAttempts($phone);

            return response()->json([
                'success' => true,
                'data' => [
                    'verification_status' => $status,
                    'can_send_sms' => $canSend,
                    'remaining_attempts' => $remainingAttempts,
                    'is_locked' => $this->smsService->isPhoneLocked($phone),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function validatePhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $phone = $request->get('phone');
            $validation = $this->smsService->validatePhoneFormat($phone);

            $additional = [];

            if ($validation['valid']) {
                // 检查手机号使用情况
                $user = \App\Models\User::where('phone', $phone)->first();
                $additional = [
                    'is_registered' => $user ? true : false,
                    'can_register' => $user ? false : true,
                    'can_login' => $user ? true : false,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($validation, $additional)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'message' => 'required|string|max:200',
            'template_id' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // 只允许管理员发送通知短信
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $phone = $request->get('phone');
            $message = $request->get('message');
            $templateId = $request->get('template_id');

            $result = $this->smsService->sendNotification($phone, $message, $templateId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function batchSendSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phones' => 'required|array|max:100',
            'phones.*' => 'string|regex:/^1[3-9]\d{9}$/',
            'template_id' => 'required|string',
            'template_params' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // 只允许管理员批量发送短信
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $phones = $request->get('phones');
            $templateId = $request->get('template_id');
            $templateParams = $request->get('template_params', []);

            $result = $this->smsService->sendBatchSms($phones, $templateId, $templateParams);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSmsStats()
    {
        try {
            // 只允许管理员查看短信统计
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $stats = $this->smsService->getSmsStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserSmsInfo()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '请先登录'
                ], 401);
            }

            $info = [
                'phone_bound' => $user->phone ? true : false,
                'phone_verified' => $user->phone_verified_at ? true : false,
                'masked_phone' => $user->phone ? substr($user->phone, 0, 3) . '****' . substr($user->phone, -4) : null,
                'verified_at' => $user->phone_verified_at,
            ];

            if ($user->phone) {
                $info['can_send_sms'] = $this->smsService->canSendSms($user->phone);
                $info['is_locked'] = $this->smsService->isPhoneLocked($user->phone);
                $info['remaining_attempts'] = $this->smsService->getRemainingAttempts($user->phone);
            }

            return response()->json([
                'success' => true,
                'data' => $info
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function resendVerificationCode(Request $request)
    {
        // 重发验证码，本质上和发送验证码相同，但可以添加额外的限制
        return $this->sendVerificationCode($request);
    }

    public function clearPhoneLock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // 只允许管理员清除锁定
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $phone = $request->get('phone');

            // 清除锁定和尝试次数
            \Illuminate\Support\Facades\Cache::forget("sms_locked_{$phone}");
            \Illuminate\Support\Facades\Cache::forget("sms_attempts_{$phone}");

            \App\Models\SystemLog::logUserAction(
                'sms_lock_cleared',
                "清除手机号短信锁定: {$phone}",
                ['phone' => substr($phone, 0, 3) . '****' . substr($phone, -4)],
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => '短信锁定已清除'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}