<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduledPublishTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function diajukan(): Post
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $data = ['name' => 'Berita'];
        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = 'berita-' . Str::random(4);
        }
        $kategori = Category::forceCreate($data);

        return Post::forceCreate([
            'user_id'     => $siswa->id,
            'category_id' => $kategori->id,
            'title'       => 'Judul ' . Str::random(6),
            'slug'        => Str::slug('judul-' . Str::random(8)),
            'content'     => '<p>isi artikel</p>',
            'status'      => Post::SUBMITTED,
        ]);
    }

    public function test_tanpa_tanggal_mulai_langsung_tayang(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", ['expired_at' => now()->addDays(7)->toDateString()])
            ->assertRedirect();

        $post->refresh();
        $this->assertSame(Post::PUBLISHED, $post->status);
        $this->assertTrue($post->isActive());
        $this->assertFalse($post->isScheduled());
        $this->assertSame(Post::PUBLISHED, $post->displayStatus());
    }

    public function test_tanggal_mulai_hari_ini_juga_langsung_tayang(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->toDateString(),
                'expired_at' => now()->addDays(7)->toDateString(),
            ]);

        $this->assertTrue($post->refresh()->isActive());
    }

    public function test_tanggal_mulai_depan_membuat_postingan_terjadwal(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(2)->toDateString(),
                'expired_at' => now()->addDays(9)->toDateString(),
            ])
            ->assertRedirect();

        $post->refresh();
        $this->assertSame(Post::PUBLISHED, $post->status);
        $this->assertTrue($post->isScheduled());
        $this->assertFalse($post->isActive());
        $this->assertSame(Post::SCHEDULED, $post->displayStatus());
        $this->assertSame(0, Post::active()->count());
        $this->assertSame(1, Post::scheduled()->count());
    }

    public function test_postingan_terjadwal_tidak_bisa_dibuka_tamu(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(2)->toDateString(),
                'expired_at' => now()->addDays(9)->toDateString(),
            ]);

        auth()->logout();

        $this->get("/posts/{$post->slug}")->assertNotFound();
    }

    public function test_postingan_terjadwal_tayang_sendiri_saat_waktunya_tiba(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(2)->toDateString(),
                'expired_at' => now()->addDays(9)->toDateString(),
            ]);

        $this->travelTo(now()->addDays(3));

        $post->refresh();
        $this->assertTrue($post->isActive());
        $this->assertFalse($post->isScheduled());
        $this->assertSame(1, Post::active()->count());
        $this->assertSame(0, Post::scheduled()->count());
    }

    public function test_tanggal_mulai_tidak_boleh_lewat(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->subDay()->toDateString(),
                'expired_at' => now()->addDays(7)->toDateString(),
            ])
            ->assertSessionHasErrors('publish_at');

        $this->assertSame(Post::SUBMITTED, $post->refresh()->status);
    }

    public function test_akhir_tayang_tidak_boleh_sebelum_mulai_tayang(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(5)->toDateString(),
                'expired_at' => now()->addDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('expired_at');

        $this->assertSame(Post::SUBMITTED, $post->refresh()->status);
    }

    public function test_akhir_tayang_sama_dengan_mulai_tayang_diperbolehkan(): void
    {
        $post = $this->diajukan();
        $hari = now()->addDays(4)->toDateString();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", ['publish_at' => $hari, 'expired_at' => $hari])
            ->assertSessionHasNoErrors();

        $this->assertTrue($post->refresh()->isScheduled());
    }

    public function test_postingan_terjadwal_tidak_masuk_arsip(): void
    {
        $post = $this->diajukan();

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/approve", [
                'publish_at' => now()->addDays(2)->toDateString(),
                'expired_at' => now()->addDays(9)->toDateString(),
            ]);

        $this->assertSame(0, Post::archive()->count());
        $this->assertFalse($post->refresh()->isArchived());
    }
}