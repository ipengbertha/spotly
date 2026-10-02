<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::active()
                ->with(['user:id,name', 'category:id,name'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Post $p) => [
                    'id'              => $p->id,
                    'title'           => $p->title,
                    'author'          => $p->user?->name,
                    'category'        => $p->category?->name,
                    'is_pinned'       => $p->is_pinned,
                    'published_label' => $p->published_at->translatedFormat('d M Y'),
                    'expired_label'   => $p->expired_at?->translatedFormat('d M Y') ?? 'Tanpa batas',
                    'expired_input'   => $p->expired_at?->toDateString() ?? '',
                ]),
            'pinnedCount' => Post::active()->where('is_pinned', true)->count(),
            'maxPinned'   => Post::MAX_PINNED,
        ]);
    }

    public function updateExpiry(Request $request, Post $post)
    {
        if (! $post->isActive()) {
            return back()->withErrors(['post' => 'Hanya postingan yang sedang tayang yang bisa diubah masa tayangnya.']);
        }

        $data = $request->validate([
            'expired_at' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'expired_at.required'       => 'Tanggal akhir tayang wajib diisi.',
            'expired_at.date'           => 'Tanggal akhir tayang tidak valid.',
            'expired_at.after_or_equal' => 'Tanggal akhir tayang tidak boleh sebelum hari ini.',
        ]);

        $post->setExpiry(Carbon::parse($data['expired_at'])->endOfDay());

        return back();
    }

    public function pin(Post $post)
    {
        if (! $post->isActive()) {
            return back()->withErrors(['post' => 'Hanya postingan yang sedang tayang yang bisa di-pin.']);
        }

        // Hitung dan pasang dalam satu transaksi dengan kunci baris,
        // supaya dua admin tidak bisa sama-sama meloloskan pin ke-4.
        $error = DB::transaction(function () use ($post) {
            $pinnedIds = Post::active()->where('is_pinned', true)->lockForUpdate()->pluck('id');

            if ($pinnedIds->contains($post->id)) {
                return null; // sudah di-pin
            }
            if ($pinnedIds->count() >= Post::MAX_PINNED) {
                return 'Maksimal ' . Post::MAX_PINNED . ' postingan yang bisa di-pin. Lepas pin salah satu dulu.';
            }

            $post->setPinned(true);
            return null;
        });

        return $error ? back()->withErrors(['post' => $error]) : back();
    }

    public function unpin(Post $post)
    {
        $post->setPinned(false);

        return back();
    }
}