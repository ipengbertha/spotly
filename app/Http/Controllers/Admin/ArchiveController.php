<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Archive/Index', [
            'posts' => Post::archive()
                ->with(['user:id,name', 'category:id,name'])
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                }))
                ->orderByDesc('updated_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Post $p) => [
                    'id'              => $p->id,
                    'title'           => $p->title,
                    'author'          => $p->user?->name,
                    'category'        => $p->category?->name,
                    'reason'          => $p->status === Post::ARCHIVED ? 'Diarsipkan admin' : 'Masa tayang habis',
                    'published_label' => $p->published_at?->translatedFormat('d M Y') ?? '-',
                    'expired_label'   => $p->expired_at?->translatedFormat('d M Y') ?? '-',
                ]),
            'filters' => ['q' => $q],
        ]);
    }

    // Tayangkan lagi dengan tanggal akhir baru. Tayang mulai sekarang, tanpa pin.
    public function republish(Request $request, Post $post)
    {
        if (! $post->isArchived()) {
            return back()->withErrors(['post' => 'Postingan ini tidak ada di arsip.']);
        }

        $data = $request->validate([
            'expired_at' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'expired_at.required'       => 'Tanggal akhir tayang wajib diisi.',
            'expired_at.date'           => 'Tanggal akhir tayang tidak valid.',
            'expired_at.after_or_equal' => 'Tanggal akhir tayang tidak boleh sebelum hari ini.',
        ]);

        $post->markPublished(Carbon::parse($data['expired_at'])->endOfDay());
        $post->setPinned(false);

        return back();
    }
}