<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    // Daftar komentar terbaru dari semua postingan, dengan pencarian dan filter "dilaporkan".
    // filter=reported: hanya komentar yang punya laporan menunggu (pending).
    public function index(Request $request)
    {
        $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'in:all,reported'],
        ]);

        $q      = $request->query('q');
        $filter = $request->query('filter') === 'reported' ? 'reported' : 'all';

        $withPendingReports = fn ($report) => $report->pending();

        return Inertia::render('Admin/Comments/Index', [
            'filters'  => ['q' => $q, 'filter' => $filter],
            // Jumlah tidak ikut pencarian, supaya angka di tab tetap stabil
            'counts'   => [
                'all'      => Comment::count(),
                'reported' => Comment::whereHas('reports', $withPendingReports)->count(),
            ],
            'comments' => Comment::with([
                    'user:id,name',
                    'post:id,title,slug,status,expired_at',
                    'parent.user:id,name',
                    'reports' => fn ($r) => $r->pending()->with('reporter:id,name')->oldest()->oldest('id'),
                ])
                ->withCount([
                    'replies',
                    'reports as pending_reports' => $withPendingReports,
                ])
                ->when($filter === 'reported', fn ($query) => $query->whereHas('reports', $withPendingReports))
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('content', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('post', fn ($p) => $p->where('title', 'like', "%{$q}%"));
                }))
                ->latest()
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Comment $c) => [
                    'id'              => $c->id,
                    'content'         => $c->content,
                    'reply_to'        => $c->parent?->user?->name,
                    'replies_count'   => $c->replies_count,
                    'pending_reports' => (int) $c->pending_reports,
                    'reports'         => $c->reports->map(fn (CommentReport $r) => [
                        'id'           => $r->id,
                        'reporter'     => $r->reporter?->name,
                        'reason_label' => $r->reasonLabel(),
                        'note'         => $r->note,
                    ])->values(),
                    'author'          => $c->user?->name,
                    'author_id'       => $c->user?->id,
                    'post_title'      => $c->post?->title,
                    'post_slug'       => $c->post?->slug,
                    'post_status'     => $c->post?->displayStatus(),
                    'time'            => $c->created_at->locale('id')->diffForHumans(),
                    'date'            => $c->created_at->translatedFormat('d M Y, H:i'),
                ]),
        ]);
    }

    // Admin boleh menghapus komentar apa pun, termasuk di postingan yang sudah tidak tayang
    // (moderasi tidak boleh terhalang status postingan). Balasan di bawahnya ikut terhapus.
    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back();
    }

    // Laporan dinilai tidak beralasan: semua laporan yang menunggu ditandai selesai (dismissed),
    // komentarnya tetap tampil. Mengubah lewat query karena status dan reviewed_* tidak fillable.
    public function dismissReports(Request $request, Comment $comment)
    {
        $comment->reports()->pending()->update([
            'status'      => CommentReport::DISMISSED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back();
    }
}