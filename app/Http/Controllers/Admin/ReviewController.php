<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
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
            'posts' => Post::with(['user:id,name', 'category:id,name'])
                ->where('status', Post::SUBMITTED)
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                }))
                ->oldest('updated_at')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function approve(Request $request, Post $post)
    {
        if ($post->status !== Post::SUBMITTED) {
            return back()->withErrors(['post' => 'Postingan ini sudah tidak menunggu review.']);
        }

        $data = $request->validate([
            'expired_at' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'expired_at.required'       => 'Tanggal akhir tayang wajib diisi.',
            'expired_at.date'           => 'Tanggal akhir tayang tidak valid.',
            'expired_at.after_or_equal' => 'Tanggal akhir tayang tidak boleh sebelum hari ini.',
        ]);

        $post->markPublished(Carbon::parse($data['expired_at'])->endOfDay());

        return redirect()->route('admin.reviews.index');
    }

    public function reject(Request $request, Post $post)
    {
        if ($post->status !== Post::SUBMITTED) {
            return back()->withErrors(['post' => 'Postingan ini sudah tidak menunggu review.']);
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min'      => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max'      => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $post->markRejected($data['rejection_reason']);

        return redirect()->route('admin.reviews.index');
    }
}