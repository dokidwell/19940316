<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'type',
        'file_path',
        'file_size',
        'mime_type',
        'thumbnail_path',
        'metadata',
        'price',
        'is_for_sale',
        'is_featured',
        'view_count',
        'like_count',
        'download_count',
        'category_id',
        'tags',
        'status',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'price' => 'decimal:8',
        'is_for_sale' => 'boolean',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'download_count' => 'integer',
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function pointTransactions()
    {
        return $this->morphMany(PointTransaction::class, 'related');
    }

    public function incrementView()
    {
        $this->increment('view_count');
        // 社区作品不再产生积分奖励，仅用于学习交流
    }

    public function incrementLike()
    {
        $this->increment('like_count');
        // 社区作品不再产生积分奖励，仅用于学习交流
    }

    public function incrementDownload()
    {
        $this->increment('download_count');
        // 社区作品不再产生积分奖励，仅用于学习交流
    }

    public function handlePurchase($buyer, $amount)
    {
        $creatorShare = $amount * 0.70;
        $publicPoolShare = $amount * 0.25;
        $burnAmount = $amount * 0.05;

        $this->creator->addPoints(
            $creatorShare,
            'sale_revenue',
            "作品《{$this->title}》销售收入",
            $this
        );

        $buyer->subtractPoints(
            $amount,
            'artwork_purchase',
            "购买作品《{$this->title}》",
            $this
        );

        SystemLog::create([
            'type' => 'artwork_sale',
            'user_id' => $buyer->id,
            'data' => [
                'artwork_id' => $this->id,
                'creator_id' => $this->creator_id,
                'amount' => $amount,
                'creator_share' => $creatorShare,
                'public_pool_share' => $publicPoolShare,
                'burn_amount' => $burnAmount,
            ],
            'description' => "作品交易: 《{$this->title}》",
        ]);
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : $this->file_url;
    }
}
