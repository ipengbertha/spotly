<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_pratinjau_mengembalikan_html_yang_sudah_dibersihkan(): void
    {
        $res = $this->actingAs($this->student())
            ->postJson('/user/posts/preview', ['content' => '<p>Halo</p><script>alert(1)</script>'])
            ->assertOk();

        $this->assertStringContainsString('Halo', $res->json('html'));
        $this->assertStringNotContainsString('<script', $res->json('html'));
    }

    public function test_pratinjau_membuang_gambar_dari_situs_lain(): void
    {
        $res = $this->actingAs($this->student())
            ->postJson('/user/posts/preview', ['content' => '<p>Halo</p><img src="https://situs-lain.com/a.jpg">'])
            ->assertOk();

        $this->assertStringNotContainsString('<img', $res->json('html'));
    }

    public function test_pratinjau_teks_biasa_diberi_paragraf_dan_di_escape(): void
    {
        $res = $this->actingAs($this->student())
            ->postJson('/user/posts/preview', ['content' => 'a < b'])
            ->assertOk();

        $this->assertSame('<p>a &lt; b</p>', $res->json('html'));
    }

    public function test_pratinjau_kosong_mengembalikan_html_kosong(): void
    {
        $this->actingAs($this->student())
            ->postJson('/user/posts/preview', ['content' => ''])
            ->assertOk()
            ->assertJson(['html' => '']);
    }

    public function test_pratinjau_tidak_menyimpan_apa_apa(): void
    {
        $this->actingAs($this->student())
            ->postJson('/user/posts/preview', ['content' => '<p>Halo</p>'])
            ->assertOk();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_tamu_dan_admin_tidak_bisa_memakai_pratinjau(): void
    {
        $this->postJson('/user/posts/preview', ['content' => '<p>Halo</p>'])->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson('/user/posts/preview', ['content' => '<p>Halo</p>'])
            ->assertForbidden();
    }
}