<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\CommentReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
            'content' => ['required', 'string', 'min:2', 'max:500'],
        ], [
            'content.required' => 'Komentar tidak boleh kosong.',
            'content.min'      => 'Komentar minimal 2 karakter.',
            'content.max'      => 'Komentar maksimal 500 karakter.',
        ]);

        RateLimiter::hit($key, 60);

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $data['content'],
        ]);

        // Penulis postingan diberi tahu, kecuali kalau dia mengomentari postingannya sendiri
        // atau sudah mematikan notifikasi komentar di Pengaturan.
        $author = $post->user;
        if ($author && $author->id !== $user->id && $author->wantsNotification('comment_received')) {
            $author->notify(new CommentReceived($post, $comment->setRelation('user', $user)));
        }

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
