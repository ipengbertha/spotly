<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FeedController extends Controller
{
    private const PER_PAGE = 12;
    private const WINDOW_MONTHS = 3;

    public function index(Request $request)
    {
        $request->validate([
            'q'         => ['nullable', 'string', 'max:100'],
            'category'  => ['nullable', 'integer'],
            'spotlight' => ['nullable', 'boolean'],
        ]);

        $q = $request->query('q');

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name'])
            // Tanpa pencarian: hanya 3 bulan terakhir (yang di-pin tetap tampil).
            // Dengan pencarian: semua postingan aktif bisa ditemukan.
            ->when(! $q, fn ($query) => $query->where(function ($w) {
                $w->where('is_pinned', true)
                  ->orWhere('published_at', '>=', now()->subMonths(self::WINDOW_MONTHS));
            }))
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            }))
            ->when($request->filled('category'), fn ($query) =>
                $query->where('category_id', $request->integer('category')))
            ->when($request->boolean('spotlight'), fn ($query) =>
                $query->where('is_pinned', true))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Post $p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'excerpt'      => Str::limit(strip_tags($p->content), 110),
                'image_url'    => $p->image ? Storage::url($p->image) : null,
                'category'     => $p->category->name,
                'author'       => $p->user->name,
                'is_pinned'    => $p->is_pinned,
                'published_at' => $p->published_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('User/Beranda', [
            'posts'   => $posts,
            'filters' => [
                'q'         => $q,
                'category'  => $request->query('category'),
                'spotlight' => $request->boolean('spotlight'),
            ],
        ]);
    }
}