<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\ReadingHistory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoryController extends Controller
{
    // Postingan yang pernah dibaca siswa dan MASIH tayang, yang terakhir dibaca di atas
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($userId)
            ->whereHas('readings', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc(
                ReadingHistory::select('last_read_at')
                    ->whereColumn('post_id', 'posts.id')
                    ->where('user_id', $userId)
            )
            ->orderByDesc('posts.id')
            ->paginate(Setting::perPage())
            ->through(fn (Post $p) => $p->toCard());

        return Inertia::render('User/History', ['posts' => $posts]);
    }

    // Hapus seluruh riwayat bacaan milik siswa yang sedang login
    public function destroy(Request $request)
    {
        ReadingHistory::where('user_id', $request->user()->id)->delete();

        return redirect()->route('user.history');
    }
}