<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Reviews/Index', [
            'posts' => Post::with(['user:id,name', 'category:id,name'])
                ->where('status', Post::SUBMITTED)
                ->oldest('updated_at')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }
}