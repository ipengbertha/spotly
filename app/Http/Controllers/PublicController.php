<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function home(Request $request)
    {
        $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ]);

        $q = $request->query('q');

        $posts = Post::active()
            ->with(['category:id,name', 'user:id,name', 'extraCategories'])
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            }))
            // Kategori utama ATAU tambahan
            ->when($request->filled('category'), function ($query) use ($request) {
                $id = $request->integer('category');
                $query->where(function ($w) use ($id) {
                    $w->where('category_id', $id)
                      ->orWhereHas('extraCategories', fn ($c) => $c->where('categories.id', $id));
                });
            })
            ->orderByDesc('is_pinned')      // pinned selalu di atas
            ->orderByDesc('published_at')   // lalu yang terbaru
            ->get()
            ->map(fn (Post $p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'excerpt'      => Str::limit(strip_tags($p->content), 110),
                'image_url'    => $p->image ? Storage::url($p->image) : null,
                'category'     => $p->category->name,
                'categories'   => $p->categoryNames(),
                'author'       => $p->user->name,
                'is_pinned'    => $p->is_pinned,
                'published_at' => $p->published_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Home', [
            'posts'      => $posts,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters'    => [
                'q'        => $q,
                'category' => $request->query('category'),
            ],
        ]);
    }
}