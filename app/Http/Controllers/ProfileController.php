<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    // Profil publik penulis: hanya siswa yang akunnya aktif.
    // Hanya data yang aman dikirim (tanpa e-mail, telepon, atau username).
    public function show(Request $request, User $user)
    {
        abort_unless($user->role === 'user' && $user->is_active, 404);

        $viewer   = $request->user();
        $viewerId = $viewer?->id;

        $posts = Post::active()
            ->where('user_id', $user->id)
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($viewerId)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(Setting::perPage())
            ->through(fn (Post $p) => $p->toCard());

        $activeIds = Post::active()->where('user_id', $user->id)->select('id');

        return Inertia::render('Profiles/Show', [
            'profile' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'bio'         => $user->bio,
                'avatar_url'  => $user->avatar ? '/storage/' . $user->avatar : null,
                'role_label'  => 'Siswa',
                'joined_at'   => $user->created_at?->toIso8601String(),
                'posts_count' => Post::active()->where('user_id', $user->id)->count(),
                'likes_count' => Like::whereIn('post_id', $activeIds)->count(),
                'is_me'       => $viewerId === $user->id,
            ],
            'posts'  => $posts,
            'viewer' => $viewer?->isAdmin() ? 'admin' : ($viewer ? 'user' : 'guest'),
        ]);
    }
}