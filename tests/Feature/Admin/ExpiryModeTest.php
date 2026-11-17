<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpiryModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function makePost(string $mode, string $status = 'submitted', array $attrs = []): Post
    {
        $category = Category::create(['name' => 'Kat ' . $mode, 'slug' => 'kat-' . $mode, 'expiry_mode' => $mode]);

        return Post::factory()->create(array_merge([
            'user_id'      => User::factory()->create()->id,
            'category_id'  => $category->id,
            'status'       => $status,
            'published_at' => null,
            'expired_at'   => null,
        ], $attrs));
    }

    public function test_akhir_tahun_ajaran_dihitung_dari_bulan_juli(): void
    {
        $this->assertSame('2027-06-30', Category::schoolYearEnd(Carbon::parse('2026-11-16'))->toDateString());
        $this->assertSame('2027-06-30', Category::schoolYearEnd(Carbon::parse('2027-05-10'))->toDateString());
        $this->assertSame('2027-06-30', Category::schoolYearEnd(Carbon::parse('2027-06-30'))->toDateString());
        $this->assertSame('2028-06-30', Category::schoolYearEnd(Carbon::parse('2027-07-01'))->toDateString());
    }

    public function test_kategori_ada_batas_butuh_tanggal_atau_tanpa_batas(): void
    {
        $post = $this->makePost('dated');

        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$post->id}/approve", [])
            ->assertSessionHasErrors('expired_at');

        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$post->id}/approve", ['no_expiry' => true])
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNull($post->expired_at);
    }

    public function test_kategori_tanpa_batas_tidak_butuh_tanggal(): void
    {
        $post = $this->makePost('none');

        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$post->id}/approve", [])
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNull($post->expired_at);
    }

    public function test_kategori_tahun_ajaran_menghitung_tanggal_otomatis(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 10:00:00'));
        $post = $this->makePost('school_year');

        // Tanggal kiriman admin diabaikan
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$post->id}/approve", ['expired_at' => '2026-11-20'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-06-30', $post->fresh()->expired_at->toDateString());
    }

    public function test_menayangkan_ulang_mengikuti_aturan_kategori(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 10:00:00'));
        $post = $this->makePost('school_year', 'archived');

        $this->actingAs($this->admin)
            ->post("/admin/archive/{$post->id}/republish", [])
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertSame('2027-06-30', $post->expired_at->toDateString());
    }

    public function test_masa_tayang_kategori_otomatis_tidak_bisa_diubah(): void
    {
        $post = $this->makePost('none', 'published', ['published_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->patch("/admin/posts/{$post->id}/expiry", ['expired_at' => now()->addDays(5)->toDateString()])
            ->assertSessionHasErrors('post');

        $this->assertNull($post->fresh()->expired_at);
    }

    public function test_masa_tayang_bisa_diubah_jadi_tanpa_batas(): void
    {
        $post = $this->makePost('dated', 'published', [
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(3),
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/posts/{$post->id}/expiry", ['no_expiry' => true])
            ->assertSessionHasNoErrors();

        $this->assertNull($post->fresh()->expired_at);
    }

    public function test_buat_kategori_dengan_mode_batas_tayang(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/categories', ['name' => 'Berita Khusus', 'expiry_mode' => 'none'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Berita Khusus', 'expiry_mode' => 'none']);
    }

    public function test_mode_batas_tayang_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/categories', ['name' => 'Aneh', 'expiry_mode' => 'asal'])
            ->assertSessionHasErrors('expiry_mode');

        $this->assertDatabaseMissing('categories', ['name' => 'Aneh']);
    }

    public function test_ubah_mode_kategori_tercatat_di_log(): void
    {
        $category = Category::create(['name' => 'Prestasi Uji', 'slug' => 'prestasi-uji', 'expiry_mode' => 'dated']);

        $this->actingAs($this->admin)
            ->put("/admin/categories/{$category->id}", ['name' => 'Prestasi Uji', 'expiry_mode' => 'school_year'])
            ->assertSessionHasNoErrors();

        $this->assertSame('school_year', $category->fresh()->expiry_mode);
        $this->assertSame(['category.expiry_changed'], ActivityLog::pluck('action')->all());
    }
}