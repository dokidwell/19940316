<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public function artworks()
    {
        return $this->hasMany(Artwork::class, 'creator_id');
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'creator_id');
    }

    public function votes()
    {
        return $this->hasMany(ProposalVote::class);
    }

    public function whaleAccount()
    {
        return $this->belongsTo(WhaleAccount::class);
    }

    public function nftCollections()
    {
        return $this->hasMany(NftCollection::class);
    }

    public function addPoints($amount, $type, $description = null, $relatedModel = null)
    {
        $transaction = $this->pointTransactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->points_balance + $amount,
            'description' => $description,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_id' => $relatedModel ? $relatedModel->id : null,
        ]);

        $this->increment('points_balance', $amount);
        $this->increment('total_points_earned', $amount);

        return $transaction;
    }

    public function subtractPoints($amount, $type, $description = null, $relatedModel = null)
    {
        if ($this->points_balance < $amount) {
            throw new \Exception('Insufficient points balance');
        }

        $transaction = $this->pointTransactions()->create([
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->points_balance - $amount,
            'description' => $description,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_id' => $relatedModel ? $relatedModel->id : null,
        ]);

        $this->decrement('points_balance', $amount);
        $this->increment('total_points_spent', $amount);

        return $transaction;
    }

    public function getWhaleRewardMultiplier()
    {
        if (!$this->whale_account_id || $this->whale_nft_count === 0) {
            return 1.0;
        }

        return min(1.0 + ($this->whale_nft_count * 0.1), 3.0);
    }

    public function canCreateProposal()
    {
        return $this->points_balance >= 1000.00000000 && $this->is_verified;
    }

    public function getVotingPower()
    {
        return sqrt($this->points_balance);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hoho_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'whale_account_id',
        'whale_nft_count',
        'is_verified',
        'verification_level',
        'social_links',
        'bio',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'whale_nft_count' => 'integer',
        'is_verified' => 'boolean',
        'verification_level' => 'integer',
        'social_links' => 'array',
        'is_active' => 'boolean',
        'points_balance' => 'decimal:8',
        'total_points_earned' => 'decimal:8',
        'total_points_spent' => 'decimal:8',
    ];
}
