<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'user_id',
        'position',
        'vote_strength',
        'points_cost',
        'justification',
    ];

    protected $casts = [
        'vote_strength' => 'integer',
        'points_cost' => 'decimal:8',
    ];

    const POSITION_FOR = 'for';
    const POSITION_AGAINST = 'against';
    const POSITION_ABSTAIN = 'abstain';

    const POSITIONS = [
        self::POSITION_FOR => '赞成',
        self::POSITION_AGAINST => '反对',
        self::POSITION_ABSTAIN => '弃权',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions()
    {
        return $this->morphMany(PointTransaction::class, 'related');
    }

    public function getPositionDisplayAttribute()
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }

    public function getEffectiveVotingPowerAttribute()
    {
        return $this->vote_strength * $this->user->getVotingPower();
    }

    public static function calculateQuadraticCost($voteStrength)
    {
        return $voteStrength * $voteStrength;
    }
}
