<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScheduledManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function buatPost(string $status, array $tanggal = []): Post
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

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
            'status'      => $status,
        ], $tanggal));
    }

    private function terjadwal(): Post
    {
        return $this->buatPost(Post::PUBLISHED, [
            'published_at' => now()->addDays(3),
            'expired_at'   => now()->addDays(10),
        ]);
    }

    private function tayang(): Post
    {
        return $this->buatPost(Post::PUBLISHED, [
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ]);
    }

    public function test_daftar_terjadwal_terpisah_dari_yang_tayang(): void
    {
        $this->tayang();
        $jadwal = $this->terjadwal();

        $this->actingAs($this->admin())
            ->get('/admin/posts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Posts/Index')
                ->has('posts.data', 1)
                ->has('scheduled', 1)
                ->where('scheduled.0.id', $jadwal->id)
                ->where('scheduled.0.title', $jadwal->title));
    }

    public function test_batalkan_jadwal_mengembalikan_ke_antrean_review(): void
    {
        $post = $this->terjadwal();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/posts/{$post->id}/unschedule")
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame(Post::SUBMITTED, $post->status);
        $this->assertNull($post->published_at);
        $this->assertNull($post->expired_at);
        $this->assertFalse($post->isScheduled());

        $this->actingAs($admin)
            ->get('/admin/reviews')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }

    public function test_postingan_yang_sudah_tayang_tidak_bisa_dibatalkan_jadwalnya(): void
    {
        $post = $this->tayang();

        $this->actingAs($this->admin())
            ->post("/admin/posts/{$post->id}/unschedule")
            ->assertSessionHasErrors('post');

        $this->assertSame(Post::PUBLISHED, $post->refresh()->status);
        $this->assertTrue($post->isActive());
    }

    public function test_siswa_tidak_bisa_membatalkan_jadwal(): void
    {
        $post = $this->terjadwal();
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->actingAs($siswa)
            ->post("/admin/posts/{$post->id}/unschedule")
            ->assertForbidden();

        $this->assertTrue($post->refresh()->isScheduled());
    }

    public function test_postingan_terjadwal_tidak_bisa_di_pin(): void
    {
        $post = $this->terjadwal();

        $this->actingAs($this->admin())
            ->post("/admin/posts/{$post->id}/pin")
            ->assertSessionHasErrors('post');

        $this->assertFalse((bool) $post->refresh()->is_pinned);
    }

    public function test_notifikasi_menyebut_tanggal_kalau_dijadwalkan(): void
    {
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(2)->toDateString(),
                'expired_at' => now()->addDays(9)->toDateString(),
            ]);

        $notif = $post->user->notifications()->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('akan tayang', $notif->data['message']);
        $this->assertStringContainsString('dijadwalkan', $notif->data['title']);
    }

    public function test_notifikasi_biasa_tetap_menyebut_sudah_tayang(): void
    {
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'expired_at' => now()->addDays(9)->toDateString(),
            ]);

        $notif = $post->user->notifications()->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('sudah tayang', $notif->data['message']);
    }
        public function test_batalkan_jadwal_mengirim_notifikasi_ke_penulis(): void
    {
        $post = $this->terjadwal();
        $tanggal = $post->published_at->translatedFormat('d M Y');

        $this->actingAs($this->admin())
            ->post("/admin/posts/{$post->id}/unschedule")
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $post->user->notifications()->count());

        $notif = $post->user->notifications()->first();

        $this->assertSame('unscheduled', $notif->data['type']);
        $this->assertSame($post->id, $notif->data['post_id']);
        $this->assertSame('Jadwal tayang dibatalkan', $notif->data['title']);
        $this->assertStringContainsString($post->title, $notif->data['message']);
        $this->assertStringContainsString($tanggal, $notif->data['message']);
        $this->assertSame('/user/posts', $notif->data['url']);
    }

    public function test_batalkan_jadwal_yang_gagal_tidak_mengirim_notifikasi(): void
    {
        $post = $this->tayang(); // sudah tayang, bukan terjadwal

        $this->actingAs($this->admin())
            ->post("/admin/posts/{$post->id}/unschedule")
            ->assertSessionHasErrors('post');

        $this->assertSame(0, $post->user->notifications()->count());
    }
}