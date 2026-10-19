<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    // Beri / cabut jempol di komentar. Route sudah dibatasi untuk siswa (role:user).
    public function toggle(Request $request, Comment $comment)
    {
        // Postingan yang tidak tayang menjawab 404 untuk semua aksi komentar
        abort_unless($comment->post->isActive(), 404);
        abort_if($comment->user_id === $request->user()->id, 403, 'Kamu tidak bisa menyukai komentarmu sendiri.');

        try {
            $like = CommentLike::firstOrCreate([
                'comment_id' => $comment->id,
                'user_id'    => $request->user()->id,
            ]);

            // Sudah ada sebelumnya -> ini permintaan "cabut jempol"
            if (! $like->wasRecentlyCreated) {
                $like->delete();
            }
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: jempol sudah tercatat, tidak perlu apa-apa lagi
        }

        return back();
    }
}
