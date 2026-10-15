<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FeedController extends Controller
{
    private const WINDOW_MONTHS = 3;

    // Pilihan filter tanggal (dalam hari): 7 hari, 30 hari, 3 bulan
    public const PERIOD_OPTIONS = [7, 30, 90];

    public function index(Request $request)
    {
        $request->validate([
            'q'         => ['nullable', 'string', 'max:100'],
            'category'  => ['nullable', 'integer'],
            'spotlight' => ['nullable', 'boolean'],
            'period'    => ['nullable', 'integer', Rule::in(self::PERIOD_OPTIONS)],
        ]);

        $q      = $request->query('q');
        $period = $request->filled('period') ? $request->integer('period') : null;

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($request->user()->id)
            // Filter tanggal: hanya postingan yang tayang dalam N hari terakhir
            // (termasuk yang di-pin; yang tayang lebih lama tidak ikut).
            ->when($period, fn ($query) =>
                $query->where('published_at', '>=', now()->subDays($period)))
            // Tanpa pencarian dan tanpa filter tanggal: hanya 3 bulan terakhir (yang di-pin tetap tampil).
            // Dengan pencarian: semua postingan aktif bisa ditemukan.
            ->when(! $q && ! $period, fn ($query) => $query->where(function ($w) {
                $w->where('is_pinned', true)
                  ->orWhere('published_at', '>=', now()->subMonths(self::WINDOW_MONTHS));
            }))
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            }))
            // Kategori utama ATAU tambahan
            ->when($request->filled('category'), function ($query) use ($request) {
                $id = $request->integer('category');
                $query->where(function ($w) use ($id) {
                    $w->where('category_id', $id)
                      ->orWhereHas('extraCategories', fn ($c) => $c->where('categories.id', $id));
                });
            })
            ->when($request->boolean('spotlight'), fn ($query) =>
                $query->where('is_pinned', true))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(Setting::perPage())
            ->withQueryString()
            ->through(fn (Post $p) => $p->toCard());

        return Inertia::render('User/Beranda', [
            'posts'      => $posts,
            'filters'    => [
                'q'         => $q,
                'category'  => $request->query('category'),
                'spotlight' => $request->boolean('spotlight'),
                'period'    => $period,
            ],
            'isAdmin'    => $request->user()->isAdmin(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }
}