<?php

namespace App\Services;

use App\Models\User;
use App\Models\Artwork;
use App\Models\SystemLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadService
{
    protected $tencentCloudService;
    protected $allowedImageTypes;
    protected $allowedVideoTypes;
    protected $allowedAudioTypes;
    protected $allowedDocumentTypes;
    protected $maxFileSize;

    public function __construct(TencentCloudService $tencentCloudService)
    {
        $this->tencentCloudService = $tencentCloudService;
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $this->allowedImageTypes = config('services.uploads.allowed_image_types', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
        ]);

        $this->allowedVideoTypes = config('services.uploads.allowed_video_types', [
            'mp4', 'webm', 'ogg', 'mov'
        ]);

        $this->allowedAudioTypes = config('services.uploads.allowed_audio_types', [
            'mp3', 'wav', 'ogg', 'aac'
        ]);

        $this->allowedDocumentTypes = config('services.uploads.allowed_document_types', [
            'pdf', 'doc', 'docx', 'txt'
        ]);

        $this->maxFileSize = config('services.uploads.max_file_size', 50 * 1024 * 1024);
    }

    public function uploadArtwork(UploadedFile $file, User $user, $metadata = [])
    {
        try {
            $this->validateArtworkFile($file);

            $uploadResult = $this->tencentCloudService->uploadArtwork($file, $user->id);

            $thumbnails = [];
            if ($this->isImageFile($file)) {
                $thumbnails = $this->generateThumbnails($uploadResult['key']);
            }

            $fileInfo = array_merge($uploadResult, [
                'user_id' => $user->id,
                'type' => $this->getFileType($file),
                'metadata' => $metadata,
                'thumbnails' => $thumbnails,
                'processing_status' => 'completed',
            ]);

            $this->logUpload('artwork', $user, $fileInfo);

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('作品文件上传失败', [
                'user_id' => $user->id,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('作品上传失败: ' . $e->getMessage());
        }
    }

    public function uploadAvatar(UploadedFile $file, User $user)
    {
        try {
            $this->validateAvatarFile($file);

            $uploadResult = $this->tencentCloudService->uploadAvatar($file, $user->id);

            $user->update(['avatar' => $uploadResult['url']]);

            $this->logUpload('avatar', $user, $uploadResult);

            return $uploadResult;

        } catch (\Exception $e) {
            Log::error('头像上传失败', [
                'user_id' => $user->id,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('头像上传失败: ' . $e->getMessage());
        }
    }

    public function uploadWhaleAvatar($imageUrl, User $user, $nftId)
    {
        try {
            $uploadResult = $this->tencentCloudService->uploadWhaleAvatar($imageUrl, $user->id, $nftId);

            $user->update(['avatar' => $uploadResult['url']]);

            $this->logUpload('whale_avatar', $user, array_merge($uploadResult, [
                'nft_id' => $nftId,
                'source_url' => $imageUrl,
            ]));

            return $uploadResult;

        } catch (\Exception $e) {
            Log::error('鲸探NFT头像设置失败', [
                'user_id' => $user->id,
                'nft_id' => $nftId,
                'image_url' => $imageUrl,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('鲸探NFT头像设置失败: ' . $e->getMessage());
        }
    }

    public function uploadBanner(UploadedFile $file, $metadata = [])
    {
        try {
            $this->validateImageFile($file);

            $directory = 'banners';
            $uploadResult = $this->tencentCloudService->uploadFile($file, $directory);

            $thumbnails = $this->generateThumbnails($uploadResult['key']);

            $fileInfo = array_merge($uploadResult, [
                'type' => 'banner',
                'metadata' => $metadata,
                'thumbnails' => $thumbnails,
            ]);

            $this->logUpload('banner', auth()->user(), $fileInfo);

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('横幅上传失败', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('横幅上传失败: ' . $e->getMessage());
        }
    }

    public function processFileUpload(UploadedFile $file, $type, User $user = null, $options = [])
    {
        $user = $user ?: auth()->user();

        switch ($type) {
            case 'artwork':
                return $this->uploadArtwork($file, $user, $options['metadata'] ?? []);

            case 'avatar':
                return $this->uploadAvatar($file, $user);

            case 'banner':
                return $this->uploadBanner($file, $options['metadata'] ?? []);

            default:
                return $this->uploadGeneral($file, $user, $options);
        }
    }

    protected function uploadGeneral(UploadedFile $file, User $user, $options = [])
    {
        try {
            $this->validateFile($file);

            $directory = $options['directory'] ?? 'general';
            $uploadResult = $this->tencentCloudService->uploadFile($file, $directory);

            $fileInfo = array_merge($uploadResult, [
                'user_id' => $user->id,
                'type' => $this->getFileType($file),
                'metadata' => $options['metadata'] ?? [],
            ]);

            $this->logUpload('general', $user, $fileInfo);

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('文件上传失败', [
                'user_id' => $user->id,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件上传失败: ' . $e->getMessage());
        }
    }

    protected function generateThumbnails($key)
    {
        $thumbnails = [];
        $sizes = config('services.uploads.thumbnail_sizes', [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [800, 600],
        ]);

        try {
            foreach ($sizes as $size => [$width, $height]) {
                $thumbnail = $this->tencentCloudService->generateThumbnail($key, $width, $height);
                $thumbnails[$size] = $thumbnail;
            }

            return $thumbnails;

        } catch (\Exception $e) {
            Log::warning('缩略图生成失败', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    protected function validateFile(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \Exception('文件上传失败');
        }

        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 1);
            throw new \Exception("文件大小超过 {$maxSizeMB}MB 限制");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allAllowedTypes = array_merge(
            $this->allowedImageTypes,
            $this->allowedVideoTypes,
            $this->allowedAudioTypes,
            $this->allowedDocumentTypes
        );

        if (!in_array($extension, $allAllowedTypes)) {
            throw new \Exception('不支持的文件类型: ' . $extension);
        }
    }

    protected function validateArtworkFile(UploadedFile $file)
    {
        $this->validateFile($file);

        // 作品文件的额外验证
        $fileType = $this->getFileType($file);

        if ($fileType === 'image') {
            $this->validateImageFile($file);
        } elseif ($fileType === 'video') {
            $this->validateVideoFile($file);
        } elseif ($fileType === 'audio') {
            $this->validateAudioFile($file);
        }
    }

    protected function validateImageFile(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedImageTypes)) {
            throw new \Exception('不支持的图片格式');
        }

        // 检查图片尺寸
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \Exception('无效的图片文件');
        }

        [$width, $height] = $imageInfo;

        if ($width < 100 || $height < 100) {
            throw new \Exception('图片尺寸太小，最小要求 100x100 像素');
        }

        if ($width > 8000 || $height > 8000) {
            throw new \Exception('图片尺寸太大，最大支持 8000x8000 像素');
        }
    }

    protected function validateVideoFile(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedVideoTypes)) {
            throw new \Exception('不支持的视频格式');
        }

        // 视频文件大小限制更严格
        $maxVideoSize = 100 * 1024 * 1024; // 100MB
        if ($file->getSize() > $maxVideoSize) {
            throw new \Exception('视频文件大小不能超过 100MB');
        }
    }

    protected function validateAudioFile(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedAudioTypes)) {
            throw new \Exception('不支持的音频格式');
        }

        // 音频文件大小限制
        $maxAudioSize = 50 * 1024 * 1024; // 50MB
        if ($file->getSize() > $maxAudioSize) {
            throw new \Exception('音频文件大小不能超过 50MB');
        }
    }

    protected function validateAvatarFile(UploadedFile $file)
    {
        $this->validateImageFile($file);

        // 头像文件额外限制
        $maxAvatarSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxAvatarSize) {
            throw new \Exception('头像文件大小不能超过 5MB');
        }
    }

    protected function getFileType(UploadedFile $file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, $this->allowedImageTypes)) {
            return 'image';
        } elseif (in_array($extension, $this->allowedVideoTypes)) {
            return 'video';
        } elseif (in_array($extension, $this->allowedAudioTypes)) {
            return 'audio';
        } elseif (in_array($extension, $this->allowedDocumentTypes)) {
            return 'document';
        }

        return 'unknown';
    }

    protected function isImageFile(UploadedFile $file)
    {
        return $this->getFileType($file) === 'image';
    }

    protected function logUpload($type, User $user = null, $fileInfo = [])
    {
        SystemLog::logUserAction(
            'file_upload',
            "文件上传: {$type}",
            [
                'type' => $type,
                'filename' => $fileInfo['filename'] ?? null,
                'size' => $fileInfo['size'] ?? null,
                'url' => $fileInfo['url'] ?? null,
                'key' => $fileInfo['key'] ?? null,
            ],
            $user?->id
        );
    }

    public function deleteFile($key, User $user = null)
    {
        try {
            $this->tencentCloudService->deleteFile($key);

            SystemLog::logUserAction(
                'file_delete',
                "文件删除: {$key}",
                ['key' => $key],
                $user?->id
            );

            return true;

        } catch (\Exception $e) {
            Log::error('文件删除失败', [
                'key' => $key,
                'user_id' => $user?->id,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件删除失败: ' . $e->getMessage());
        }
    }

    public function getUploadToken($type, User $user, $filename = null)
    {
        $directory = $this->getUploadDirectory($type, $user);
        $key = $directory . '/' . ($filename ?: Str::random(32));

        return $this->tencentCloudService->getUploadToken($key);
    }

    protected function getUploadDirectory($type, User $user)
    {
        switch ($type) {
            case 'artwork':
                return "artworks/{$user->id}";
            case 'avatar':
                return "avatars/{$user->id}";
            case 'banner':
                return 'banners';
            default:
                return "uploads/{$user->id}";
        }
    }

    public function getFileInfo($key)
    {
        return $this->tencentCloudService->getFileInfo($key);
    }

    public function getFileUrl($key)
    {
        return $this->tencentCloudService->getFileUrl($key);
    }

    public function getUserStorageStats(User $user)
    {
        // 这里可以实现用户存储统计
        // 在实际环境中，可以查询数据库或调用API获取用户的存储使用情况

        return [
            'total_files' => 0,
            'total_size' => 0,
            'artwork_files' => 0,
            'artwork_size' => 0,
            'avatar_files' => 0,
            'avatar_size' => 0,
            'quota_limit' => 1024 * 1024 * 1024, // 1GB默认配额
            'quota_used' => 0,
        ];
    }

    public function getAllowedTypes()
    {
        return [
            'image' => $this->allowedImageTypes,
            'video' => $this->allowedVideoTypes,
            'audio' => $this->allowedAudioTypes,
            'document' => $this->allowedDocumentTypes,
        ];
    }

    public function getUploadLimits()
    {
        return [
            'max_file_size' => $this->maxFileSize,
            'max_file_size_mb' => round($this->maxFileSize / 1024 / 1024, 1),
            'max_video_size' => 100 * 1024 * 1024,
            'max_audio_size' => 50 * 1024 * 1024,
            'max_avatar_size' => 5 * 1024 * 1024,
            'allowed_types' => $this->getAllowedTypes(),
        ];
    }
}