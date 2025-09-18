<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consumption_scenario_id',
        'amount_paid',
        'purchased_at',
        'expires_at',
        'status',
        'purchase_data',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:8',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'purchase_data' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_USED = 'used';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consumptionScenario()
    {
        return $this->belongsTo(ConsumptionScenario::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere(function($subQuery) {
                  $subQuery->where('status', self::STATUS_ACTIVE)
                           ->where('expires_at', '<=', now());
              });
        });
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE &&
               ($this->expires_at === null || $this->expires_at > now());
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at !== null && $this->expires_at <= now());
    }

    public function getRemainingTime()
    {
        if (!$this->expires_at || $this->isExpired()) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    public function getRemainingHours()
    {
        if (!$this->expires_at || $this->isExpired()) {
            return 0;
        }

        return max(0, now()->diffInHours($this->expires_at, false));
    }

    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public function cancel()
    {
        if ($this->isActive()) {
            $this->update(['status' => self::STATUS_CANCELLED]);

            // 可以考虑退款逻辑
            // $this->user->addPoints($this->amount_paid, 'refund', '消费取消退款');
        }
    }

    public function getEffects()
    {
        if (!$this->isActive()) {
            return [];
        }

        return $this->consumptionScenario->effects ?? [];
    }

    public function hasEffect($effectKey)
    {
        $effects = $this->getEffects();
        return isset($effects[$effectKey]) && $effects[$effectKey];
    }

    public function getEffectValue($effectKey, $default = null)
    {
        $effects = $this->getEffects();
        return $effects[$effectKey] ?? $default;
    }
}