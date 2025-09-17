<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id',
        'data',
        'description',
        'ip_address',
        'user_agent',
        'level',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    const TYPES = [
        'user_register' => '用户注册',
        'user_login' => '用户登入',
        'user_logout' => '用户登出',
        'whale_account_bind' => '鲸探账户绑定',
        'whale_account_sync' => '鲸探账户同步',
        'whale_nft_reward' => '鲸探NFT空投奖励',
        'point_transaction' => '积分交易',
        'artwork_upload' => '作品上传',
        'artwork_approved' => '作品审核通过',
        'artwork_rejected' => '作品审核拒绝',
        'artwork_view' => '作品浏览',
        'artwork_like' => '作品点赞',
        'artwork_download' => '作品下载',
        'artwork_sale' => '作品交易',
        'proposal_created' => '提案创建',
        'proposal_approved' => '提案审核通过',
        'proposal_rejected' => '提案审核拒绝',
        'proposal_finalized' => '提案投票结束',
        'vote_cast' => '投票参与',
        'banner_click' => '横幅点击',
        'system_maintenance' => '系统维护',
        'admin_action' => '管理员操作',
        'public_pool_adjustment' => '公共积分调整',
        'point_burn' => '积分销毁',
        'whale_pricing_update' => '鲸探定价更新',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForTransparency($query)
    {
        return $query->whereIn('type', [
            'point_transaction',
            'artwork_sale',
            'proposal_finalized',
            'vote_cast',
            'whale_nft_reward',
            'public_pool_adjustment',
            'point_burn',
            'whale_pricing_update',
        ]);
    }

    public function getTypeDisplayAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getFormattedDataAttribute()
    {
        if (!$this->data) return null;

        $formatted = [];
        foreach ($this->data as $key => $value) {
            if (is_numeric($value) && strpos($key, 'amount') !== false) {
                $formatted[$key] = number_format($value, 8);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    public static function logUserAction($type, $description, $data = [], $userId = null, $level = self::LEVEL_INFO)
    {
        return static::create([
            'type' => $type,
            'user_id' => $userId ?: auth()->id(),
            'description' => $description,
            'data' => $data,
            'level' => $level,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function getSystemStats()
    {
        return [
            'total_users' => \App\Models\User::count(),
            'total_points_in_circulation' => \App\Models\User::sum('points_balance'),
            'total_points_earned' => \App\Models\User::sum('total_points_earned'),
            'total_points_spent' => \App\Models\User::sum('total_points_spent'),
            'total_artworks' => \App\Models\Artwork::count(),
            'total_proposals' => \App\Models\Proposal::count(),
            'total_votes' => \App\Models\ProposalVote::count(),
            'total_whale_accounts' => \App\Models\WhaleAccount::where('verification_status', 'verified')->count(),
            'total_nft_collections' => \App\Models\NftCollection::count(),
            'public_pool_balance' => static::calculatePublicPoolBalance(),
            'total_burned_points' => static::calculateBurnedPoints(),
        ];
    }

    protected static function calculatePublicPoolBalance()
    {
        $income = \App\Models\PointTransaction::where('type', 'public_pool_income')->sum('amount');
        $spent = \App\Models\PointTransaction::where('type', 'public_pool_expense')->sum('amount');
        return $income + $spent;
    }

    protected static function calculateBurnedPoints()
    {
        return \App\Models\PointTransaction::where('type', 'point_burn')
            ->sum('amount');
    }

    public static function getRecentActivity($limit = 100)
    {
        return static::forTransparency()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
