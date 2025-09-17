<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index()
    {
        return view('community.index');
    }

    public function proposals()
    {
        return view('community.proposals');
    }

    public function createProposal(Request $request)
    {
        return response()->json(['success' => true, 'message' => '提案已提交！']);
    }

    public function vote(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => '投票成功！']);
    }

    public function transparency()
    {
        return view('community.transparency');
    }
}
