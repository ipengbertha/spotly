<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Peringkat kontributor satu bulan.
 *
 * Aturan:
 * 1. Jumlah karya terbit di bulan itu (published_at), status tayang atau arsip.
 * 2. Seri: total suka + komentar pada karya bulan itu.
 * 3. Masih seri: yang karya pertamanya terbit lebih dulu menang.
 * 4. Terakhir, ID pengguna, supaya urutan selalu pasti.
 * Minimal MIN_POSTS karya agar boleh diumumkan.
 */
class ContributorRanking
{
    // PERIKSA: sesuaikan dengan nilai kolom `status` di tabel posts milikmu.
    public const COUNTED_STATUSES = ['published', 'archived'];

    public const MIN_POSTS = 2;
    public const MAX_ANNOUNCED = 5;

    /** @return Collection<int, array> semua siswa yang punya karya di bulan itu, sudah berurutan */
    public static function forMonth(Carbon $month): Collection
    {
        $posts = Post::query()
            ->whereIn('status', self::COUNTED_STATUSES)
            ->whereBetween('published_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->withCount(['likes', 'comments'])
            ->with('user')
            ->get();

        return $posts
            ->groupBy('user_id')
            ->map(function (Collection $group) {
                $user = $group->first()->user;

                return [
                    'user'           => $user,
                    'user_id'        => (int) $group->first()->user_id,
                    'posts_count'    => $group->count(),
                    'likes_count'    => (int) $group->sum('likes_count'),
                    'comments_count' => (int) $group->sum('comments_count'),
                    'first_at'       => $group->min(fn ($p) => $p->published_at->timestamp),
                ];
            })
            ->filter(fn ($r) => $r['user'] && ! $r['user']->isAdmin())
            ->sort(function ($a, $b) {
                $engA = $a['likes_count'] + $a['comments_count'];
                $engB = $b['likes_count'] + $b['comments_count'];

                return [$b['posts_count'], $engB, $a['first_at'], $a['user_id']]
                   <=> [$a['posts_count'], $engA, $b['first_at'], $b['user_id']];
            })
            ->values()
            ->map(fn ($r, $i) => [
                'rank'           => $i + 1,
                'user_id'        => $r['user_id'],
                'name'           => $r['user']->name,
                'avatar_url'     => $r['user']->avatar_url,
                'posts_count'    => $r['posts_count'],
                'likes_count'    => $r['likes_count'],
                'comments_count' => $r['comments_count'],
                'eligible'       => $r['posts_count'] >= self::MIN_POSTS,
            ]);
    }

    /** Kandidat yang boleh diumumkan: maksimal 5 teratas yang memenuhi syarat minimum. */
    public static function candidates(Collection $ranking): Collection
    {
        return $ranking->where('eligible', true)->take(self::MAX_ANNOUNCED)->values();
    }
}
