<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollVoteController extends Controller
{
    public function store(Request $request, Poll $poll)
    {
        $data = $request->validate([
            'option_id' => ['required', 'integer'],
        ], [
            'option_id.required' => 'Pilih salah satu jawaban.',
            'option_id.integer'  => 'Pilihan tidak valid.',
        ]);

        if (! $poll->isOpen()) {
            return back()->withErrors(['poll' => 'Polling ini sudah tidak menerima suara.']);
        }

        // Pilihan harus milik polling ini
        $option = $poll->options()->whereKey($data['option_id'])->first();
        if (! $option) {
            return back()->withErrors(['option_id' => 'Pilihan tidak valid untuk polling ini.']);
        }

        $userId = $request->user()->id;

        try {
            $error = DB::transaction(function () use ($poll, $option, $userId) {
                $vote = PollVote::where('poll_id', $poll->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($vote === null) {
                    PollVote::create([
                        'poll_id'        => $poll->id,
                        'poll_option_id' => $option->id,
                        'user_id'        => $userId,
                    ]);

                    return null;
                }

                if (! $poll->allow_change) {
                    return 'Suara pada polling ini tidak bisa diubah.';
                }

                // Suara lama diganti, bukan ditambah
                if ((int) $vote->poll_option_id !== (int) $option->id) {
                    $vote->update(['poll_option_id' => $option->id]);
                }

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            // Dua permintaan pertama masuk bersamaan: yang kedua kalah
            $error = 'Suaramu sudah tercatat. Muat ulang halaman.';
        }

        if ($error) {
            return back()->withErrors(['poll' => $error]);
        }

        return back();
    }
}