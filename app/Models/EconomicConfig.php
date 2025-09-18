<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_key',
        'config_name',
        'description',
        'config_type',
        'config_value',
        'min_value',
        'max_value',
        'is_active',
        'effective_at',
        'updated_by',
        'change_reason',
    ];

    protected $casts = [
        'config_value' => 'decimal:8',
        'min_value' => 'decimal:8',
        'max_value' => 'decimal:8',
        'is_active' => 'boolean',
        'effective_at' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('effective_at')
                          ->orWhere('effective_at', '<=', now());
                    });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('config_type', $type);
    }

    public function isEffective()
    {
        return $this->is_active &&
               ($this->effective_at === null || $this->effective_at <= now());
    }

    public static function getValue($key, $default = null)
    {
        $config = static::where('config_key', $key)
            ->active()
            ->first();

        return $config ? $config->config_value : $default;
    }
}