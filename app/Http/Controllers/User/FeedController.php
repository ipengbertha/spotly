<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Poll;
use App\Models\ContributorAward;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FeedController extends Controller
{
    private const WINDOW_MONTHS = 3;

    // Pilihan filter tanggal: 7 hari, 30 hari, bulan ini, tahun ajaran ini
    public const PERIOD_OPTIONS = ['7', '30', '90', 'month', 'school_year'];

    /** Tanggal paling awal yang masih dihitung untuk pilihan filter tertentu. */
    public static function periodStart(string $period): Carbon
    {
        $now = now();

        return match ($period) {
            '7', '30', '90' => $now->copy()->subDays((int) $period),
            'month'       => $now->copy()->startOfMonth(),
            // Tahun ajaran: 1 Juli sampai 30 Juni
            'school_year' => Carbon::create($now->month >= 7 ? $now->year : $now->year - 1, 7, 1)->startOfDay(),
        };
    }

    public function index(Request $request)
    {
        $request->validate([
            'q'         => ['nullable', 'string', 'max:100'],
            'category'  => ['nullable', 'integer'],
            'spotlight' => ['nullable', 'boolean'],
            'period'    => ['nullable', 'string', Rule::in(self::PERIOD_OPTIONS)],
        ]);

        $q      = $request->query('q');
        $period = $request->filled('period') ? (string) $request->query('period') : null;

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($request->user()->id)
            // Filter tanggal: hanya postingan yang mulai tayang sejak batas awal rentang
            ->when($period, fn ($query) =>
                $query->where('published_at', '>=', self::periodStart($period)))
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

        $poll = Poll::open()->with('options')->latest('id')->first();

        return Inertia::render('User/Beranda', [
            'posts'      => $posts,
            'filters'    => [
                'q'         => $q,
                'category'  => $request->query('category'),
                'spotlight' => $request->boolean('spotlight'),
                'period'    => ($period !== null && ctype_digit($period)) ? (int) $period : $period,
            ],
            'isAdmin'    => $request->user()->isAdmin(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'poll'       => $poll?->forViewer($request->user()),
            'contributors' => ContributorAward::latestForFeed(),
            'bestPost'   => $this->bestPost($request->user()->id),
        ]);
    }

    private function bestPost(int $userId): ?array
    {
        $post = Post::active()
            ->with(['category:id,name', 'user:id,name,avatar', 'extraCategories'])
            ->withLikes($userId)
            ->withCount('views')
            ->where('posts.published_at', '>=', now()->startOfMonth())
            ->orderByDesc('likes_count')
            ->orderByDesc('views_count')
            ->orderByDesc('posts.published_at')
            ->orderByDesc('posts.id')
            ->first();

        return ($post && $post->likes_count > 0) ? $post->toCard() : null;
    }
}