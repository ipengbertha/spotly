<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserScheduledListTest extends TestCase
{
    use RefreshDatabase;

    private function buatPostSiswa(User $siswa, array $tanggal): Post
    {
        $data = ['name' => 'Berita ' . Str::random(4)];
        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = 'berita-' . Str::random(6);
        }
        $kategori = Category::forceCreate($data);

        return Post::forceCreate(array_merge([
            'user_id'     => $siswa->id,
            'category_id' => $kategori->id,
            'title'       => 'Judul ' . Str::random(6),
            'slug'        => Str::slug('judul-' . Str::random(8)),
            'content'     => '<p>isi artikel</p>',
            'status'      => Post::PUBLISHED,
        ], $tanggal));
    }

    public function test_siswa_melihat_status_terjadwal_di_daftar_kirimannya(): void
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->buatPostSiswa($siswa, [
            'published_at' => now()->addDays(3),
            'expired_at'   => now()->addDays(10),
        ]);
        $this->buatPostSiswa($siswa, [
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ]);

        $this->actingAs($siswa)
            ->get('/user/posts')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('User/Posts/Index')->has('posts', 2);

                $status = collect($page->toArray()['props']['posts'])->pluck('display_status')->sort()->values()->all();
                $this->assertSame(['published', 'scheduled'], $status);
            });
    }

    public function test_tanggal_tayang_hanya_dikirim_untuk_postingan_terjadwal(): void
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $mulai = now()->addDays(3);

        $this->buatPostSiswa($siswa, [
            'published_at' => $mulai,
            'expired_at'   => now()->addDays(10),
        ]);
        $this->buatPostSiswa($siswa, [
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ]);

        $this->actingAs($siswa)
            ->get('/user/posts')
            ->assertInertia(function (Assert $page) use ($mulai) {
                $posts = collect($page->toArray()['props']['posts']);

                $jadwal = $posts->firstWhere('display_status', 'scheduled');
                $tayang = $posts->firstWhere('display_status', 'published');

                $this->assertSame($mulai->translatedFormat('d M Y'), $jadwal['publish_label']);
                $this->assertNull($tayang['publish_label']);
            });
    }
}