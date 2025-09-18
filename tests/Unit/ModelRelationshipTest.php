<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Artwork;
use App\Models\PointTransaction;
use App\Models\Proposal;
use App\Models\ProposalVote;
use App\Models\WhaleAccount;
use App\Models\NftCollection;
use App\Models\Banner;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試User模型關係
     */
    public function test_user_model_relationships(): void
    {
        $user = User::factory()->create();

        // 測試artworks關係
        $artwork = Artwork::factory()->create(['creator_id' => $user->id]);
        $this->assertTrue($user->artworks->contains($artwork));

        // 測試pointTransactions關係
        $transaction = PointTransaction::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->pointTransactions->contains($transaction));

        // 測試proposals關係
        $proposal = Proposal::factory()->create(['creator_id' => $user->id]);
        $this->assertTrue($user->proposals->contains($proposal));

        // 測試proposalVotes關係
        $vote = ProposalVote::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->proposalVotes->contains($vote));

        // 測試whaleAccount關係
        $whaleAccount = WhaleAccount::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(WhaleAccount::class, $user->whaleAccount);
        $this->assertEquals($whaleAccount->id, $user->whaleAccount->id);

        // 測試nftCollections關係
        $nftCollection = NftCollection::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->nftCollections->contains($nftCollection));
    }

    /**
     * 測試Artwork模型關係
     */
    public function test_artwork_model_relationships(): void
    {
        $creator = User::factory()->create();
        $artwork = Artwork::factory()->create(['creator_id' => $creator->id]);

        // 測試creator關係
        $this->assertInstanceOf(User::class, $artwork->creator);
        $this->assertEquals($creator->id, $artwork->creator->id);
        $this->assertEquals($creator->name, $artwork->creator->name);
    }

    /**
     * 測試PointTransaction模型關係
     */
    public function test_point_transaction_model_relationships(): void
    {
        $user = User::factory()->create();
        $transaction = PointTransaction::factory()->create(['user_id' => $user->id]);

        // 測試user關係
        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
        $this->assertEquals($user->name, $transaction->user->name);
    }

    /**
     * 測試Proposal模型關係
     */
    public function test_proposal_model_relationships(): void
    {
        $creator = User::factory()->create();
        $proposal = Proposal::factory()->create(['creator_id' => $creator->id]);

        // 測試creator關係
        $this->assertInstanceOf(User::class, $proposal->creator);
        $this->assertEquals($creator->id, $proposal->creator->id);

        // 測試votes關係
        $voter1 = User::factory()->create();
        $voter2 = User::factory()->create();

        $vote1 = ProposalVote::factory()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $voter1->id
        ]);
        $vote2 = ProposalVote::factory()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $voter2->id
        ]);

        $this->assertTrue($proposal->votes->contains($vote1));
        $this->assertTrue($proposal->votes->contains($vote2));
        $this->assertCount(2, $proposal->votes);
    }

    /**
     * 測試ProposalVote模型關係
     */
    public function test_proposal_vote_model_relationships(): void
    {
        $user = User::factory()->create();
        $proposal = Proposal::factory()->create();
        $vote = ProposalVote::factory()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $user->id
        ]);

        // 測試proposal關係
        $this->assertInstanceOf(Proposal::class, $vote->proposal);
        $this->assertEquals($proposal->id, $vote->proposal->id);

        // 測試user關係
        $this->assertInstanceOf(User::class, $vote->user);
        $this->assertEquals($user->id, $vote->user->id);
    }

    /**
     * 測試WhaleAccount模型關係
     */
    public function test_whale_account_model_relationships(): void
    {
        $user = User::factory()->create();
        $whaleAccount = WhaleAccount::factory()->create(['user_id' => $user->id]);

        // 測試user關係
        $this->assertInstanceOf(User::class, $whaleAccount->user);
        $this->assertEquals($user->id, $whaleAccount->user->id);

        // 測試nftCollections關係
        $nftCollection1 = NftCollection::factory()->create(['user_id' => $user->id]);
        $nftCollection2 = NftCollection::factory()->create(['user_id' => $user->id]);

        $nftCollections = $whaleAccount->nftCollections;
        $this->assertTrue($nftCollections->contains($nftCollection1));
        $this->assertTrue($nftCollections->contains($nftCollection2));
        $this->assertCount(2, $nftCollections);
    }

    /**
     * 測試NftCollection模型關係
     */
    public function test_nft_collection_model_relationships(): void
    {
        $user = User::factory()->create();
        $nftCollection = NftCollection::factory()->create(['user_id' => $user->id]);

        // 測試user關係
        $this->assertInstanceOf(User::class, $nftCollection->user);
        $this->assertEquals($user->id, $nftCollection->user->id);

        // 測試whaleAccount關係（通過user）
        $whaleAccount = WhaleAccount::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(WhaleAccount::class, $nftCollection->user->whaleAccount);
    }

    /**
     * 測試Banner模型關係
     */
    public function test_banner_model_relationships(): void
    {
        $creator = User::factory()->create();
        $banner = Banner::factory()->create(['created_by' => $creator->id]);

        // 測試creator關係
        $this->assertInstanceOf(User::class, $banner->creator);
        $this->assertEquals($creator->id, $banner->creator->id);
    }

    /**
     * 測試SystemLog模型關係
     */
    public function test_system_log_model_relationships(): void
    {
        $user = User::factory()->create();
        $systemLog = SystemLog::factory()->create(['user_id' => $user->id]);

        // 測試user關係
        $this->assertInstanceOf(User::class, $systemLog->user);
        $this->assertEquals($user->id, $systemLog->user->id);
    }

    /**
     * 測試級聯刪除行為
     */
    public function test_cascade_delete_behavior(): void
    {
        $user = User::factory()->create();

        // 創建相關數據
        $artwork = Artwork::factory()->create(['creator_id' => $user->id]);
        $transaction = PointTransaction::factory()->create(['user_id' => $user->id]);
        $proposal = Proposal::factory()->create(['creator_id' => $user->id]);
        $whaleAccount = WhaleAccount::factory()->create(['user_id' => $user->id]);
        $nftCollection = NftCollection::factory()->create(['user_id' => $user->id]);

        // 刪除用戶
        $user->delete();

        // 檢查相關數據是否正確處理
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // 有些關係應該被級聯刪除，有些應該保留但將外鍵設為null
        // 具體行為取決於模型中的配置
        $this->assertDatabaseMissing('whale_accounts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('nft_collections', ['user_id' => $user->id]);
    }

    /**
     * 測試查詢範圍（Scopes）
     */
    public function test_model_scopes(): void
    {
        // 測試User的active scope
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $activeUsers = User::active()->get();
        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));

        // 測試Proposal的active scope
        $activeProposal = Proposal::factory()->create(['status' => 'active']);
        $draftProposal = Proposal::factory()->create(['status' => 'draft']);

        $activeProposals = Proposal::active()->get();
        $this->assertTrue($activeProposals->contains($activeProposal));
        $this->assertFalse($activeProposals->contains($draftProposal));

        // 測試PointTransaction的byType scope
        $rewardTransaction = PointTransaction::factory()->create(['type' => 'reward']);
        $purchaseTransaction = PointTransaction::factory()->create(['type' => 'purchase']);

        $rewardTransactions = PointTransaction::byType('reward')->get();
        $this->assertTrue($rewardTransactions->contains($rewardTransaction));
        $this->assertFalse($rewardTransactions->contains($purchaseTransaction));
    }

    /**
     * 測試模型訪問器（Accessors）
     */
    public function test_model_accessors(): void
    {
        // 測試User的formatted_balance訪問器
        $user = User::factory()->create(['points_balance' => '1234.56789012']);
        $this->assertEquals('1,234.56789012', $user->formatted_balance);

        // 測試User的avatar_url訪問器
        $userWithAvatar = User::factory()->create(['avatar' => 'avatar.jpg']);
        $this->assertStringContainsString('avatar.jpg', $userWithAvatar->avatar_url);

        $userWithoutAvatar = User::factory()->create(['avatar' => null]);
        $this->assertStringContainsString('default-avatar.png', $userWithoutAvatar->avatar_url);
    }

    /**
     * 測試模型修改器（Mutators）
     */
    public function test_model_mutators(): void
    {
        // 測試User的password修改器
        $user = User::factory()->create(['password' => 'plain-password']);
        $this->assertTrue(\Hash::check('plain-password', $user->password));

        // 測試PointTransaction的amount修改器（確保8位精度）
        $transaction = PointTransaction::factory()->create(['amount' => '123.123456789']);
        $this->assertEquals('123.12345679', $transaction->amount); // 四捨五入到8位
    }

    /**
     * 測試多對多關係
     */
    public function test_many_to_many_relationships(): void
    {
        // 如果有多對多關係，例如用戶收藏作品
        $user = User::factory()->create();
        $artwork1 = Artwork::factory()->create();
        $artwork2 = Artwork::factory()->create();

        // 假設有收藏功能
        // $user->favoriteArtworks()->attach([$artwork1->id, $artwork2->id]);
        // $this->assertCount(2, $user->favoriteArtworks);
        // $this->assertTrue($user->favoriteArtworks->contains($artwork1));
    }

    /**
     * 測試模型事件
     */
    public function test_model_events(): void
    {
        // 測試User創建時的事件
        $user = User::factory()->create();

        // 檢查是否自動生成了HOHO ID
        $this->assertNotNull($user->hoho_id);
        $this->assertStringStartsWith('H', $user->hoho_id);

        // 檢查是否設置了初始積分餘額
        $this->assertEquals('10000.00000000', $user->points_balance);
    }

    /**
     * 測試複雜查詢
     */
    public function test_complex_queries(): void
    {
        $user = User::factory()->create();

        // 創建不同類型的交易
        PointTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'reward',
            'amount' => '100.00000000'
        ]);
        PointTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => '-50.00000000'
        ]);

        // 測試用戶的獲得積分總額
        $totalEarned = $user->pointTransactions()
            ->where('amount', '>', 0)
            ->sum('amount');
        $this->assertEquals('100.00000000', $totalEarned);

        // 測試用戶的消費積分總額
        $totalSpent = $user->pointTransactions()
            ->where('amount', '<', 0)
            ->sum('amount');
        $this->assertEquals('-50.00000000', $totalSpent);
    }
}