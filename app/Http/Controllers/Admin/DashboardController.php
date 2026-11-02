<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'submitted' => Post::where('status', Post::SUBMITTED)->count(),
                'active'    => Post::active()->count(),
                'scheduled' => Post::scheduled()->count(),
                'archive'   => Post::archive()->count(),
                'rejected'  => Post::where('status', Post::REJECTED)->count(),
                'users'     => User::where('role', 'user')->count(),
            ],
            'queue' => Post::with(['user:id,name', 'category:id,name'])
                ->where('status', Post::SUBMITTED)
                ->oldest('updated_at')
                ->limit(5)
                ->get(['id', 'user_id', 'category_id', 'title', 'status', 'updated_at']),
            'weekly'     => $this->weekly(),
            'topPosts'   => $this->topPosts(),
            'categories' => $this->categories(),
            'activity'   => ActivityLog::with('admin:id,name')
                ->latest()
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (ActivityLog $l) => $l->toRow())
                ->values(),
        ]);
    }

    // Jumlah postingan yang mulai tayang di tiap hari, 7 hari terakhir (termasuk hari ini)
    private function weekly(): array
    {
        $start = now()->subDays(6)->startOfDay();

        $counts = Post::whereIn('status', [Post::PUBLISHED, Post::ARCHIVED])
            ->whereBetween('published_at', [$start, now()])
            ->get(['id', 'published_at'])
            ->countBy(fn (Post $p) => $p->published_at->toDateString());

        return collect(range(0, 6))
            ->map(function ($i) use ($start, $counts) {
                $date = $start->copy()->addDays($i)->toDateString();

                return ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
            })
            ->all();
    }

    // Postingan yang sedang tayang dengan suka terbanyak (lalu komentar). Yang belum ada interaksi tidak ikut.
    private function topPosts(): array
    {
        return Post::active()
            ->withCount(['likes', 'comments'])
            ->with('user:id,name')
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->limit(5)
            ->get()
            ->filter(fn (Post $p) => $p->likes_count + $p->comments_count > 0)
            ->map(fn (Post $p) => [
                'id'       => $p->id,
                'slug'     => $p->slug,
                'title'    => $p->title,
                'author'   => $p->user?->name,
                'likes'    => (int) $p->likes_count,
                'comments' => (int) $p->comments_count,
            ])
            ->values()
            ->all();
    }

    // Kategori utama dengan postingan tayang terbanyak
    private function categories(): array
    {
        $rows = Post::active()
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $names = Category::whereIn('id', $rows->pluck('category_id'))->pluck('name', 'id');

        return $rows
            ->map(fn ($r) => [
                'name'  => $names[$r->category_id] ?? 'Tanpa kategori',
                'count' => (int) $r->total,
            ])
            ->values()
            ->all();
    }
}