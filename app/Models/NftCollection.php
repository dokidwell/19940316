<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NftCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whale_account_id',
        'whale_collection_id',
        'name',
        'description',
        'image_url',
        'rarity',
        'value',
        'acquired_at',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:8',
        'acquired_at' => 'datetime',
        'metadata' => 'array',
    ];

    const RARITIES = [
        'common' => '普通',
        'uncommon' => '稀有',
        'rare' => '稀有',
        'epic' => '史诗',
        'legendary' => '传说',
        'mythic' => '神话',
    ];

    const RARITY_MULTIPLIERS = [
        'common' => 1.0,
        'uncommon' => 1.2,
        'rare' => 1.5,
        'epic' => 2.0,
        'legendary' => 3.0,
        'mythic' => 5.0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whaleAccount()
    {
        return $this->belongsTo(WhaleAccount::class);
    }

    public function getRarityDisplayAttribute()
    {
        return self::RARITIES[$this->rarity] ?? $this->rarity;
    }

    public function getRarityMultiplierAttribute()
    {
        return self::RARITY_MULTIPLIERS[$this->rarity] ?? 1.0;
    }

    public function getRewardBonusAttribute()
    {
        return $this->rarity_multiplier * 0.1;
    }

    public function isHighValue()
    {
        return $this->value >= 1000.00000000;
    }

    public function isRare()
    {
        return in_array($this->rarity, ['rare', 'epic', 'legendary', 'mythic']);
    }

    public static function getTotalValueForUser($userId)
    {
        return static::where('user_id', $userId)->sum('value');
    }

    public static function getCountByRarity($userId = null)
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->groupBy('rarity')
            ->selectRaw('rarity, count(*) as count')
            ->pluck('count', 'rarity')
            ->toArray();
    }
}
