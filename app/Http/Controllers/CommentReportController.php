<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CommentReported;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class CommentReportController extends Controller
{
    // Siswa melaporkan komentar orang lain. Route sudah dibatasi untuk siswa (role:user).
    public function store(Request $request, Comment $comment)
    {
        $user = $request->user();

        // Postingan yang tidak tayang menjawab 404 untuk semua aksi komentar
        abort_unless($comment->post->isActive(), 404);
        abort_if($comment->user_id === $user->id, 403, 'Kamu tidak bisa melaporkan komentarmu sendiri.');

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'note'   => ['nullable', 'string', 'max:300'],
        ], [
            'reason.required' => 'Pilih alasan pelaporan.',
            'reason.in'       => 'Alasan pelaporan tidak valid.',
            'note.max'        => 'Catatan maksimal 300 karakter.',
        ]);

        if ($comment->reports()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['reason' => 'Kamu sudah melaporkan komentar ini.']);
        }

        // Admin hanya diberi tahu pada laporan PERTAMA yang menunggu, supaya tidak dibanjiri
        $alreadyPending = $comment->reports()->pending()->exists();

        try {
            $report = $comment->reports()->create([
                'user_id' => $user->id,
                'reason'  => $data['reason'],
                'note'    => $data['note'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua klik nyaris bersamaan: laporan sudah tercatat
            return back()->withErrors(['reason' => 'Kamu sudah melaporkan komentar ini.']);
        }

        if (! $alreadyPending) {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            Notification::send($admins, new CommentReported($comment, $report->load('reporter')));
        }

        return back();
    }
}
