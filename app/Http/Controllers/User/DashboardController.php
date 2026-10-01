<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('User/Dashboard', [
            'counts' => [
                'total'     => $user->posts()->count(),
                'submitted' => $user->posts()->where('status', Post::SUBMITTED)->count(),
                'published' => $user->posts()->active()->count(),
                'rejected'  => $user->posts()->where('status', Post::REJECTED)->count(),
                'draft'     => $user->posts()->where('status', Post::DRAFT)->count(),
                'archived'  => $user->posts()->archive()->count(),
            ],
            'attention' => $user->posts()
                ->where('status', Post::REJECTED)
                ->latest('updated_at')->limit(5)
                ->get(['id', 'title', 'rejection_reason']),
            'expiring' => $user->posts()->active()
                ->whereNotNull('expired_at')
                ->where('expired_at', '<=', now()->addDays(3))
                ->orderBy('expired_at')
                ->get()
                ->map(fn (Post $p) => [
                    'id'         => $p->id,
                    'title'      => $p->title,
                    'expired_at' => $p->expired_at->translatedFormat('d M Y'),
                ]),
            'recent' => $user->posts()->with('category:id,name')
                ->latest()->limit(5)->get()
                ->map(fn (Post $p) => [
                    'id'             => $p->id,
                    'title'          => $p->title,
                    'category'       => $p->category->name,
                    'display_status' => $p->displayStatus(),
                ]),
        ]);
    }
}