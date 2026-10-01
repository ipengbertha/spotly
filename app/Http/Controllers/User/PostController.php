<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    // Daftar postingan milik user yang sedang login (dipakai sebagai dashboard)
        // Daftar postingan milik user (halaman Kiriman Saya)
    public function index()
    {
        $posts = Auth::user()->posts()->with('category')->latest()->get()
            ->map(fn (Post $p) => [
                'id'               => $p->id,
                'title'            => $p->title,
                'status'           => $p->status,
                'display_status'   => $p->displayStatus(),
                'rejection_reason' => $p->rejection_reason,
                'category'         => $p->category->name,
                'image_url'        => $p->image ? '/storage/' . $p->image : null,
                'can' => [
                    'edit'   => Gate::allows('update', $p),
                    'submit' => Gate::allows('submit', $p),
                    'delete' => Gate::allows('delete', $p),
                ],
            ]);

        return Inertia::render('User/Posts/Index', ['posts' => $posts]);
    }

    public function create()
    {
        return Inertia::render('User/PostCreate', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PostRequest $request)
    {
        $data = $request->validated();

        // user_id diisi otomatis dari relasi, bukan dari form
        $post = Auth::user()->posts()->create([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'content'     => $data['content'],
            'slug'        => Str::slug($data['title']) . '-' . Str::random(5),
            'image'       => $request->file('image')?->store('posts', 'public'),
        ]);
        // status TIDAK dari form: default database = draft

        if ($request->input('action') === 'submit') {
            $post->markSubmitted();
        }

        return redirect()->route('user.posts.index');
    }

    public function edit(Post $post)
    {
        Gate::authorize('update', $post);

        return Inertia::render('User/PostEdit', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'post' => [
                'id'               => $post->id,
                'title'            => $post->title,
                'content'          => $post->content,
                'category_id'      => $post->category_id,
                'rejection_reason' => $post->rejection_reason,
                'image_url'        => $post->image ? '/storage/' . $post->image : null,
            ],
        ]);
    }

    public function update(PostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);
        $data = $request->validated();

        $post->fill([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'content'     => $data['content'],
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama agar storage tidak menumpuk
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $post->image = $request->file('image')->store('posts', 'public');
        }
        $post->save();

        if ($request->input('action') === 'submit') {
            $post->markSubmitted();
        }

        return redirect()->route('user.posts.index');
    }

    public function submit(Post $post)
    {
        Gate::authorize('submit', $post);
        $post->markSubmitted();

        return redirect()->route('user.posts.index');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $post->delete();

        return redirect()->route('user.posts.index');
    }
}