<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumptionScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'scenario_key',
        'name',
        'description',
        'category',
        'price',
        'duration_hours',
        'is_active',
        'daily_limit',
        'total_limit',
        'requirements',
        'effects',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'is_active' => 'boolean',
        'requirements' => 'array',
        'effects' => 'array',
    ];

    const CATEGORY_GOVERNANCE = 'governance';
    const CATEGORY_PREMIUM = 'premium';
    const CATEGORY_PROMOTION = 'promotion';
    const CATEGORY_UTILITY = 'utility';

    public function userConsumptions()
    {
        return $this->hasMany(UserConsumption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function canUserPurchase($user)
    {
        if (!$this->is_active) {
            return false;
        }

        // 检查用户余额
        if ($user->points_balance < $this->price) {
            return false;
        }

        // 检查每日限制
        if ($this->daily_limit) {
            $todayPurchases = $this->userConsumptions()
                ->where('user_id', $user->id)
                ->whereDate('purchased_at', today())
                ->count();

            if ($todayPurchases >= $this->daily_limit) {
                return false;
            }
        }

        // 检查总限制
        if ($this->total_limit) {
            $totalPurchases = $this->userConsumptions()
                ->where('user_id', $user->id)
                ->count();

            if ($totalPurchases >= $this->total_limit) {
                return false;
            }
        }

        return $this->checkRequirements($user);
    }

    private function checkRequirements($user)
    {
        if (!$this->requirements) {
            return true;
        }

        // 检查账户年龄
        if (isset($this->requirements['min_account_age_days'])) {
            $accountAge = $user->created_at->diffInDays();
            if ($accountAge < $this->requirements['min_account_age_days']) {
                return false;
            }
        }

        // 检查是否有已审核作品
        if (isset($this->requirements['has_approved_artwork']) && $this->requirements['has_approved_artwork']) {
            $hasApprovedArtwork = $user->artworks()
                ->where('status', 'approved')
                ->exists();
            if (!$hasApprovedArtwork) {
                return false;
            }
        }

        return true;
    }

    public function getUserActiveEffect($user)
    {
        return $this->userConsumptions()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function getTotalPurchases()
    {
        return $this->userConsumptions()->count();
    }

    public function getTotalRevenue()
    {
        return $this->userConsumptions()->sum('amount_paid');
    }

    public static function getCategories()
    {
        return [
            self::CATEGORY_GOVERNANCE => '治理功能',
            self::CATEGORY_PREMIUM => '高级功能',
            self::CATEGORY_PROMOTION => '推广服务',
            self::CATEGORY_UTILITY => '实用工具',
        ];
    }
}