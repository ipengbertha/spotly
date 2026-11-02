<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function kategori(string $nama): Category
    {
        $data = ['name' => $nama];
        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = Str::slug($nama) . '-' . Str::random(4);
        }

        return Category::forceCreate($data);
    }

    private function buatPost(Category $kategori, array $extra = []): Post
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        return Post::forceCreate(array_merge([
            'user_id'      => $siswa->id,
            'category_id'  => $kategori->id,
            'title'        => 'Judul ' . Str::random(6),
            'slug'         => Str::slug('judul-' . Str::random(8)),
            'content'      => '<p>isi artikel</p>',
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ], $extra));
    }

    private function suka(Post $post, int $jumlah): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            $u = User::factory()->create(['role' => 'user', 'is_active' => true]);
            Like::forceCreate(['post_id' => $post->id, 'user_id' => $u->id]);
        }
    }

    public function test_dashboard_mengirim_jumlah_terjadwal(): void
    {
        $kat = $this->kategori('Berita');
        $this->buatPost($kat);
        $this->buatPost($kat, ['published_at' => now()->addDays(3)]);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.active', 1)
                ->where('stats.scheduled', 1));
    }

    public function test_grafik_terbit_tujuh_hari(): void
    {
        $kat = $this->kategori('Berita');

        // Hari ini (selalu di dalam hari berjalan) dan 3 hari lalu
        $this->buatPost($kat, ['published_at' => now()->startOfDay()->addSecond()]);
        $this->buatPost($kat, ['published_at' => now()->subDays(3)]);
        // Di luar jendela 7 hari
        $this->buatPost($kat, ['published_at' => now()->subDays(20)]);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('weekly', 7)
                ->where('weekly.6.date', now()->toDateString())
                ->where('weekly.6.count', 1)
                ->where('weekly.3.count', 1)
                ->where('weekly.0.count', 0));
    }

    public function test_postingan_terpopuler_urut_suka_dan_tanpa_interaksi_tidak_ikut(): void
    {
        $kat = $this->kategori('Berita');
        $sepi   = $this->buatPost($kat);
        $biasa  = $this->buatPost($kat);
        $favorit = $this->buatPost($kat);

        $this->suka($biasa, 1);
        $this->suka($favorit, 3);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('topPosts', 2)
                ->where('topPosts.0.id', $favorit->id)
                ->where('topPosts.0.likes', 3)
                ->where('topPosts.1.id', $biasa->id));
    }

    public function test_kategori_teraktif(): void
    {
        $berita = $this->kategori('Berita');
        $opini  = $this->kategori('Opini');

        $this->buatPost($berita);
        $this->buatPost($berita);
        $this->buatPost($opini);
        // Postingan terjadwal tidak dihitung
        $this->buatPost($opini, ['published_at' => now()->addDays(2)]);

        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('categories', 2)
                ->where('categories.0.name', 'Berita')
                ->where('categories.0.count', 2)
                ->where('categories.1.name', 'Opini')
                ->where('categories.1.count', 1));
    }

    public function test_aktivitas_terbaru_maksimal_lima(): void
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 6; $i++) {
            ActivityLog::forceCreate([
                'admin_id'      => $admin->id,
                'action'        => 'post.approved',
                'subject_title' => "Judul {$i}",
                'created_at'    => now()->subMinutes(10 - $i),
                'updated_at'    => now()->subMinutes(10 - $i),
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('activity', 5)
                ->where('activity.0.title', 'Judul 6'));
    }
}