<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\CommentReceived;
use App\Notifications\ReplyReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    // Batas kirim: 5 komentar per menit untuk tiap siswa
    private const MAX_PER_MINUTE = 5;

    // Siswa menulis komentar. Route sudah dibatasi untuk siswa (role:user).
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        // Hanya postingan yang sedang tayang. 404 supaya postingan lain tidak ketahuan ada.
        abort_unless($post->isActive(), 404);

        // Penulis mematikan komentar: tidak ada yang bisa menulis. Dicek sebelum batas kirim
        // supaya permintaan yang ditolak tidak ikut menghabiskan jatah 5 per menit.
        abort_unless($post->comments_enabled, 403, 'Penulis mematikan komentar di postingan ini.');

        // Batas kirim dicek lewat controller (bukan middleware throttle) supaya pesannya
        // tampil rapi di bawah kotak komentar, bukan halaman error 429.
        $key = "comment:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_MINUTE)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'content' => "Kamu mengirim komentar terlalu cepat. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $data = $request->validate([
            'content'   => ['required', 'string', 'min:2', 'max:500'],
            // Balasan: komentar yang dibalas harus ada di postingan yang sama
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('post_id', $post->id)],
        ], [
            'parent_id.exists' => 'Komentar yang kamu balas sudah tidak ada.',
            'content.required' => 'Komentar tidak boleh kosong.',
            'content.min'      => 'Komentar minimal 2 karakter.',
            'content.max'      => 'Komentar maksimal 500 karakter.',
        ]);

        RateLimiter::hit($key, 60);

        $comment = $post->comments()->create([
            'user_id'   => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'content'   => $data['content'],
        ]);
        $comment->setRelation('user', $user);

        // Penulis komentar yang dibalas diberi tahu (kecuali membalas komentarnya sendiri
        // atau dia mematikan notifikasi komentar di Pengaturan).
        $parentAuthor = $comment->parent_id ? Comment::find($comment->parent_id)?->user : null;
        $repliedUserId = null;
        if ($parentAuthor && $parentAuthor->id !== $user->id && $parentAuthor->wantsNotification('comment_received')) {
            $parentAuthor->notify(new ReplyReceived($post, $comment));
            $repliedUserId = $parentAuthor->id;
        }

        // Penulis postingan diberi tahu, kecuali kalau dia menulis di postingannya sendiri,
        // sudah mematikan notifikasi komentar, atau sudah diberi tahu lewat balasan di atas.
        $author = $post->user;
        if ($author && $author->id !== $user->id && $author->id !== $repliedUserId
            && $author->wantsNotification('comment_received')) {
            $author->notify(new CommentReceived($post, $comment));
        }

        return back();
    }

    // Penulis postingan mematikan atau menyalakan komentar. Route sudah dibatasi untuk siswa (role:user).
    // Komentar yang sudah ada tetap tampil dan tetap bisa dihapus; hanya komentar baru yang ditutup.
    public function updateSetting(Request $request, Post $post)
    {
        // Postingan yang tidak tayang menjawab 404 untuk semua aksi komentar
        abort_unless($post->isActive(), 404);
        abort_unless($post->user_id === $request->user()->id, 403);

        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        $post->setCommentsEnabled((bool) $data['enabled']);

        return back();
    }

    // Siswa menghapus komentarnya sendiri. (Admin memakai Admin\CommentController.)
    public function destroy(Request $request, Comment $comment)
    {
        // Postingan yang tidak tayang menjawab 404 untuk semua aksi komentar
        abort_unless($comment->post->isActive(), 404);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back();
    }
}