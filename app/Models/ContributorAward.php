<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ContributorAward extends Model
{
    protected $fillable = [
    'period', 'user_id', 'rank',
    'posts_count', 'likes_count', 'comments_count', 'announced_by',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** '2026-10' -> 'Oktober 2026' */
    public static function labelFor(string $period): string
    {
        return Carbon::createFromFormat('!Y-m', $period)->locale('id')->translatedFormat('F Y');
    }

    /**
     * Pengumuman terbaru untuk kartu di Beranda.
     * Kartu bertahan sampai ada pengumuman bulan berikutnya.
     * Hanya data yang perlu ditampilkan yang dikirim ke siswa.
     */
    public static function latestForFeed(): ?array
    {
        $period = static::max('period');
        if (! $period) {
            return null;
        }

        $items = static::with('user:id,name,avatar')
            ->where('period', $period)
            ->orderBy('rank')
            ->get()
            ->filter(fn ($a) => $a->user)
            ->map(fn ($a) => [
                'id'          => $a->user->id,
                'rank'        => $a->rank,
                'name'        => $a->user->name,
                'avatar_url'  => $a->user->avatar_url,
                'posts_count' => $a->posts_count,
                'likes_count' => $a->likes_count,
            ])
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'period' => $period,
            'label'  => static::labelFor($period),
            'items'  => $items,
        ];
    }
}
