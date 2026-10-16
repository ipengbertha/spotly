<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    // Daftar komentar terbaru dari semua postingan, dengan pencarian
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Comments/Index', [
            'filters'  => ['q' => $q],
            'comments' => Comment::with(['user:id,name', 'post:id,title,slug,status,expired_at', 'parent.user:id,name'])
                ->withCount('replies')
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('content', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('post', fn ($p) => $p->where('title', 'like', "%{$q}%"));
                }))
                ->latest()
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Comment $c) => [
                    'id'          => $c->id,
                    'content'     => $c->content,
                    'reply_to'    => $c->parent?->user?->name,
                    'replies_count' => $c->replies_count,
                    'author'      => $c->user?->name,
                    'author_id'   => $c->user?->id,
                    'post_title'  => $c->post?->title,
                    'post_slug'   => $c->post?->slug,
                    'post_status' => $c->post?->displayStatus(),
                    'time'        => $c->created_at->locale('id')->diffForHumans(),
                    'date'        => $c->created_at->translatedFormat('d M Y, H:i'),
                ]),
        ]);
    }

    // Admin boleh menghapus komentar apa pun, termasuk di postingan yang sudah tidak tayang
    // (moderasi tidak boleh terhalang status postingan). Balasan di bawahnya ikut terhapus.
    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back();
    }
}