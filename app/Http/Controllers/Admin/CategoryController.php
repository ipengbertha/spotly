<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'posts_count' => $c->posts_count,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:categories,name'],
        ], $this->messages());

        $slug = $this->slugFor($data['name']);
        if ($slug === null) {
            return back()->withErrors(['name' => 'Nama kategori tidak valid atau terlalu mirip dengan kategori lain.']);
        }

        Category::create(['name' => $data['name'], 'slug' => $slug]);

        return redirect()->route('admin.categories.index');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->ignore($category->id)],
        ], $this->messages());

        $slug = $this->slugFor($data['name'], $category->id);
        if ($slug === null) {
            return back()->withErrors(['name' => 'Nama kategori tidak valid atau terlalu mirip dengan kategori lain.']);
        }

        $category->update(['name' => $data['name'], 'slug' => $slug]);

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category)
    {
        $count = $category->posts()->count();

        if ($count > 0) {
            return back()->withErrors([
                'category' => "Kategori \"{$category->name}\" masih dipakai {$count} postingan, jadi tidak bisa dihapus.",
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index');
    }

    // Slug otomatis dari nama. null = kosong atau sudah dipakai kategori lain.
    private function slugFor(string $name, ?int $ignoreId = null): ?string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            return null;
        }

        $taken = Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return $taken ? null : $slug;
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max'      => 'Nama kategori maksimal 50 karakter.',
            'name.unique'   => 'Nama kategori sudah dipakai.',
        ];
    }
}