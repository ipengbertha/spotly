<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        // Portofolio: jumlah postingan tayang per kategori (utama + tambahan).
        // Satu postingan dihitung sekali per kategori, walau kategorinya dobel.
        $counts = [];
        Post::active()
            ->where('user_id', $user->id)
            ->with('extraCategories')
            ->get(['id', 'category_id'])
            ->each(function (Post $p) use (&$counts) {
                $ids = collect([$p->category_id])
                    ->merge($p->extraCategories->pluck('id'))
                    ->filter()
                    ->unique();

                foreach ($ids as $id) {
                    $counts[$id] = ($counts[$id] ?? 0) + 1;
                }
            });

        $categories = Category::whereIn('id', array_keys($counts))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'count' => $counts[$c->id],
            ])
            ->values();

        // Filter dari ?kategori=ID. Kategori yang tidak dimiliki penulis diabaikan.
        $selected = (int) $request->query('kategori');
        if (! isset($counts[$selected])) {
            $selected = null;
        }

        $posts = Post::active()
            ->where('user_id', $user->id)
            ->when($selected, fn ($q) => $q->where(function ($w) use ($selected) {
                $w->where('category_id', $selected)
                  ->orWhereHas('extraCategories', fn ($e) => $e->where('categories.id', $selected));
            }))
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($viewerId)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(Setting::perPage())
            ->withQueryString()
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
                'categories_count' => $categories->count(),
                'is_me'       => $viewerId === $user->id,
            ],
            'portfolio' => [
                'categories' => $categories,
                'selected'   => $selected,
            ],
            'posts'  => $posts,
            'viewer' => $viewer?->isAdmin() ? 'admin' : ($viewer ? 'user' : 'guest'),
        ]);
    }
}