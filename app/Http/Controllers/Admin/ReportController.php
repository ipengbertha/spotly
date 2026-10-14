<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Notifications\PostSuspended;
use App\Notifications\ReportReviewed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    // Antrean laporan: satu baris per postingan (semua laporan menunggu digabung), plus riwayat keputusan
    public function index(Request $request)
    {
        $request->validate([
            'q'   => ['nullable', 'string', 'max:100'],
            'tab' => ['nullable', 'in:pending,history'],
        ]);

        $q   = $request->query('q');
        $tab = $request->query('tab', 'pending');

        $data = ['tab' => $tab, 'filters' => ['q' => $q]];

        if ($tab === 'pending') {
            $data['posts'] = Post::whereHas('reports', fn ($r) => $r->pending())
                ->with([
                    'user:id,name',
                    'category:id,name',
                    'reports' => fn ($r) => $r->pending()->with('reporter:id,name')->oldest(),
                ])
                ->when($q, fn ($query) => $query->where('title', 'like', "%{$q}%"))
                ->withCount(['reports as pending_count' => fn ($r) => $r->pending()])
                ->orderByDesc('pending_count')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Post $p) => [
                    'id'           => $p->id,
                    'title'        => $p->title,
                    'slug'         => $p->slug,
                    'author'       => $p->user?->name,
                    'category'     => $p->category?->name,
                    'status'       => $p->displayStatus(),
                    'can_suspend'  => $p->status === Post::PUBLISHED,
                    'pending_count' => $p->pending_count,
                    // Alasan yang paling sering dipilih pelapor, jadi pilihan awal saat menangguhkan
                    'top_reason'   => $p->reports->countBy('reason')->sortDesc()->keys()->first(),
                    'reports'      => $p->reports->map(fn (Report $r) => [
                        'id'           => $r->id,
                        'reason'       => $r->reason,
                        'reason_label' => $r->reasonLabel(),
                        'note'         => $r->note,
                        'reporter'     => $r->reporter?->name,
                        'time'         => $r->created_at->locale('id')->diffForHumans(),
                    ])->values(),
                ]);
        } else {
            $data['history'] = Report::whereIn('status', [Report::UPHELD, Report::DISMISSED])
                ->with(['post:id,title,slug', 'reporter:id,name', 'reviewer:id,name'])
                ->when($q, fn ($query) => $query->whereHas('post', fn ($p) => $p->where('title', 'like', "%{$q}%")))
                ->latest('reviewed_at')
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Report $r) => [
                    'id'           => $r->id,
                    'post_title'   => $r->post?->title,
                    'post_slug'    => $r->post?->slug,
                    'reporter'     => $r->reporter?->name,
                    'reason_label' => $r->reasonLabel(),
                    'status'       => $r->status,
                    'admin_note'   => $r->admin_note,
                    'reviewer'     => $r->reviewer?->name,
                    'reviewed_at'  => $r->reviewed_at?->translatedFormat('d M Y'),
                ]);
        }

        $data['counts'] = [
            'pending' => Post::whereHas('reports', fn ($r) => $r->pending())->count(),
            'history' => Report::whereIn('status', [Report::UPHELD, Report::DISMISSED])->count(),
        ];
        $data['reasons'] = Report::reasonOptions();

        return Inertia::render('Admin/Reports/Index', $data);
    }

    // Ada pelanggaran: postingan ditangguhkan, semua laporan menunggunya dinyatakan ditindaklanjuti
    public function suspend(Request $request, Post $post)
    {
        $data = $request->validate([
            'reason'     => ['required', Rule::in(array_keys(Report::REASONS))],
            'admin_note' => ['nullable', 'string', 'max:300'],
        ], [
            'reason.required' => 'Pilih alasan penangguhan.',
            'reason.in'       => 'Alasan penangguhan tidak valid.',
            'admin_note.max'  => 'Catatan maksimal 300 karakter.',
        ]);

        if (! $post->reports()->pending()->exists()) {
            return back()->withErrors(['post' => 'Tidak ada laporan yang menunggu untuk postingan ini.']);
        }
        if ($post->status !== Post::PUBLISHED) {
            return back()->withErrors(['post' => 'Hanya postingan yang berstatus tayang yang bisa ditangguhkan.']);
        }

        $note   = trim((string) ($data['admin_note'] ?? ''));
        $reason = Report::REASONS[$data['reason']] . ($note !== '' ? ". {$note}" : '');

        $reportIds = DB::transaction(function () use ($post, $reason, $note, $request) {
            $ids = $post->reports()->pending()->pluck('id');

            $post->markSuspended($reason);
            $this->resolve($ids, Report::UPHELD, $note, $request);

            return $ids;
        });

        $post->user?->notify(new PostSuspended($post));
        $this->notifyReporters($reportIds);

        return back();
    }

    // Tidak ada pelanggaran: postingan tetap tayang, para pelapor diberi tahu
    public function dismiss(Request $request, Post $post)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:300'],
        ], [
            'admin_note.max' => 'Catatan maksimal 300 karakter.',
        ]);

        if (! $post->reports()->pending()->exists()) {
            return back()->withErrors(['post' => 'Tidak ada laporan yang menunggu untuk postingan ini.']);
        }

        $note = trim((string) ($data['admin_note'] ?? ''));

        $reportIds = DB::transaction(function () use ($post, $note, $request) {
            $ids = $post->reports()->pending()->pluck('id');
            $this->resolve($ids, Report::DISMISSED, $note, $request);

            return $ids;
        });

        $this->notifyReporters($reportIds);

        return back();
    }

    private function resolve($ids, string $status, string $note, Request $request): void
    {
        Report::whereIn('id', $ids)->update([
            'status'      => $status,
            'admin_note'  => $note !== '' ? $note : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
    }

    private function notifyReporters($ids): void
    {
        Report::whereIn('id', $ids)->with(['reporter', 'post'])->get()
            ->each(fn (Report $r) => $r->reporter?->notify(new ReportReviewed($r)));
    }
}
