<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'submitted' => Post::where('status', Post::SUBMITTED)->count(),
                'active'    => Post::active()->count(),
                'archive'   => Post::archive()->count(),
                'rejected'  => Post::where('status', Post::REJECTED)->count(),
                'users'     => User::where('role', 'user')->count(),
            ],
            'queue' => Post::with(['user:id,name', 'category:id,name'])
                ->where('status', Post::SUBMITTED)
                ->oldest('updated_at')
                ->limit(5)
                ->get(['id', 'user_id', 'category_id', 'title', 'status', 'updated_at']),
        ]);
    }
}