<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'link_url',
        'type',
        'position',
        'is_active',
        'display_order',
        'start_date',
        'end_date',
        'click_count',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'click_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];

    const TYPE_HERO = 'hero';
    const TYPE_ARTWORK = 'artwork';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_PROMOTION = 'promotion';

    const POSITION_HOME = 'home';
    const POSITION_ARTWORK = 'artwork';
    const POSITION_MARKET = 'market';
    const POSITION_COMMUNITY = 'community';

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at', 'desc');
    }

    public function incrementClick()
    {
        $this->increment('click_count');

        SystemLog::create([
            'type' => 'banner_click',
            'data' => [
                'banner_id' => $this->id,
                'title' => $this->title,
                'click_count' => $this->click_count + 1,
            ],
            'description' => "横幅点击: {$this->title}",
        ]);
    }

    public function isExpired()
    {
        return $this->end_date && $this->end_date->lt(now());
    }

    public function isScheduled()
    {
        return $this->start_date && $this->start_date->gt(now());
    }

    public function getImageUrlAttribute($value)
    {
        if (!$value) return null;

        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return asset('storage/' . $value);
    }
}
