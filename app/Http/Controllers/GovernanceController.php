<?php

namespace App\Http\Controllers;

use App\Services\GovernanceService;
use App\Models\Proposal;
use App\Models\ProposalVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GovernanceController extends Controller
{
    protected $governanceService;

    public function __construct(GovernanceService $governanceService)
    {
        $this->governanceService = $governanceService;
        $this->middleware('auth');
    }

    public function index()
    {
        $activeProposals = $this->governanceService->getActiveProposals();
        $proposalHistory = $this->governanceService->getProposalHistory(10);
        $stats = $this->governanceService->getGovernanceStats();
        $user = Auth::user();
        $canCreateProposal = $this->governanceService->canUserCreateProposal($user);

        return view('governance.index', compact(
            'activeProposals',
            'proposalHistory',
            'stats',
            'canCreateProposal'
        ));
    }

    public function proposals()
    {
        $activeProposals = $this->governanceService->getActiveProposals(50);
        $user = Auth::user();

        return view('governance.proposals', compact('activeProposals', 'user'));
    }

    public function proposalHistory()
    {
        $proposalHistory = $this->governanceService->getProposalHistory(100);

        return view('governance.history', compact('proposalHistory'));
    }

    public function createProposal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'required|string|in:platform_improvement,feature_request,community_rule,economic_policy,governance_change,other',
            'voting_start_at' => 'sometimes|date|after:now',
            'voting_end_at' => 'sometimes|date|after:voting_start_at',
            'min_points_to_vote' => 'sometimes|numeric|min:0.00000001|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = Auth::user();

            $proposalData = [
                'title' => $request->get('title'),
                'description' => $request->get('description'),
                'category' => $request->get('category'),
                'voting_start_at' => $request->get('voting_start_at') ?
                    \Carbon\Carbon::parse($request->get('voting_start_at')) :
                    now()->addHours(1),
                'voting_end_at' => $request->get('voting_end_at') ?
                    \Carbon\Carbon::parse($request->get('voting_end_at')) :
                    now()->addDays(7),
                'min_points_to_vote' => $request->get('min_points_to_vote', 1.00000000),
                'metadata' => [
                    'creator_ip' => $request->ip(),
                    'creator_user_agent' => $request->userAgent(),
                ],
            ];

            $proposal = $this->governanceService->createProposal($user, $proposalData);

            return response()->json([
                'success' => true,
                'message' => '提案创建成功，等待管理员审核',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'title' => $proposal->title,
                    'status' => $proposal->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showProposal($id)
    {
        try {
            $proposal = Proposal::with(['creator', 'votes.user'])->findOrFail($id);
            $user = Auth::user();

            $canVote = null;
            $maxVoteStrength = 0;
            $votingCosts = [];

            if ($user) {
                $canVote = $this->governanceService->canUserVoteOnProposal($proposal, $user);
                $maxVoteStrength = $this->governanceService->getMaxVoteStrengthForUser($user);

                // 计算不同投票强度的成本
                for ($i = 1; $i <= min($maxVoteStrength, 10); $i++) {
                    $votingCosts[$i] = $this->governanceService->calculateVotingCost($i);
                }
            }

            $votingDistribution = $this->governanceService->getVotingPowerDistribution($proposal);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'proposal' => $proposal,
                        'can_vote' => $canVote,
                        'max_vote_strength' => $maxVoteStrength,
                        'voting_costs' => $votingCosts,
                        'voting_distribution' => $votingDistribution,
                    ]
                ]);
            }

            return view('governance.proposal', compact(
                'proposal',
                'canVote',
                'maxVoteStrength',
                'votingCosts',
                'votingDistribution'
            ));

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 404);
            }

            abort(404);
        }
    }

    public function vote(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'position' => 'required|string|in:for,against,abstain',
            'vote_strength' => 'required|integer|min:1|max:100',
            'justification' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $proposal = Proposal::findOrFail($id);
            $user = Auth::user();

            $position = $request->get('position');
            $voteStrength = $request->get('vote_strength');
            $justification = $request->get('justification', '');

            $vote = $this->governanceService->castVote($proposal, $user, $position, $voteStrength, $justification);

            return response()->json([
                'success' => true,
                'message' => '投票成功',
                'data' => [
                    'vote_id' => $vote->id,
                    'position' => $vote->position,
                    'vote_strength' => $vote->vote_strength,
                    'points_cost' => $vote->points_cost,
                    'user_balance' => $user->fresh()->points_balance,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approveProposal(Request $request, $id)
    {
        try {
            // 只允许管理员操作
            $user = Auth::user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $proposal = Proposal::findOrFail($id);
            $approvedProposal = $this->governanceService->approveProposal($proposal, $user);

            return response()->json([
                'success' => true,
                'message' => '提案审核通过',
                'data' => [
                    'proposal_id' => $approvedProposal->id,
                    'status' => $approvedProposal->status,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectProposal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // 只允许管理员操作
            $user = Auth::user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $proposal = Proposal::findOrFail($id);
            $reason = $request->get('reason');

            $rejectedProposal = $this->governanceService->rejectProposal($proposal, $user, $reason);

            return response()->json([
                'success' => true,
                'message' => '提案已拒绝',
                'data' => [
                    'proposal_id' => $rejectedProposal->id,
                    'status' => $rejectedProposal->status,
                    'reason' => $reason,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function finalizeProposal($id)
    {
        try {
            // 只允许管理员或系统自动触发
            $user = Auth::user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $proposal = Proposal::findOrFail($id);
            $finalizedProposal = $this->governanceService->finalizeProposal($proposal);

            return response()->json([
                'success' => true,
                'message' => '提案投票已结束',
                'data' => [
                    'proposal_id' => $finalizedProposal->id,
                    'result' => $finalizedProposal->result,
                    'vote_counts' => [
                        'for' => $finalizedProposal->vote_count_for,
                        'against' => $finalizedProposal->vote_count_against,
                        'abstain' => $finalizedProposal->vote_count_abstain,
                    ],
                    'total_points_spent' => $finalizedProposal->total_points_spent,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserVotingHistory()
    {
        try {
            $user = Auth::user();
            $votingHistory = $this->governanceService->getUserVotingHistory($user);

            return response()->json([
                'success' => true,
                'data' => $votingHistory
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getGovernanceStats()
    {
        try {
            $stats = $this->governanceService->getGovernanceStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkProposalPermission()
    {
        try {
            $user = Auth::user();
            $permission = $this->governanceService->canUserCreateProposal($user);

            return response()->json([
                'success' => true,
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkVotingPermission($id)
    {
        try {
            $proposal = Proposal::findOrFail($id);
            $user = Auth::user();

            $permission = $this->governanceService->canUserVoteOnProposal($proposal, $user);
            $maxVoteStrength = $this->governanceService->getMaxVoteStrengthForUser($user);

            return response()->json([
                'success' => true,
                'data' => array_merge($permission, [
                    'max_vote_strength' => $maxVoteStrength,
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function calculateVotingCost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vote_strength' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $voteStrength = $request->get('vote_strength');
            $cost = $this->governanceService->calculateVotingCost($voteStrength);

            return response()->json([
                'success' => true,
                'data' => [
                    'vote_strength' => $voteStrength,
                    'points_cost' => $cost,
                    'formatted_cost' => number_format($cost, 8, '.', ''),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getVotingDistribution($id)
    {
        try {
            $proposal = Proposal::findOrFail($id);
            $distribution = $this->governanceService->getVotingPowerDistribution($proposal);

            return response()->json([
                'success' => true,
                'data' => $distribution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function searchProposals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:255',
            'category' => 'sometimes|string',
            'status' => 'sometimes|string',
            'creator_id' => 'sometimes|integer',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '请求参数错误',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $query = Proposal::with(['creator', 'votes']);

            if ($request->has('q')) {
                $searchTerm = $request->get('q');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->has('category')) {
                $query->where('category', $request->get('category'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('creator_id')) {
                $query->where('creator_id', $request->get('creator_id'));
            }

            $limit = $request->get('limit', 20);
            $proposals = $query->orderBy('created_at', 'desc')->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'proposals' => $proposals,
                    'total' => $proposals->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingProposals()
    {
        try {
            // 只允许管理员查看
            $user = Auth::user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => '权限不足'
                ], 403);
            }

            $pendingProposals = Proposal::where('status', Proposal::STATUS_DRAFT)
                ->with(['creator'])
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pendingProposals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}