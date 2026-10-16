<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostShowController extends Controller
{
    public function __invoke(Request $request, Post $post): Response
    {
        $viewer  = $request->user();
        $isAdmin = $viewer?->role === 'admin';
        $isOwner = $viewer !== null && $viewer->id === $post->user_id;

        // Postingan yang sedang tayang boleh dibaca siapa saja.
        // Selain itu (draft, review, ditolak, arsip, kedaluwarsa) hanya pemilik dan admin.
        // Memakai 404 (bukan 403) supaya orang lain tidak tahu postingan itu ada.
        abort_unless($post->isActive() || $isAdmin || $isOwner, 404);

        $post->load(['user:id,name,avatar', 'category', 'extraCategories'])->loadCount('likes');
        $canSeePrivate = $isAdmin || $isOwner;
        $isStudent     = $viewer?->role === 'user';
        $reported      = $viewer !== null
            && $post->reports()->where('user_id', $viewer->id)->exists();

        // Komentar hanya dibuka untuk postingan yang sedang tayang.
        // Bentuknya pohon: komentar utama (terbaru di atas), masing-masing membawa balasannya
        // (terlama di atas supaya percakapan terbaca urut). Balasan bisa bertingkat tanpa batas;
        // tampilan yang membatasi jorokannya.
        // Hapus: siswa hanya miliknya sendiri, admin semuanya (lewat route masing-masing).
        // Menghapus komentar ikut menghapus semua balasan di bawahnya.
        $comments = collect();
        if ($post->isActive()) {
            $byParent = $post->comments()->with('user:id,name,username,avatar')
                ->orderBy('created_at')->orderBy('id')->get()
                ->groupBy(fn (Comment $c) => $c->parent_id ?? 0);

            $build = function (int $parentId, ?string $parentName, ?string $parentUsername, bool $newestFirst) use (&$build, $byParent, $viewer, $isAdmin, $isStudent) {
                $list = $byParent->get($parentId, collect());
                if ($newestFirst) {
                    $list = $list->reverse();
                }

                return $list->map(function (Comment $c) use (&$build, $parentName, $parentUsername, $viewer, $isAdmin, $isStudent) {
                    $replies = $build($c->id, $c->user?->name, $c->user?->username, false);

                    return [
                        'id'            => $c->id,
                        'content'       => $c->content,
                        'author'        => $c->user?->name,
                        'author_id'     => $c->user?->id,
                        'author_username' => $c->user?->username,
                        'author_avatar' => $c->user?->avatar ? '/storage/' . $c->user->avatar : null,
                        'created_at'    => $c->created_at->toIso8601String(),
                        'reply_to'      => $parentName,
                        'reply_to_username' => $parentUsername,
                        'replies'       => $replies,
                        // jumlah semua balasan di bawahnya (termasuk balasan dari balasan)
                        'replies_total' => $replies->sum(fn ($r) => 1 + $r['replies_total']),
                        'delete_url'    => match (true) {
                            $isAdmin                                  => "/admin/comments/{$c->id}",
                            $isStudent && $c->user_id === $viewer->id => "/comments/{$c->id}",
                            default                                   => null,
                        },
                    ];
                })->values();
            };

            $comments = $build(0, null, null, true);
        }

        $reactionCounts = $post->reactions()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Posts/Show', [
            'post' => [
                'id'               => $post->id,
                'title'            => $post->title,
                'content'          => $post->content,
                'reading_minutes'  => $post->readingMinutes(),
                'image_url'        => $post->image ? '/storage/' . $post->image : null,
                'categories'       => $post->categoryNames(),
                'author'           => $post->user?->name,
                'author_id'        => $post->user?->id,
                'author_avatar'    => $post->user?->avatar ? '/storage/' . $post->user->avatar : null,
                'published_at'     => $post->published_at?->toIso8601String(),
                'status'           => $post->displayStatus(),
                'is_active'        => $post->isActive(),
                'likes_count'      => $post->likes_count,
                'liked'            => $viewer !== null
                    && $post->likes()->where('user_id', $viewer->id)->exists(),
                // Hanya siswa yang bisa like, dan hanya untuk postingan yang sedang tayang
                'can_like'         => $viewer?->role === 'user' && $post->isActive(),
                'reactions'        => collect(PostReaction::OPTIONS)->map(fn ($emoji, $type) => [
                    'type'  => $type,
                    'emoji' => $emoji,
                    'count' => (int) ($reactionCounts[$type] ?? 0),
                ])->values(),
                'my_reaction'      => $isStudent
                    ? $post->reactions()->where('user_id', $viewer->id)->value('type')
                    : null,
                // Simpan artikel: hanya siswa, dan hanya untuk postingan yang sedang tayang
                'can_bookmark'     => $isStudent && $post->isActive(),
                'bookmarked'       => $isStudent
                    && $post->bookmarks()->where('user_id', $viewer->id)->exists(),
                // Hanya siswa yang bisa menulis komentar, dan hanya di postingan yang sedang tayang
                'can_comment'      => $isStudent && $post->isActive() && $post->comments_enabled,
                // Komentar dimatikan penulis: komentar lama tetap tampil, komentar baru ditutup
                'comments_enabled' => (bool) $post->comments_enabled,
                // Hanya penulis (siswa) yang melihat saklarnya
                'can_toggle_comments' => $isStudent && $isOwner && $post->isActive(),
                'comments_count'   => $post->comments()->count(),
                'comments'         => $comments,
                'rejection_reason' => $canSeePrivate ? $post->rejection_reason : null,
                'suspension_reason' => $canSeePrivate ? $post->suspension_reason : null,
                // Siswa bisa melaporkan postingan tayang milik orang lain, sekali saja
                'can_report'       => $isStudent && ! $isOwner && $post->isActive() && ! $reported,
                'reported'         => $reported,
                'report_reasons'   => Report::reasonOptions(),
                'edit_url'         => ($isOwner && $post->isEditableByOwner())
                    ? "/user/posts/{$post->id}/edit"
                    : null,
            ],
            'viewer' => $isAdmin ? 'admin' : ($viewer ? 'user' : 'guest'),
        ]);
    }
}