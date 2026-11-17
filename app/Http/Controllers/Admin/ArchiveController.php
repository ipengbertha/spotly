<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
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
                ->with(['user:id,name', 'category:id,name,expiry_mode'])
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
                    'expiry_mode'     => $p->category?->mode() ?? Category::EXPIRY_DATED,
                    // Masa tayang lewat = "Masa tayang habis", baik sudah diarsipkan otomatis maupun belum
                    'reason'          => $p->expired_at && $p->expired_at->lte(now()) ? 'Masa tayang habis' : 'Diarsipkan admin',
                    'published_label' => $p->published_at?->translatedFormat('d M Y') ?? '-',
                    'expired_label'   => $p->expired_at?->translatedFormat('d M Y') ?? '-',
                ]),
            'filters' => ['q' => $q],
        ]);
    }

    // Tayangkan lagi. Tayang mulai sekarang, tanpa pin. Tanggal akhir mengikuti aturan kategori.
    public function republish(Request $request, Post $post)
    {
        if (! $post->isArchived()) {
            return back()->withErrors(['post' => 'Postingan ini tidak ada di arsip.']);
        }

        $category = $post->category;
        $dated    = $category->mode() === Category::EXPIRY_DATED;
        $noExpiry = $dated && $request->boolean('no_expiry');

        $rules = [];
        if ($dated && ! $noExpiry) {
            $rules['expired_at'] = ['required', 'date', 'after_or_equal:today'];
        }

        $data = $request->validate($rules, [
            'expired_at.required'       => 'Tanggal akhir tayang wajib diisi.',
            'expired_at.date'           => 'Tanggal akhir tayang tidak valid.',
            'expired_at.after_or_equal' => 'Tanggal akhir tayang tidak boleh sebelum hari ini.',
        ]);

        $requested = ($dated && ! $noExpiry) ? Carbon::parse($data['expired_at'])->endOfDay() : null;

        $post->markPublished($category->resolveExpiry($requested));
        $post->setPinned(false);

        ActivityLog::record('post.republished', $post,
            $post->expired_at ? 'Sampai ' . $post->expired_at->translatedFormat('d M Y') : 'Tanpa batas');

        return back();
    }
}