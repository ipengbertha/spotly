<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Category;
use App\Notifications\PostApproved;
use App\Notifications\PostRejected;
use App\Notifications\PostRevisionRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Reviews/Index', [
            'posts' => Post::with(['user:id,name', 'category:id,name,expiry_mode'])
                ->where('status', Post::SUBMITTED)
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                }))
                ->oldest('updated_at')
                ->paginate(10)
                ->withQueryString()
                ->through(function (Post $p) {
                    $p->setAttribute('content_html', $p->contentHtml());

                    return $p;
                }),
        ]);
    }

    public function approve(Request $request, Post $post)
    {
        if ($post->status !== Post::SUBMITTED) {
            return back()->withErrors(['post' => 'Postingan ini sudah tidak menunggu review.']);
        }

        $category = $post->category;
        $dated    = $category->mode() === Category::EXPIRY_DATED;
        $noExpiry = $dated && $request->boolean('no_expiry');

        $rules = [
            // Tanggal mulai tayang boleh kosong: artinya tayang sekarang
            'publish_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];

        // Tanggal akhir hanya diminta untuk kategori "ada batas tanggal",
        // dan hanya kalau admin tidak memilih "tanpa batas".
        if ($dated && ! $noExpiry) {
            $rules['expired_at'] = [
                'required', 'date', 'after_or_equal:today',
                // Akhir tayang tidak boleh sebelum mulai tayang
                function ($attribute, $value, $fail) use ($request) {
                    $start = $request->input('publish_at');

                    if ($start && strtotime($start) !== false && strtotime($value) !== false
                        && Carbon::parse($value)->lt(Carbon::parse($start))) {
                        $fail('Tanggal akhir tayang tidak boleh sebelum tanggal mulai tayang.');
                    }
                },
            ];
        }

        $data = $request->validate($rules, [
            'publish_at.date'           => 'Tanggal mulai tayang tidak valid.',
            'publish_at.after_or_equal' => 'Tanggal mulai tayang tidak boleh sebelum hari ini.',
            'expired_at.required'       => 'Tanggal akhir tayang wajib diisi.',
            'expired_at.date'           => 'Tanggal akhir tayang tidak valid.',
            'expired_at.after_or_equal' => 'Tanggal akhir tayang tidak boleh sebelum hari ini.',
        ]);

        // Hari ini atau kosong: tayang sekarang. Tanggal depan: tayang mulai pukul 00.00 hari itu.
        $publishAt = null;
        if (! empty($data['publish_at'])) {
            $start = Carbon::parse($data['publish_at'])->startOfDay();
            $publishAt = $start->isFuture() ? $start : null;
        }

        // Tanggal akhir ditentukan aturan kategori (tanggal pilihan admin, tanpa batas, atau akhir tahun ajaran)
        $requested = ($dated && ! $noExpiry) ? Carbon::parse($data['expired_at'])->endOfDay() : null;

        $post->markPublished($category->resolveExpiry($requested, $publishAt ?? now()), $publishAt);

        $span = $post->expired_at
            ? 'sampai ' . $post->expired_at->translatedFormat('d M Y')
            : 'tanpa batas';

        if ($publishAt) {
            ActivityLog::record('post.scheduled', $post,
                'Tayang ' . $post->published_at->translatedFormat('d M Y') . ' ' . $span);
        } else {
            ActivityLog::record('post.approved', $post, 'Tayang ' . $span);
        }

        if ($post->user?->wantsNotification('post_approved')) {
            $post->user->notify(new PostApproved($post));
        }

        return redirect()->route('admin.reviews.index');
    }

    public function requestRevision(Request $request, Post $post)
    {
        if ($post->status !== Post::SUBMITTED) {
            return back()->withErrors(['post' => 'Postingan ini sudah tidak menunggu review.']);
        }

        $data = $request->validate([
            'revision_note' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'revision_note.required' => 'Catatan revisi wajib diisi.',
            'revision_note.min'      => 'Catatan revisi minimal 10 karakter.',
            'revision_note.max'      => 'Catatan revisi maksimal 500 karakter.',
        ]);

        $post->markRevisionRequested($data['revision_note']);

        ActivityLog::record('post.revision', $post, $data['revision_note']);

        // Notifikasi memakai preferensi yang sama dengan penolakan
        if ($post->user?->wantsNotification('post_rejected')) {
            $post->user->notify(new PostRevisionRequested($post));
        }

        return redirect()->route('admin.reviews.index');
    }
}