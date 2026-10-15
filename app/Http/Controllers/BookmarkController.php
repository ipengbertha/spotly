<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    // Simpan / batal simpan artikel. Route sudah dibatasi untuk siswa (role:user).
    public function toggle(Request $request, Post $post)
    {
        // Hanya postingan yang sedang tayang. 404 supaya postingan lain tidak ketahuan ada.
        abort_unless($post->isActive(), 404);

        try {
            $bookmark = Bookmark::firstOrCreate([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
            ]);

            // Sudah ada sebelumnya -> ini permintaan "batal simpan"
            if (! $bookmark->wasRecentlyCreated) {
                $bookmark->delete();
            }
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: sudah tersimpan, tidak perlu apa-apa lagi
        }

        return back();
    }
}
