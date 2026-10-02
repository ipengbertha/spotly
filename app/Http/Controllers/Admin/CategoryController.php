<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
        public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount(['posts', 'extraPosts'])
                ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Category $c) => [
                    'id'            => $c->id,
                    'name'          => $c->name,
                    'slug'          => $c->slug,
                    // Total pemakaian (utama + tambahan) dan khusus sebagai kategori utama
                    'posts_count'   => $c->posts_count + $c->extra_posts_count,
                    'primary_count' => $c->posts_count,
                    'is_fallback'   => $c->isFallback(),
                ]),
            // Semua kategori (tanpa halaman) untuk pilihan tujuan pemindahan
            'allCategories' => Category::orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'is_fallback' => $c->isFallback(),
                ]),
            'filters' => ['q' => $q],
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
        if ($category->isFallback()) {
            return back()->withErrors(['name' => 'Kategori "Lainnya" adalah kategori bawaan, namanya tidak bisa diubah.']);
        }

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

    public function destroy(Request $request, Category $category)
    {
        if ($category->isFallback()) {
            return back()->withErrors(['category' => 'Kategori "Lainnya" adalah kategori bawaan dan tidak bisa dihapus.']);
        }

        $data = $request->validate([
            'move_to' => ['nullable', 'integer', Rule::exists('categories', 'id'), Rule::notIn([$category->id])],
        ], [
            'move_to.integer' => 'Kategori tujuan tidak valid.',
            'move_to.exists'  => 'Kategori tujuan tidak ditemukan.',
            'move_to.not_in'  => 'Kategori tujuan tidak boleh sama dengan kategori yang dihapus.',
        ]);

        try {
            DB::transaction(function () use ($category, $data) {
                $stuck = []; // postingan tanpa kategori tambahan: perlu tujuan baru

                $primaryIds = DB::table('posts')->where('category_id', $category->id)->pluck('id');

                foreach ($primaryIds as $postId) {
                    $next = DB::table('category_post')
                        ->where('post_id', $postId)
                        ->where('category_id', '!=', $category->id)
                        ->orderBy('id')
                        ->first();

                    if ($next) {
                        // Kategori tambahan pertama naik jadi kategori utama
                        // (lewat DB::table supaya updated_at tidak berubah: dipakai urutan antrean review)
                        DB::table('posts')->where('id', $postId)->update(['category_id' => $next->category_id]);
                        DB::table('category_post')->where('id', $next->id)->delete();
                    } else {
                        $stuck[] = $postId;
                    }
                }

                if ($stuck) {
                    $target = ! empty($data['move_to'])
                        ? Category::findOrFail($data['move_to'])
                        : Category::fallback();

                    DB::table('posts')->whereIn('id', $stuck)->update(['category_id' => $target->id]);
                }

                // Lepas kategori ini dari postingan yang memakainya sebagai kategori tambahan
                DB::table('category_post')->where('category_id', $category->id)->delete();

                $category->delete();
            });
        } catch (QueryException) {
            return back()->withErrors([
                'category' => 'Kategori gagal dihapus karena baru saja dipakai postingan lain. Coba lagi.',
            ]);
        }

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