<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = ['question', 'allow_change', 'ends_at', 'closed_at', 'created_by'];

    protected $casts = [
        'allow_change' => 'boolean',
        'ends_at'      => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // Terbuka: belum ditutup admin dan belum lewat tanggal berakhir
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at')->where('ends_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->ends_at->isFuture();
    }

    // open: menerima suara | ended: lewat tanggal | closed: ditutup admin
    public function status(): string
    {
        if ($this->closed_at !== null) {
            return 'closed';
        }

        return $this->ends_at->isFuture() ? 'open' : 'ended';
    }

    // Bentuk data untuk Beranda. Angka suara hanya dikirim kalau penonton
    // memang boleh melihatnya: admin, atau siswa yang sudah memilih.
    public function forViewer(User $viewer): array
    {
        $isStudent = $viewer->role === 'user';
        $mine = $isStudent
            ? $this->votes()->where('user_id', $viewer->id)->value('poll_option_id')
            : null;
        $showResults = ! $isStudent || $mine !== null;

        $counts = $showResults
            ? $this->votes()->selectRaw('poll_option_id, count(*) as total')
                ->groupBy('poll_option_id')->pluck('total', 'poll_option_id')
            : collect();
        $total = (int) $counts->sum();

        return [
            'id'           => $this->id,
            'question'     => $this->question,
            'allow_change' => $this->allow_change,
            'ends_label'   => $this->ends_at->translatedFormat('d M Y'),
            'can_vote'     => $isStudent && $this->isOpen(),
            'my_option_id' => $mine !== null ? (int) $mine : null,
            'show_results' => $showResults,
            'total_votes'  => $showResults ? $total : null,
            'options'      => $this->options->map(function ($o) use ($showResults, $counts, $total) {
                $votes = (int) ($counts[$o->id] ?? 0);

                return [
                    'id'      => $o->id,
                    'label'   => $o->label,
                    'votes'   => $showResults ? $votes : null,
                    'percent' => $showResults && $total > 0 ? (int) round($votes / $total * 100) : ($showResults ? 0 : null),
                ];
            })->values()->all(),
        ];
    }
}