<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\CommentReport;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\ReadingHistory;
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

        // Catat riwayat bacaan: hanya siswa, hanya postingan yang sedang tayang,
        // dan bukan artikel miliknya sendiri. Membuka lagi hanya memperbarui waktunya.
        if ($isStudent && ! $isOwner && $post->isActive()) {
            ReadingHistory::updateOrCreate(
                ['user_id' => $viewer->id, 'post_id' => $post->id],
                ['last_read_at' => now()],
            );
        }

        // Komentar hanya dibuka untuk postingan yang sedang tayang.
        // Bentuknya pohon: komentar utama (terbaru di atas), masing-masing membawa balasannya
        // (terlama di atas supaya percakapan terbaca urut). Balasan bisa bertingkat tanpa batas;
        // tampilan yang membatasi jorokannya.
        // Hapus: siswa hanya miliknya sendiri, admin semuanya (lewat route masing-masing).
        // Menghapus komentar ikut menghapus semua balasan di bawahnya.
        $comments = collect();
        if ($post->isActive()) {
            $all = $post->comments()->with('user:id,name,username,avatar')->withCount('likes')
                ->orderBy('created_at')->orderBy('id')->get();
            $byParent = $all->groupBy(fn (Comment $c) => $c->parent_id ?? 0);

            // Komentar yang sudah diberi jempol / dilaporkan oleh siswa yang sedang membuka halaman
            $likedIds = $reportedIds = [];
            if ($isStudent) {
                $likedIds = CommentLike::where('user_id', $viewer->id)->whereIn('comment_id', $all->pluck('id'))
                    ->pluck('comment_id')->flip()->all();
                $reportedIds = CommentReport::where('user_id', $viewer->id)->whereIn('comment_id', $all->pluck('id'))
                    ->pluck('comment_id')->flip()->all();
            }

            $build = function (int $parentId, ?string $parentName, ?string $parentUsername, bool $newestFirst) use (&$build, $byParent, $viewer, $isAdmin, $isStudent, $likedIds, $reportedIds) {
                $list = $byParent->get($parentId, collect());
                if ($newestFirst) {
                    $list = $list->reverse();
                }

                return $list->map(function (Comment $c) use (&$build, $parentName, $parentUsername, $viewer, $isAdmin, $isStudent, $likedIds, $reportedIds) {
                    $isMine = $isStudent && $c->user_id === $viewer->id;

                    $replies = $build($c->id, $c->user?->name, $c->user?->username, false);

                    return [
                        'id'            => $c->id,
                        'content'       => $c->content,
                        'author'        => $c->user?->name,
                        'author_id'     => $c->user?->id,
                        'author_username' => $c->user?->username,
                        'author_avatar' => $c->user?->avatar ? '/storage/' . $c->user->avatar : null,
                        'created_at'    => $c->created_at->toIso8601String(),
                        // Komentar yang isinya pernah diubah penulisnya diberi tanda "(diedit)"
                        'edited'        => $c->edited_at !== null,
                        'is_mine'       => $isMine,
                        // Edit hanya untuk komentar sendiri dan hanya selama belum lewat 24 jam
                        'can_edit'      => $isMine && $c->isEditable(),
                        'likes_count'   => $c->likes_count,
                        'liked'         => isset($likedIds[$c->id]),
                        // Jempol dan laporan hanya untuk komentar orang lain
                        'can_like'      => $isStudent && ! $isMine,
                        'can_report'    => $isStudent && ! $isMine && ! isset($reportedIds[$c->id]),
                        'reported'      => isset($reportedIds[$c->id]),
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

        // Artikel terkait: postingan tayang lain yang berbagi minimal satu kategori
        // (utama atau tambahan), terbaru di atas. Hanya dihitung untuk postingan yang tayang.
        $related = collect();
        if ($post->isActive()) {
            $categoryIds = $post->extraCategories->pluck('id')->push($post->category_id)->unique()->values();

            $related = Post::active()
                ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
                ->withLikes($viewer?->id)
                ->where('posts.id', '!=', $post->id)
                ->where(function ($q) use ($categoryIds) {
                    $q->whereIn('posts.category_id', $categoryIds)
                      ->orWhereHas('extraCategories', fn ($c) => $c->whereIn('categories.id', $categoryIds));
                })
                ->orderByDesc('posts.published_at')
                ->orderByDesc('posts.id')
                ->limit(5)
                ->get()
                ->map(fn (Post $p) => $p->toCard())
                ->values();
        }

        $reactionCounts = $post->reactions()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Posts/Show', [
            'post' => [
                'id'               => $post->id,
                'title'            => $post->title,
                'content_html'     => $post->contentHtml(),
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
            'related' => $related,
            'viewer' => $isAdmin ? 'admin' : ($viewer ? 'user' : 'guest'),
        ]);
    }
}