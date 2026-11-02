<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfilePortfolioTest extends TestCase
{
    use RefreshDatabase;

    private function siswa(): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    private function kategori(string $nama): Category
    {
        $data = ['name' => $nama];

        // Isi slug hanya kalau tabelnya punya kolom itu
        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = Str::slug($nama) . '-' . Str::random(4);
        }

        return Category::forceCreate($data);
    }

    private function buatPost(User $u, Category $utama, array $tambahan = [], string $status = Post::PUBLISHED): Post
    {
        $p = Post::forceCreate([
            'user_id'      => $u->id,
            'category_id'  => $utama->id,
            'title'        => 'Judul ' . Str::random(6),
            'slug'         => Str::slug('judul-' . Str::random(8)),
            'content'      => '<p>isi artikel</p>',
            'status'       => $status,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ]);

        if ($tambahan) {
            $p->extraCategories()->attach($tambahan);
        }

        return $p;
    }

    public function test_ringkasan_menghitung_kategori_utama_dan_tambahan(): void
    {
        $u = $this->siswa();
        $berita = $this->kategori('Berita');
        $opini  = $this->kategori('Opini');

        $this->buatPost($u, $berita);
        $this->buatPost($u, $berita, [$opini->id]);

        $this->get("/profil/{$u->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.categories_count', 2)
                ->has('portfolio.categories', 2)
                ->where('portfolio.categories.0.name', 'Berita')
                ->where('portfolio.categories.0.count', 2)
                ->where('portfolio.categories.1.name', 'Opini')
                ->where('portfolio.categories.1.count', 1)
                ->where('portfolio.selected', null));
    }

    public function test_filter_kategori_utama_dan_tambahan(): void
    {
        $u = $this->siswa();
        $berita = $this->kategori('Berita');
        $opini  = $this->kategori('Opini');

        $this->buatPost($u, $berita);
        $this->buatPost($u, $berita, [$opini->id]);

        $this->get("/profil/{$u->id}?kategori={$opini->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolio.selected', $opini->id)
                ->has('posts.data', 1));

        $this->get("/profil/{$u->id}?kategori={$berita->id}")
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
    }

    public function test_kategori_tidak_dikenal_diabaikan(): void
    {
        $u = $this->siswa();
        $this->buatPost($u, $this->kategori('Berita'));
        $lain = $this->kategori('Milik orang lain');

        $this->get("/profil/{$u->id}?kategori={$lain->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolio.selected', null)
                ->has('posts.data', 1));
    }

    public function test_draf_tidak_masuk_portofolio(): void
    {
        $u = $this->siswa();
        $this->buatPost($u, $this->kategori('Berita'), [], Post::DRAFT);

        $this->get("/profil/{$u->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('portfolio.categories', 0)
                ->has('posts.data', 0));
    }
}