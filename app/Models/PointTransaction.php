<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
        'reference_type',
        'reference_id',
        'admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'balance_before' => 'decimal:6',
        'balance_after' => 'decimal:6',
        'metadata' => 'array',
    ];

    const TYPES = [
        'view_reward' => '浏览奖励',
        'like_reward' => '点赞奖励',
        'download_reward' => '下载奖励',
        'share_reward' => '分享奖励',
        'comment_reward' => '评论奖励',
        'daily_checkin' => '每日签到',
        'whale_nft_bonus' => '鲸探NFT奖励',
        'creation_reward' => '创作奖励',
        'sale_revenue' => '销售收入',
        'artwork_purchase' => '作品购买',
        'proposal_creation' => '提案创建',
        'proposal_vote' => '提案投票',
        'governance_reward' => '治理奖励',
        'system_adjustment' => '系统调整',
        'admin_grant' => '管理员发放',
        'referral_bonus' => '推荐奖励',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function getTypeDisplayAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getIsIncomeAttribute()
    {
        return $this->amount > 0;
    }

    public function getIsExpenseAttribute()
    {
        return $this->amount < 0;
    }

    public static function getTotalVolume()
    {
        return static::sum('amount');
    }

    public static function getTotalIncome()
    {
        return static::where('amount', '>', 0)->sum('amount');
    }

    public static function getTotalExpense()
    {
        return static::where('amount', '<', 0)->sum('amount');
    }

    public static function getRecentActivity($limit = 50)
    {
        return static::with(['user', 'related'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
