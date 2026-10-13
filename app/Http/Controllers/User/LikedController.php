<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LikedController extends Controller
{
    // Postingan yang disukai siswa dan MASIH tayang, yang terbaru disukai di atas
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name', 'extraCategories'])
            ->withLikes($userId)
            ->whereHas('likes', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc(
                Like::select('created_at')
                    ->whereColumn('post_id', 'posts.id')
                    ->where('user_id', $userId)
            )
            ->orderByDesc('posts.id')
            ->paginate(Setting::perPage())
            ->through(fn (Post $p) => $p->toCard());

        return Inertia::render('User/Liked', ['posts' => $posts]);
    }
}
