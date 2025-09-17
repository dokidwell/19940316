<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        return view('market.index');
    }

    public function show($id)
    {
        return view('market.show', compact('id'));
    }

    public function purchase(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => '購買成功！']);
    }
}
