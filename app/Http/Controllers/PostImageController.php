<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ]);

        $path = $request->file('image')->store('post-images', 'public');

        // Alamat relatif, supaya tidak bergantung pada APP_URL
        return response()->json(['url' => '/storage/' . $path]);
    }
}