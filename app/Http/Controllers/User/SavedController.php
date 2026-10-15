<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SavedController extends Controller
{
    // Postingan yang disimpan siswa dan MASIH tayang, yang terbaru disimpan di atas
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($userId)
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc(
                Bookmark::select('created_at')
                    ->whereColumn('post_id', 'posts.id')
                    ->where('user_id', $userId)
            )
            ->orderByDesc('posts.id')
            ->paginate(Setting::perPage())
            ->through(fn (Post $p) => $p->toCard());

        return Inertia::render('User/Saved', ['posts' => $posts]);
    }
}
