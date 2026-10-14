<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportSubmitted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    // Siswa melaporkan postingan yang sedang tayang. Route sudah dibatasi untuk siswa (role:user).
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        // Hanya postingan tayang. 404 supaya postingan lain tidak ketahuan ada.
        abort_unless($post->isActive(), 404);
        abort_if($post->user_id === $user->id, 403, 'Kamu tidak bisa melaporkan postingan sendiri.');

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'note'   => ['nullable', 'string', 'max:300'],
        ], [
            'reason.required' => 'Pilih alasan pelaporan.',
            'reason.in'       => 'Alasan pelaporan tidak valid.',
            'note.max'        => 'Catatan maksimal 300 karakter.',
        ]);

        if ($post->reports()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['reason' => 'Kamu sudah melaporkan postingan ini.']);
        }

        // Admin hanya diberi tahu pada laporan PERTAMA yang menunggu, supaya tidak dibanjiri
        $alreadyPending = $post->reports()->pending()->exists();

        try {
            $report = $post->reports()->create([
                'user_id' => $user->id,
                'reason'  => $data['reason'],
                'note'    => $data['note'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: laporan sudah tercatat
            return back()->withErrors(['reason' => 'Kamu sudah melaporkan postingan ini.']);
        }

        if (! $alreadyPending) {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            Notification::send($admins, new ReportSubmitted($post, $report->load('reporter')));
        }

        return back();
    }

    // Detail laporan untuk pelapor (dibuka dari notifikasi). Laporan orang lain = 404.
    public function show(Request $request, Report $report)
    {
        abort_unless($report->user_id === $request->user()->id, 404);

        $report->load('post:id,title,slug,status');

        return Inertia::render('Reports/Show', [
            'report' => [
                'id'           => $report->id,
                'post_title'   => $report->post?->title,
                'post_url'     => $report->post ? "/posts/{$report->post->slug}" : null,
                'reason_label' => $report->reasonLabel(),
                'note'         => $report->note,
                'status'       => $report->status,
                'admin_note'   => $report->admin_note,
                'created_at'   => $report->created_at->toIso8601String(),
                'reviewed_at'  => $report->reviewed_at?->toIso8601String(),
            ],
        ]);
    }
}
