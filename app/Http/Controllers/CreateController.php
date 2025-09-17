<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CreateController extends Controller
{
    public function index()
    {
        return view('create.index');
    }

    public function upload(Request $request)
    {
        // Handle file upload
        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        // Store artwork
        return redirect()->route('artworks.index')->with('success', '作品已成功發布！');
    }
}
