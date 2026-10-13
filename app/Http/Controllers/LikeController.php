<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    // Suka / batal suka. Route sudah dibatasi untuk siswa (role:user).
    public function toggle(Request $request, Post $post)
    {
        // Hanya postingan yang sedang tayang. 404 supaya postingan lain tidak ketahuan ada.
        abort_unless($post->isActive(), 404);

        try {
            $like = Like::firstOrCreate([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
            ]);

            // Sudah ada sebelumnya -> ini permintaan "batal suka"
            if (! $like->wasRecentlyCreated) {
                $like->delete();
            }
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: suka sudah tercatat, tidak perlu apa-apa lagi
        }

        return back();
    }
}
