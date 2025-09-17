<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TencentCloudService
{
    protected $cosClient;
    protected $bucket;
    protected $region;
    protected $domain;

    public function __construct()
    {
        $this->bucket = config('services.tencent.cos.bucket');
        $this->region = config('services.tencent.cos.region');
        $this->domain = config('services.tencent.cos.domain');

        $this->initializeCosClient();
    }

    protected function initializeCosClient()
    {
        $secretId = config('services.tencent.cos.secret_id');
        $secretKey = config('services.tencent.cos.secret_key');

        if (!$secretId || !$secretKey) {
            throw new \Exception('腾讯云COS配置不完整');
        }

        // 这里我们模拟腾讯云COS客户端的初始化
        // 在实际环境中，你需要安装并使用腾讯云COS PHP SDK
        $this->cosClient = (object) [
            'secretId' => $secretId,
            'secretKey' => $secretKey,
            'region' => $this->region,
            'bucket' => $this->bucket,
        ];
    }

    public function uploadFile(UploadedFile $file, $directory = 'uploads', $filename = null, $options = [])
    {
        try {
            $this->validateFile($file);

            $filename = $filename ?: $this->generateFilename($file);
            $key = $directory . '/' . $filename;

            $uploadResult = $this->performUpload($file, $key, $options);

            $fileInfo = [
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'key' => $key,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'url' => $this->getFileUrl($key),
                'bucket' => $this->bucket,
                'region' => $this->region,
                'etag' => $uploadResult['etag'] ?? null,
                'uploaded_at' => now(),
            ];

            SystemLog::logUserAction(
                'file_upload',
                "文件上传: {$file->getClientOriginalName()}",
                [
                    'key' => $key,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'url' => $fileInfo['url'],
                ],
                auth()->id()
            );

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('腾讯云文件上传失败', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件上传失败: ' . $e->getMessage());
        }
    }

    public function uploadArtwork(UploadedFile $file, $userId, $artworkId = null)
    {
        $directory = "artworks/{$userId}" . ($artworkId ? "/{$artworkId}" : '');

        $options = [
            'content_type' => $file->getMimeType(),
            'cache_control' => 'max-age=31536000',
        ];

        if ($this->isImageFile($file)) {
            $options['process'] = $this->getImageProcessOptions($file);
        }

        return $this->uploadFile($file, $directory, null, $options);
    }

    public function uploadAvatar(UploadedFile $file, $userId)
    {
        $directory = "avatars/{$userId}";

        $options = [
            'content_type' => $file->getMimeType(),
            'cache_control' => 'max-age=86400',
        ];

        if ($this->isImageFile($file)) {
            $options['process'] = 'image/resize,w_300,h_300,m_fill|image/quality,q_80';
        }

        return $this->uploadFile($file, $directory, null, $options);
    }

    public function uploadWhaleAvatar($imageUrl, $userId, $nftId)
    {
        try {
            $filename = "whale_nft_{$nftId}_" . Str::random(8) . '.jpg';
            $key = "avatars/{$userId}/{$filename}";

            $imageContent = file_get_contents($imageUrl);

            if (!$imageContent) {
                throw new \Exception('无法获取鲸探NFT图片');
            }

            $uploadResult = $this->performDirectUpload($imageContent, $key, [
                'content_type' => 'image/jpeg',
                'cache_control' => 'max-age=86400',
                'process' => 'image/resize,w_300,h_300,m_fill|image/quality,q_80',
            ]);

            $fileInfo = [
                'original_name' => "whale_nft_{$nftId}.jpg",
                'filename' => $filename,
                'key' => $key,
                'size' => strlen($imageContent),
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'url' => $this->getFileUrl($key),
                'bucket' => $this->bucket,
                'region' => $this->region,
                'etag' => $uploadResult['etag'] ?? null,
                'uploaded_at' => now(),
                'source' => 'whale_nft',
                'source_id' => $nftId,
            ];

            SystemLog::logUserAction(
                'whale_avatar_upload',
                "鲸探NFT头像上传: {$nftId}",
                [
                    'key' => $key,
                    'nft_id' => $nftId,
                    'url' => $fileInfo['url'],
                ],
                $userId
            );

            return $fileInfo;

        } catch (\Exception $e) {
            Log::error('鲸探NFT头像上传失败', [
                'user_id' => $userId,
                'nft_id' => $nftId,
                'image_url' => $imageUrl,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('鲸探NFT头像上传失败: ' . $e->getMessage());
        }
    }

    protected function performUpload(UploadedFile $file, $key, $options = [])
    {
        // 在实际环境中，这里会调用腾讯云COS SDK
        // 现在我们模拟上传过程

        $fileContent = file_get_contents($file->getRealPath());

        return $this->performDirectUpload($fileContent, $key, $options);
    }

    protected function performDirectUpload($content, $key, $options = [])
    {
        // 模拟腾讯云COS上传
        // 在实际环境中，这里会调用腾讯云COS SDK的putObject方法

        try {
            // 模拟网络延迟
            usleep(100000); // 0.1秒

            // 模拟成功的响应
            return [
                'etag' => '"' . md5($content) . '"',
                'location' => $this->getFileUrl($key),
                'key' => $key,
                'bucket' => $this->bucket,
            ];

        } catch (\Exception $e) {
            throw new \Exception('腾讯云COS上传失败: ' . $e->getMessage());
        }
    }

    public function deleteFile($key)
    {
        try {
            // 在实际环境中，这里会调用腾讯云COS SDK的deleteObject方法

            SystemLog::logUserAction(
                'file_delete',
                "文件删除: {$key}",
                ['key' => $key],
                auth()->id()
            );

            return true;

        } catch (\Exception $e) {
            Log::error('腾讯云文件删除失败', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件删除失败: ' . $e->getMessage());
        }
    }

    public function getFileUrl($key)
    {
        if ($this->domain) {
            return rtrim($this->domain, '/') . '/' . ltrim($key, '/');
        }

        return "https://{$this->bucket}.cos.{$this->region}.myqcloud.com/{$key}";
    }

    public function generateThumbnail($key, $width = 300, $height = 300, $quality = 80)
    {
        $thumbnailKey = str_replace('.', "_thumb_{$width}x{$height}.", $key);

        try {
            // 在实际环境中，这里会使用腾讯云COS的图片处理功能
            $processParams = "image/resize,w_{$width},h_{$height},m_fill|image/quality,q_{$quality}";

            return [
                'key' => $thumbnailKey,
                'url' => $this->getFileUrl($key) . '?' . $processParams,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
            ];

        } catch (\Exception $e) {
            Log::error('缩略图生成失败', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('缩略图生成失败: ' . $e->getMessage());
        }
    }

    public function listFiles($directory, $limit = 100, $marker = null)
    {
        try {
            // 在实际环境中，这里会调用腾讯云COS SDK的listObjects方法

            return [
                'files' => [],
                'next_marker' => null,
                'is_truncated' => false,
            ];

        } catch (\Exception $e) {
            Log::error('文件列表获取失败', [
                'directory' => $directory,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件列表获取失败: ' . $e->getMessage());
        }
    }

    public function getFileInfo($key)
    {
        try {
            // 在实际环境中，这里会调用腾讯云COS SDK的headObject方法

            return [
                'key' => $key,
                'size' => 0,
                'last_modified' => now(),
                'etag' => '',
                'content_type' => 'application/octet-stream',
                'url' => $this->getFileUrl($key),
            ];

        } catch (\Exception $e) {
            Log::error('文件信息获取失败', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('文件信息获取失败: ' . $e->getMessage());
        }
    }

    protected function validateFile(UploadedFile $file)
    {
        $maxSize = config('services.uploads.max_file_size', 50 * 1024 * 1024); // 50MB
        $allowedTypes = array_merge(
            config('services.uploads.allowed_image_types', []),
            config('services.uploads.allowed_video_types', []),
            config('services.uploads.allowed_audio_types', []),
            config('services.uploads.allowed_document_types', [])
        );

        if ($file->getSize() > $maxSize) {
            throw new \Exception('文件大小超过限制');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            throw new \Exception('不支持的文件类型');
        }

        if (!$file->isValid()) {
            throw new \Exception('文件上传失败');
        }
    }

    protected function generateFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    protected function isImageFile(UploadedFile $file)
    {
        $imageTypes = config('services.uploads.allowed_image_types', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, $imageTypes);
    }

    protected function getImageProcessOptions(UploadedFile $file)
    {
        $quality = config('services.uploads.image_quality', 85);

        // 根据文件大小和类型设置不同的处理参数
        $size = $file->getSize();

        if ($size > 5 * 1024 * 1024) { // 大于5MB
            return "image/quality,q_{$quality}|image/resize,w_1920,h_1080,m_lfit";
        } elseif ($size > 1 * 1024 * 1024) { // 大于1MB
            return "image/quality,q_{$quality}";
        }

        return null;
    }

    public function getUploadToken($key, $expireTime = 3600)
    {
        // 在实际环境中，这里会生成腾讯云COS的上传签名
        // 现在我们模拟一个上传token

        $expireAt = now()->addSeconds($expireTime);

        return [
            'token' => base64_encode(json_encode([
                'key' => $key,
                'bucket' => $this->bucket,
                'region' => $this->region,
                'expire_at' => $expireAt->timestamp,
            ])),
            'expire_at' => $expireAt,
            'upload_url' => "https://{$this->bucket}.cos.{$this->region}.myqcloud.com",
            'key' => $key,
        ];
    }

    public function getDownloadUrl($key, $expireTime = 3600)
    {
        // 对于公开读的文件，直接返回URL
        // 对于私有文件，生成带签名的临时URL

        return $this->getFileUrl($key);
    }

    public function getBatchUploadTokens($keys, $expireTime = 3600)
    {
        $tokens = [];

        foreach ($keys as $key) {
            $tokens[] = $this->getUploadToken($key, $expireTime);
        }

        return $tokens;
    }

    public function getStorageStats()
    {
        // 在实际环境中，这里会调用腾讯云COS API获取存储统计信息

        return [
            'total_objects' => 0,
            'total_size' => 0,
            'bucket' => $this->bucket,
            'region' => $this->region,
        ];
    }
}