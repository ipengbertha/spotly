<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContributorAward;
use App\Services\ContributorRanking;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ContributorController extends Controller
{
    private const PER_PAGE = 20;
    private const PERIOD_RULE = 'regex:/^\d{4}-(0[1-9]|1[0-2])$/';

    public function index(Request $request)
    {
        $request->validate(['period' => ['nullable', self::PERIOD_RULE]]);

        $month  = $request->filled('period')
            ? Carbon::createFromFormat('!Y-m', $request->query('period'))
            : now()->subMonthNoOverflow()->startOfMonth();   // bawaan: bulan lalu
        $period = $month->format('Y-m');

        $ranking    = ContributorRanking::forMonth($month);
        $candidates = ContributorRanking::candidates($ranking);
        $announced  = ContributorAward::where('period', $period)->orderBy('rank')->pluck('user_id')->all();

        $page = max(1, $request->integer('page', 1));
        $rows = new LengthAwarePaginator(
            $ranking->forPage($page, self::PER_PAGE)->values(),
            $ranking->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => ['period' => $period]],
        );

        // 12 bulan terakhir, termasuk bulan berjalan (hanya bisa dilihat, belum bisa diumumkan)
        $months = collect(range(0, 11))->map(function ($i) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            return ['value' => $m->format('Y-m'), 'label' => ContributorAward::labelFor($m->format('Y-m'))];
        })->values();

        return Inertia::render('Admin/Contributors', [
            'period'      => $period,
            'periodLabel' => ContributorAward::labelFor($period),
            'months'      => $months,
            'ranking'     => $rows,
            'candidateIds'=> $candidates->pluck('user_id')->all(),
            // Sudah diumumkan -> tampilkan pilihan yang tersimpan. Belum -> 5 teratas tercentang.
            'selected'    => $announced ?: $candidates->pluck('user_id')->all(),
            'announced'   => ! empty($announced),
            'canAnnounce' => $month->lt(now()->startOfMonth()),
            'max'         => ContributorRanking::MAX_ANNOUNCED,
            'minPosts'    => ContributorRanking::MIN_POSTS,
        ]);
    }

    /** Umumkan, atau ubah pengumuman yang sudah ada untuk bulan itu. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'period'     => ['required', self::PERIOD_RULE],
            'user_ids'   => ['required', 'array', 'min:1', 'max:'.ContributorRanking::MAX_ANNOUNCED],
            'user_ids.*' => ['integer', 'distinct'],
        ]);

        $month = Carbon::createFromFormat('!Y-m', $data['period']);

        if (! $month->lt(now()->startOfMonth())) {
            throw ValidationException::withMessages([
                'period' => 'Bulan ini belum selesai, jadi belum bisa diumumkan.',
            ]);
        }

        // Angka dihitung ulang di server, bukan dipercaya dari browser.
        $candidates = ContributorRanking::candidates(ContributorRanking::forMonth($month))->keyBy('user_id');

        $invalid = collect($data['user_ids'])->reject(fn ($id) => $candidates->has($id));
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Hanya 5 teratas yang memenuhi syarat minimum yang bisa diumumkan.',
            ]);
        }

        DB::transaction(function () use ($data, $candidates, $request) {
            ContributorAward::where('period', $data['period'])->delete();

            foreach ($data['user_ids'] as $id) {
                $c = $candidates[$id];
                ContributorAward::create([
                    'period'         => $data['period'],
                    'user_id'        => $c['user_id'],
                    'rank'           => $c['rank'],
                    'posts_count'    => $c['posts_count'],
                    'likes_count'    => $c['likes_count'],
                    'comments_count' => $c['comments_count'],
                    'announced_by'   => $request->user()->id,
                ]);
            }
        });

        // TODO (tahap 2): catat ke log aktivitas admin.

        return back()->with('success', 'Kontributor bulan '.ContributorAward::labelFor($data['period']).' diumumkan.');
    }

    public function destroy(string $period)
    {
        ContributorAward::where('period', $period)->delete();

        // TODO (tahap 2): catat ke log aktivitas admin.

        return back()->with('success', 'Pengumuman dibatalkan.');
    }
}
