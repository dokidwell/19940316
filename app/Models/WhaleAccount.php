<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhaleAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'alipay_uid',
        'whale_user_id',
        'nickname',
        'avatar_url',
        'nft_count',
        'total_value',
        'verification_status',
        'last_sync_at',
        'api_data',
    ];

    protected $casts = [
        'nft_count' => 'integer',
        'total_value' => 'decimal:8',
        'last_sync_at' => 'datetime',
        'api_data' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function nftCollections()
    {
        return $this->hasMany(NftCollection::class);
    }

    public function isVerified()
    {
        return $this->verification_status === self::STATUS_VERIFIED;
    }

    public function needsSync()
    {
        return !$this->last_sync_at || $this->last_sync_at->lt(now()->subHours(24));
    }

    public function calculateRewardMultiplier()
    {
        if (!$this->isVerified() || $this->nft_count === 0) {
            return 1.0;
        }

        return min(1.0 + ($this->nft_count * 0.1), 3.0);
    }

    public function updateFromWhaleApi($apiData)
    {
        $this->update([
            'nickname' => $apiData['nickname'] ?? $this->nickname,
            'avatar_url' => $apiData['avatar_url'] ?? $this->avatar_url,
            'nft_count' => $apiData['nft_count'] ?? 0,
            'total_value' => $apiData['total_value'] ?? 0,
            'verification_status' => self::STATUS_VERIFIED,
            'last_sync_at' => now(),
            'api_data' => $apiData,
        ]);

        $this->syncNftCollections($apiData['nft_collections'] ?? []);

        $this->users()->update([
            'whale_nft_count' => $this->nft_count,
            'is_verified' => true,
            'verification_level' => min(floor($this->nft_count / 10) + 1, 5),
        ]);

        SystemLog::create([
            'type' => 'whale_account_sync',
            'data' => [
                'whale_account_id' => $this->id,
                'nft_count' => $this->nft_count,
                'total_value' => $this->total_value,
            ],
            'description' => "鲸探账户同步: {$this->nickname}",
        ]);
    }

    protected function syncNftCollections($collections)
    {
        $this->nftCollections()->delete();

        foreach ($collections as $collection) {
            $this->nftCollections()->create([
                'whale_collection_id' => $collection['id'],
                'name' => $collection['name'],
                'image_url' => $collection['image_url'],
                'rarity' => $collection['rarity'] ?? 'common',
                'value' => $collection['value'] ?? 0,
                'metadata' => $collection,
            ]);
        }
    }

    public function getDisplayNameAttribute()
    {
        return $this->nickname ?: "鲸探用户{$this->whale_user_id}";
    }

    public function getTotalRewardMultiplierAttribute()
    {
        return $this->calculateRewardMultiplier();
    }
}
