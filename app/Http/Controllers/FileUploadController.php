<?php

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use App\Services\TencentCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    protected $fileUploadService;
    protected $tencentCloudService;

    public function __construct(
        FileUploadService $fileUploadService,
        TencentCloudService $tencentCloudService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->tencentCloudService = $tencentCloudService;
        $this->middleware('auth');
    }

    public function uploadArtwork(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (100 * 1024), // 100MB in KB
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'tags' => 'sometimes|array',
            'category' => 'sometimes|string|max:100',
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
            $file = $request->file('file');

            $metadata = [
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'tags' => $request->get('tags', []),
                'category' => $request->get('category'),
                'upload_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->fileUploadService->uploadArtwork($file, $user, $metadata);

            return response()->json([
                'success' => true,
                'message' => '作品上传成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:' . (5 * 1024), // 5MB in KB
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
            $file = $request->file('file');

            $result = $this->fileUploadService->uploadAvatar($file, $user);

            return response()->json([
                'success' => true,
                'message' => '头像上传成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function setWhaleAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nft_id' => 'required|string',
            'image_url' => 'required|url',
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

            if (!$user->whale_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => '请先绑定鲸探账户'
                ], 400);
            }

            $nftId = $request->get('nft_id');
            $imageUrl = $request->get('image_url');

            // 验证用户是否拥有该NFT
            $nftExists = $user->nftCollections()
                ->where('whale_collection_id', $nftId)
                ->exists();

            if (!$nftExists) {
                return response()->json([
                    'success' => false,
                    'message' => '您不拥有该NFT'
                ], 403);
            }

            $result = $this->fileUploadService->uploadWhaleAvatar($imageUrl, $user, $nftId);

            return response()->json([
                'success' => true,
                'message' => '鲸探NFT头像设置成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:' . (10 * 1024), // 10MB in KB
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
            'position' => 'sometimes|string|in:home,artwork,market,community',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $file = $request->file('file');

            $metadata = [
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'position' => $request->get('position', 'home'),
                'uploader_id' => Auth::id(),
                'upload_ip' => $request->ip(),
            ];

            $result = $this->fileUploadService->uploadBanner($file, $metadata);

            return response()->json([
                'success' => true,
                'message' => '横幅上传成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadGeneral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (50 * 1024), // 50MB in KB
            'directory' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:500',
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
            $file = $request->file('file');

            $options = [
                'directory' => $request->get('directory', 'general'),
                'metadata' => [
                    'description' => $request->get('description'),
                    'upload_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ];

            $result = $this->fileUploadService->processFileUpload($file, 'general', $user, $options);

            return response()->json([
                'success' => true,
                'message' => '文件上传成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUploadToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:artwork,avatar,banner,general',
            'filename' => 'sometimes|string|max:255',
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
            $type = $request->get('type');
            $filename = $request->get('filename');

            $token = $this->fileUploadService->getUploadToken($type, $user, $filename);

            return response()->json([
                'success' => true,
                'data' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
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
            $key = $request->get('key');

            // 验证用户权限（确保只能删除自己的文件）
            if (!$this->canUserDeleteFile($user, $key)) {
                return response()->json([
                    'success' => false,
                    'message' => '没有权限删除该文件'
                ], 403);
            }

            $this->fileUploadService->deleteFile($key, $user);

            return response()->json([
                'success' => true,
                'message' => '文件删除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFileInfo(Request $request, $key)
    {
        try {
            $fileInfo = $this->fileUploadService->getFileInfo($key);

            return response()->json([
                'success' => true,
                'data' => $fileInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserStorageStats()
    {
        try {
            $user = Auth::user();
            $stats = $this->fileUploadService->getUserStorageStats($user);

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

    public function getUploadConfig()
    {
        $limits = $this->fileUploadService->getUploadLimits();

        return response()->json([
            'success' => true,
            'data' => $limits
        ]);
    }

    public function validateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'size' => 'required|integer|min:1',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $filename = $request->get('filename');
            $size = $request->get('size');
            $type = $request->get('type');

            $limits = $this->fileUploadService->getUploadLimits();
            $allowedTypes = $limits['allowed_types'];

            // 检查文件大小
            if ($size > $limits['max_file_size']) {
                return response()->json([
                    'success' => false,
                    'message' => "文件大小超过 {$limits['max_file_size_mb']}MB 限制"
                ], 400);
            }

            // 检查文件类型
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allTypes = array_merge(...array_values($allowedTypes));

            if (!in_array($extension, $allTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => "不支持的文件类型: {$extension}"
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => '文件验证通过',
                'data' => [
                    'filename' => $filename,
                    'size' => $size,
                    'type' => $type,
                    'extension' => $extension,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function canUserDeleteFile($user, $key)
    {
        // 检查文件路径是否属于用户
        // 一般文件路径格式为: type/user_id/filename

        $pathParts = explode('/', $key);

        if (count($pathParts) >= 2) {
            $fileUserId = $pathParts[1] ?? null;

            // 用户只能删除自己的文件
            if ($fileUserId == $user->id) {
                return true;
            }
        }

        // 管理员可以删除任何文件
        return $user->hasRole('admin');
    }

    public function getUploadProgress(Request $request, $uploadId)
    {
        // 这里可以实现上传进度查询
        // 在实际环境中，可以使用Redis或数据库存储上传进度

        return response()->json([
            'success' => true,
            'data' => [
                'upload_id' => $uploadId,
                'progress' => 100,
                'status' => 'completed',
                'message' => '上传完成',
            ]
        ]);
    }

    public function batchUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:' . (50 * 1024),
            'type' => 'required|string|in:artwork,general',
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
            $files = $request->file('files');
            $type = $request->get('type');

            $results = [];
            $errors = [];

            foreach ($files as $index => $file) {
                try {
                    $result = $this->fileUploadService->processFileUpload($file, $type, $user);
                    $results[] = $result;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => count($errors) === 0,
                'message' => count($errors) === 0 ? '批量上传成功' : '部分文件上传失败',
                'data' => [
                    'successful' => $results,
                    'failed' => $errors,
                    'total' => count($files),
                    'success_count' => count($results),
                    'error_count' => count($errors),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}