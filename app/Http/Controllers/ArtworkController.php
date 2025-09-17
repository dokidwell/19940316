<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ArtworkController extends Controller
{
    public function index()
    {
        return view('artworks.index');
    }

    public function show($id)
    {
        return view('artworks.show', compact('id'));
    }
}
