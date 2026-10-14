<?php

namespace App\Http\Controllers;

use App\Models\Post;
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

        return Inertia::render('Posts/Show', [
            'post' => [
                'id'               => $post->id,
                'title'            => $post->title,
                'content'          => $post->content,
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