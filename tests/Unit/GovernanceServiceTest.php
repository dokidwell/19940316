<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GovernanceService;
use App\Models\User;
use App\Models\Proposal;
use App\Models\ProposalVote;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GovernanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GovernanceService $governanceService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->governanceService = app(GovernanceService::class);
        $this->user = User::factory()->create([
            'points_balance' => '10000.00000000'
        ]);
    }

    /**
     * 測試二次方投票成本計算
     */
    public function test_quadratic_voting_cost_calculation(): void
    {
        // 投票強度1的成本應該是1
        $cost1 = $this->governanceService->calculateVotingCost(1);
        $this->assertEquals('1.00000000', $cost1);

        // 投票強度10的成本應該是100 (10²)
        $cost10 = $this->governanceService->calculateVotingCost(10);
        $this->assertEquals('100.00000000', $cost10);

        // 投票強度5的成本應該是25 (5²)
        $cost5 = $this->governanceService->calculateVotingCost(5);
        $this->assertEquals('25.00000000', $cost5);
    }

    /**
     * 測試用戶創建提案權限
     */
    public function test_user_proposal_creation_permission(): void
    {
        // 積分充足的用戶應該可以創建提案
        $permission = $this->governanceService->canUserCreateProposal($this->user);
        $this->assertTrue($permission['can_create']);

        // 積分不足的用戶不能創建提案
        $poorUser = User::factory()->create([
            'points_balance' => '100.00000000'
        ]);
        $permission = $this->governanceService->canUserCreateProposal($poorUser);
        $this->assertFalse($permission['can_create']);
        $this->assertStringContainsString('積分不足', $permission['reason']);
    }

    /**
     * 測試提案創建流程
     */
    public function test_proposal_creation(): void
    {
        $proposalData = [
            'title' => '測試提案',
            'description' => '這是一個測試提案的描述',
            'category' => 'platform_improvement',
            'voting_start_at' => now()->addHour(),
            'voting_end_at' => now()->addDays(7),
            'min_points_to_vote' => '1.00000000'
        ];

        $proposal = $this->governanceService->createProposal($this->user, $proposalData);

        $this->assertInstanceOf(Proposal::class, $proposal);
        $this->assertEquals('測試提案', $proposal->title);
        $this->assertEquals('draft', $proposal->status);
        $this->assertEquals($this->user->id, $proposal->creator_id);

        // 檢查創建者積分是否扣除
        $expectedBalance = '10000.00000000' - config('governance.min_points_create', '1000.00000000');
        $this->assertEquals($expectedBalance, $this->user->fresh()->points_balance);
    }

    /**
     * 測試投票權限檢查
     */
    public function test_voting_permission_check(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        // 有足夠積分的用戶可以投票
        $permission = $this->governanceService->canUserVoteOnProposal($proposal, $this->user);
        $this->assertTrue($permission['can_vote']);

        // 積分不足的用戶不能投票
        $poorUser = User::factory()->create([
            'points_balance' => '50.00000000'
        ]);
        $permission = $this->governanceService->canUserVoteOnProposal($proposal, $poorUser);
        $this->assertFalse($permission['can_vote']);
        $this->assertStringContainsString('積分不足', $permission['reason']);
    }

    /**
     * 測試投票流程
     */
    public function test_voting_process(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        $initialBalance = $this->user->points_balance;
        $voteStrength = 5;
        $expectedCost = $this->governanceService->calculateVotingCost($voteStrength);

        $vote = $this->governanceService->castVote(
            $proposal,
            $this->user,
            'for',
            $voteStrength,
            '我支持這個提案'
        );

        $this->assertInstanceOf(ProposalVote::class, $vote);
        $this->assertEquals('for', $vote->position);
        $this->assertEquals($voteStrength, $vote->vote_strength);
        $this->assertEquals($expectedCost, $vote->points_cost);

        // 檢查用戶積分是否正確扣除
        $expectedBalance = $initialBalance - $expectedCost;
        $this->assertEquals($expectedBalance, $this->user->fresh()->points_balance);
    }

    /**
     * 測試重複投票防護
     */
    public function test_duplicate_voting_prevention(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        // 第一次投票
        $this->governanceService->castVote($proposal, $this->user, 'for', 3);

        // 第二次投票應該失敗
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('已經投過票');

        $this->governanceService->castVote($proposal, $this->user, 'against', 2);
    }

    /**
     * 測試投票力度限制
     */
    public function test_vote_strength_limits(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->addDays(6),
            'min_points_to_vote' => '100.00000000'
        ]);

        $maxStrength = $this->governanceService->getMaxVoteStrengthForUser($this->user);
        $this->assertGreaterThan(0, $maxStrength);
        $this->assertLessThanOrEqual(100, $maxStrength);

        // 測試超過最大強度的投票
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('投票強度超過限制');

        $this->governanceService->castVote($proposal, $this->user, 'for', $maxStrength + 1);
    }

    /**
     * 測試提案結果計算
     */
    public function test_proposal_result_calculation(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active',
            'voting_start_at' => now()->subHour(),
            'voting_end_at' => now()->subMinute(), // 即將結束
            'min_points_to_vote' => '100.00000000'
        ]);

        // 創建多個投票
        $users = User::factory()->count(5)->create([
            'points_balance' => '5000.00000000'
        ]);

        foreach ($users as $index => $user) {
            $position = $index < 3 ? 'for' : 'against';
            $strength = $index + 1;
            $this->governanceService->castVote($proposal, $user, $position, $strength);
        }

        $finalizedProposal = $this->governanceService->finalizeProposal($proposal);

        $this->assertEquals('completed', $finalizedProposal->status);
        $this->assertNotNull($finalizedProposal->result);
        $this->assertGreaterThan(0, $finalizedProposal->vote_count_for);
        $this->assertGreaterThan(0, $finalizedProposal->vote_count_against);
    }

    /**
     * 測試提案審核流程
     */
    public function test_proposal_approval_process(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $proposal = Proposal::factory()->create([
            'status' => 'draft'
        ]);

        $approvedProposal = $this->governanceService->approveProposal($proposal, $admin);

        $this->assertEquals('active', $approvedProposal->status);
        $this->assertEquals($admin->id, $approvedProposal->approved_by);
        $this->assertNotNull($approvedProposal->approved_at);
    }

    /**
     * 測試提案拒絕流程
     */
    public function test_proposal_rejection_process(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $proposal = Proposal::factory()->create([
            'status' => 'draft'
        ]);

        $rejectedProposal = $this->governanceService->rejectProposal(
            $proposal,
            $admin,
            '內容不符合社區規範'
        );

        $this->assertEquals('rejected', $rejectedProposal->status);
        $this->assertEquals($admin->id, $rejectedProposal->approved_by);
        $this->assertStringContainsString('內容不符合', $rejectedProposal->rejection_reason);
    }

    /**
     * 測試治理統計數據
     */
    public function test_governance_statistics(): void
    {
        // 創建測試數據
        Proposal::factory()->count(5)->create(['status' => 'active']);
        Proposal::factory()->count(3)->create(['status' => 'completed']);
        Proposal::factory()->count(2)->create(['status' => 'draft']);

        $stats = $this->governanceService->getGovernanceStats();

        $this->assertArrayHasKey('total_proposals', $stats);
        $this->assertArrayHasKey('active_proposals', $stats);
        $this->assertArrayHasKey('completed_proposals', $stats);
        $this->assertArrayHasKey('total_participants', $stats);
        $this->assertEquals(5, $stats['active_proposals']);
        $this->assertEquals(3, $stats['completed_proposals']);
    }

    /**
     * 測試投票權力分佈
     */
    public function test_voting_power_distribution(): void
    {
        $proposal = Proposal::factory()->create([
            'status' => 'active'
        ]);

        // 創建不同強度的投票
        $users = User::factory()->count(3)->create([
            'points_balance' => '5000.00000000'
        ]);

        $this->governanceService->castVote($proposal, $users[0], 'for', 10);
        $this->governanceService->castVote($proposal, $users[1], 'for', 5);
        $this->governanceService->castVote($proposal, $users[2], 'against', 3);

        $distribution = $this->governanceService->getVotingPowerDistribution($proposal);

        $this->assertArrayHasKey('for_power', $distribution);
        $this->assertArrayHasKey('against_power', $distribution);
        $this->assertArrayHasKey('total_power', $distribution);
        $this->assertEquals(125, $distribution['for_power']); // 10² + 5² = 100 + 25
        $this->assertEquals(9, $distribution['against_power']); // 3² = 9
    }
}