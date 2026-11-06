<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PollController extends Controller
{
    public function index()
    {
        $polls = Poll::with(['options' => fn ($q) => $q->withCount('votes')])
            ->latest('id')
            ->get()
            ->map(fn (Poll $p) => [
                'id'           => $p->id,
                'question'     => $p->question,
                'allow_change' => $p->allow_change,
                'status'       => $p->status(),
                'ends_label'   => $p->ends_at->translatedFormat('d M Y'),
                'ends_input'   => $p->ends_at->toDateString(),
                'total_votes'  => $p->options->sum('votes_count'),
                'options'      => $p->options->map(fn ($o) => [
                    'id'    => $o->id,
                    'label' => $o->label,
                    'votes' => $o->votes_count,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Admin/Polls/Index', [
            'polls'   => $polls,
            'hasOpen' => Poll::open()->exists(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question'     => ['required', 'string', 'min:5', 'max:200'],
            'allow_change' => ['required', 'boolean'],
            'ends_at'      => ['required', 'date', 'after_or_equal:today'],
            'options'      => ['required', 'array', 'min:2', 'max:6'],
            'options.*'    => ['required', 'string', 'max:100', 'distinct:ignore_case'],
        ], [
            'question.required'      => 'Pertanyaan wajib diisi.',
            'question.min'           => 'Pertanyaan minimal 5 karakter.',
            'question.max'           => 'Pertanyaan maksimal 200 karakter.',
            'allow_change.required'  => 'Pilih aturan suara.',
            'ends_at.required'       => 'Tanggal berakhir wajib diisi.',
            'ends_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum hari ini.',
            'options.required'       => 'Pilihan jawaban wajib diisi.',
            'options.min'            => 'Minimal 2 pilihan jawaban.',
            'options.max'            => 'Maksimal 6 pilihan jawaban.',
            'options.*.required'     => 'Pilihan jawaban tidak boleh kosong.',
            'options.*.max'          => 'Pilihan jawaban maksimal 100 karakter.',
            'options.*.distinct'     => 'Pilihan jawaban tidak boleh sama.',
        ]);

        // Satu polling aktif dalam satu waktu
        if (Poll::open()->exists()) {
            return back()->withErrors(['poll' => 'Masih ada polling yang berjalan. Tutup dulu sebelum membuat yang baru.']);
        }

        $poll = DB::transaction(function () use ($data) {
            $poll = Poll::create([
                'question'     => $data['question'],
                'allow_change' => $data['allow_change'],
                'ends_at'      => Carbon::parse($data['ends_at'])->endOfDay(),
                'created_by'   => auth()->id(),
            ]);

            foreach (array_values($data['options']) as $i => $label) {
                $poll->options()->create(['label' => $label, 'position' => $i]);
            }

            return $poll;
        });

        ActivityLog::recordSubject(
            'poll.created',
            $poll->question,
            $poll->allow_change ? 'Suara bisa diganti' : 'Suara final'
        );

        return back();
    }

    // Hanya tanggal berakhir yang boleh diubah, dan hanya untuk polling yang masih terbuka
    public function updateEnd(Request $request, Poll $poll)
    {
        if (! $poll->isOpen()) {
            return back()->withErrors(['poll' => 'Hanya polling yang masih terbuka yang bisa diubah tanggal berakhirnya.']);
        }

        $data = $request->validate([
            'ends_at' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'ends_at.required'       => 'Tanggal berakhir wajib diisi.',
            'ends_at.date'           => 'Tanggal berakhir tidak valid.',
            'ends_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum hari ini.',
        ]);

        $poll->update(['ends_at' => Carbon::parse($data['ends_at'])->endOfDay()]);

        ActivityLog::recordSubject('poll.extended', $poll->question, 'Sampai ' . $poll->ends_at->translatedFormat('d M Y'));

        return back();
    }

    public function close(Poll $poll)
    {
        if (! $poll->isOpen()) {
            return back()->withErrors(['poll' => 'Polling ini sudah tidak terbuka.']);
        }

        $poll->update(['closed_at' => now()]);

        ActivityLog::recordSubject('poll.closed', $poll->question);

        return back();
    }

    public function destroy(Poll $poll)
    {
        // Dicatat sebelum dihapus supaya pertanyaannya masih terbaca di log
        ActivityLog::recordSubject('poll.deleted', $poll->question);

        $poll->delete();

        return back();
    }
}