<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Notifications\PostScheduleCancelled;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::active()
                ->with(['user:id,name', 'category:id,name'])
                ->when($q, fn ($query) => $query->where('title', 'like', "%{$q}%"))
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
            // Sudah disetujui tapi belum waktunya tayang. Tidak ikut pencarian dan halaman.
            'scheduled' => Post::scheduled()
                ->with(['user:id,name', 'category:id,name'])
                ->orderBy('published_at')
                ->get()
                ->map(fn (Post $p) => [
                    'id'            => $p->id,
                    'title'         => $p->title,
                    'author'        => $p->user?->name,
                    'category'      => $p->category?->name,
                    'publish_label' => $p->published_at->translatedFormat('d M Y'),
                    'expired_label' => $p->expired_at?->translatedFormat('d M Y') ?? 'Tanpa batas',
                ])
                ->values(),
            // Dihitung dari semua postingan tayang, bukan hanya hasil pencarian
            'pinnedCount' => Post::active()->where('is_pinned', true)->count(),
            'maxPinned'   => Post::MAX_PINNED,
            'filters'     => ['q' => $q],
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

        ActivityLog::record('post.expiry', $post, 'Sampai ' . $post->expired_at->translatedFormat('d M Y'));

        return back();
    }

    public function pin(Post $post)
    {
        if (! $post->isActive()) {
            return back()->withErrors(['post' => 'Hanya postingan yang sedang tayang yang bisa di-pin.']);
        }

        // Hitung dan pasang dalam satu transaksi dengan kunci baris,
        // supaya dua admin tidak bisa sama-sama meloloskan pin ke-4.
        $result = DB::transaction(function () use ($post) {
            $pinnedIds = Post::active()->where('is_pinned', true)->lockForUpdate()->pluck('id');

            if ($pinnedIds->contains($post->id)) {
                return ['changed' => false]; // sudah di-pin
            }
            if ($pinnedIds->count() >= Post::MAX_PINNED) {
                return ['error' => 'Maksimal ' . Post::MAX_PINNED . ' postingan yang bisa di-pin. Lepas pin salah satu dulu.'];
            }

            $post->setPinned(true);

            return ['changed' => true];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['post' => $result['error']]);
        }

        if ($result['changed']) {
            ActivityLog::record('post.pinned', $post);
        }

        return back();
    }

    public function unpin(Post $post)
    {
        $wasPinned = (bool) $post->is_pinned;

        $post->setPinned(false);

        if ($wasPinned) {
            ActivityLog::record('post.unpinned', $post);
        }

        return back();
    }

    // Pindahkan postingan tayang ke arsip (pin ikut dilepas)
    public function archive(Post $post)
    {
        if (! $post->isActive()) {
            return back()->withErrors(['post' => 'Hanya postingan yang sedang tayang yang bisa diarsipkan.']);
        }

        $post->markArchived();

        ActivityLog::record('post.archived', $post);

        return back();
    }

    // Batalkan jadwal: postingan terjadwal kembali ke antrean review
        public function unschedule(Post $post)
    {
        if (! $post->isScheduled()) {
            return back()->withErrors(['post' => 'Hanya postingan terjadwal yang bisa dibatalkan jadwalnya.']);
        }

        // Diambil sebelum dibatalkan, karena cancelSchedule() bisa mengubah tanggalnya
        $scheduledLabel = $post->published_at?->translatedFormat('d M Y');

        $post->cancelSchedule();

        ActivityLog::record('post.unscheduled', $post, 'Kembali ke antrean review');

        $post->user?->notify(new PostScheduleCancelled($post, $scheduledLabel));

        return back();
    }

    // Hapus permanen. Hanya postingan tayang atau arsip; milik penulis yang masih
    // draft, ditolak, atau menunggu review tidak boleh dihapus dari sini.
    public function destroy(Post $post)
    {
        if (! $post->isActive() && ! $post->isArchived()) {
            return back()->withErrors(['post' => 'Hanya postingan yang tayang atau di arsip yang bisa dihapus dari sini.']);
        }

        // Dicatat sebelum dihapus supaya judulnya masih terbaca di log
        ActivityLog::record('post.deleted', $post);

        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $post->delete();

        return back();
    }
}