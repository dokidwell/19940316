<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'category',
        'voting_start_at',
        'voting_end_at',
        'min_points_to_vote',
        'status',
        'vote_count_for',
        'vote_count_against',
        'vote_count_abstain',
        'points_spent_for',
        'points_spent_against',
        'points_spent_abstain',
        'result',
        'executed_at',
        'metadata',
    ];

    protected $casts = [
        'voting_start_at' => 'datetime',
        'voting_end_at' => 'datetime',
        'min_points_to_vote' => 'decimal:8',
        'vote_count_for' => 'integer',
        'vote_count_against' => 'integer',
        'vote_count_abstain' => 'integer',
        'points_spent_for' => 'decimal:8',
        'points_spent_against' => 'decimal:8',
        'points_spent_abstain' => 'decimal:8',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ENDED = 'ended';
    const STATUS_EXECUTED = 'executed';
    const STATUS_CANCELLED = 'cancelled';

    const RESULT_APPROVED = 'approved';
    const RESULT_REJECTED = 'rejected';
    const RESULT_TIED = 'tied';

    const CATEGORIES = [
        'platform_improvement' => '平台改进',
        'feature_request' => '功能请求',
        'community_rule' => '社区规则',
        'economic_policy' => '经济政策',
        'governance_change' => '治理变更',
        'other' => '其他',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function votes()
    {
        return $this->hasMany(ProposalVote::class);
    }

    public function pointTransactions()
    {
        return $this->morphMany(PointTransaction::class, 'related');
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->voting_start_at <= now() &&
               $this->voting_end_at > now();
    }

    public function hasEnded()
    {
        return $this->voting_end_at <= now();
    }

    public function canVote(User $user)
    {
        return $this->isActive() &&
               $user->points_balance >= $this->min_points_to_vote &&
               !$this->votes()->where('user_id', $user->id)->exists();
    }

    public function vote(User $user, $position, $voteStrength = 1)
    {
        if (!$this->canVote($user)) {
            throw new \Exception('用户不能对此提案投票');
        }

        $quadraticCost = $voteStrength * $voteStrength;

        if ($user->points_balance < $quadraticCost) {
            throw new \Exception('积分不足以支付投票成本');
        }

        $vote = $this->votes()->create([
            'user_id' => $user->id,
            'position' => $position,
            'vote_strength' => $voteStrength,
            'points_cost' => $quadraticCost,
        ]);

        $user->subtractPoints(
            $quadraticCost,
            'proposal_vote',
            "对提案《{$this->title}》投票",
            $this
        );

        $this->updateVoteCounts();

        return $vote;
    }

    public function updateVoteCounts()
    {
        $this->vote_count_for = $this->votes()->where('position', 'for')->sum('vote_strength');
        $this->vote_count_against = $this->votes()->where('position', 'against')->sum('vote_strength');
        $this->vote_count_abstain = $this->votes()->where('position', 'abstain')->sum('vote_strength');

        $this->points_spent_for = $this->votes()->where('position', 'for')->sum('points_cost');
        $this->points_spent_against = $this->votes()->where('position', 'against')->sum('points_cost');
        $this->points_spent_abstain = $this->votes()->where('position', 'abstain')->sum('points_cost');

        $this->save();
    }

    public function finalizeVoting()
    {
        if (!$this->hasEnded()) {
            throw new \Exception('投票尚未结束');
        }

        $this->updateVoteCounts();

        if ($this->vote_count_for > $this->vote_count_against) {
            $this->result = self::RESULT_APPROVED;
        } elseif ($this->vote_count_against > $this->vote_count_for) {
            $this->result = self::RESULT_REJECTED;
        } else {
            $this->result = self::RESULT_TIED;
        }

        $this->status = self::STATUS_ENDED;
        $this->save();

        SystemLog::create([
            'type' => 'proposal_finalized',
            'data' => [
                'proposal_id' => $this->id,
                'result' => $this->result,
                'votes_for' => $this->vote_count_for,
                'votes_against' => $this->vote_count_against,
                'votes_abstain' => $this->vote_count_abstain,
            ],
            'description' => "提案《{$this->title}》投票结束",
        ]);
    }

    public function getCategoryDisplayAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getTotalVotesAttribute()
    {
        return $this->vote_count_for + $this->vote_count_against + $this->vote_count_abstain;
    }

    public function getTotalPointsSpentAttribute()
    {
        return $this->points_spent_for + $this->points_spent_against + $this->points_spent_abstain;
    }
}
