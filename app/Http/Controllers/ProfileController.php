<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index');
    }

    public function checkin(Request $request)
    {
        return response()->json(['success' => true, 'points' => 50, 'message' => '簽到成功！獲得 50 積分']);
    }

    public function updateSettings(Request $request)
    {
        return response()->json(['success' => true, 'message' => '設定已更新']);
    }

    public function pointsHistory()
    {
        return view('profile.points-history');
    }
}
