<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    // Beri / ganti / cabut reaksi. Route sudah dibatasi untuk siswa (role:user).
    public function store(Request $request, Post $post)
    {
        // Hanya postingan yang sedang tayang (404 supaya postingan lain tidak ketahuan ada)
        abort_unless($post->isActive(), 404);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(PostReaction::OPTIONS))],
        ]);

        $userId = $request->user()->id;

        try {
            DB::transaction(function () use ($post, $userId, $data) {
                $existing = PostReaction::where('post_id', $post->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    PostReaction::create(['post_id' => $post->id, 'user_id' => $userId, 'type' => $data['type']]);
                } elseif ($existing->type === $data['type']) {
                    $existing->delete();                       // emoji yang sama ditekan lagi = cabut
                } else {
                    $existing->update(['type' => $data['type']]); // emoji lain = ganti
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: reaksi sudah tercatat, tidak perlu apa-apa lagi
        }

        return back();
    }
}