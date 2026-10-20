<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PostHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostMediaTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Pembersih HTML ----------

    public function test_gambar_dari_penyimpanan_sendiri_lolos(): void
    {
        $out = PostHtml::clean('<p>Halo</p><img src="/storage/post-images/abc.jpg">');
        $this->assertStringContainsString('src="/storage/post-images/abc.jpg"', $out);
    }

    public function test_artikel_yang_hanya_berisi_gambar_tetap_dibersihkan(): void
    {
        $out = PostHtml::clean('<img src="https://situs-lain.com/foto.jpg">');
        $this->assertStringNotContainsString('situs-lain.com', $out);
    }

    public function test_gambar_dari_situs_lain_dibuang(): void
    {
        $out = PostHtml::clean('<p>Halo</p><img src="https://situs-lain.com/foto.jpg">');
        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringContainsString('Halo', $out);
    }

    public function test_gambar_dengan_path_menyusup_dibuang(): void
    {
        $out = PostHtml::clean('<p>Halo</p><img src="/storage/post-images/../../.env">');
        $this->assertStringNotContainsString('<img', $out);
    }

    public function test_video_youtube_nocookie_lolos_dan_ditulis_ulang(): void
    {
        $out = PostHtml::clean('<div data-youtube-video><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" width="640" height="480"></iframe></div>');
        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $out);
        $this->assertStringContainsString('allowfullscreen', $out);
    }

    public function test_iframe_situs_lain_dibuang(): void
    {
        $out = PostHtml::clean('<p>Halo</p><iframe src="https://situs-jahat.com"></iframe>');
        $this->assertStringNotContainsString('iframe', $out);
    }

    public function test_iframe_dengan_domain_youtube_palsu_dibuang(): void
    {
        $out = PostHtml::clean('<p>Halo</p><iframe src="https://www.youtube-nocookie.com.situs-jahat.com/embed/dQw4w9WgXcQ"></iframe>');
        $this->assertStringNotContainsString('iframe', $out);
    }

    public function test_iframe_youtube_biasa_tanpa_nocookie_dibuang(): void
    {
        $out = PostHtml::clean('<p>Halo</p><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>');
        $this->assertStringNotContainsString('iframe', $out);
    }

    public function test_hasil_pembersihan_tetap_sama_kalau_disimpan_dua_kali(): void
    {
        $once = PostHtml::clean('<div data-youtube-video><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"></iframe></div><img src="/storage/post-images/abc.jpg">');
        $this->assertSame($once, PostHtml::clean($once));
    }

    // ---------- Unggah gambar ----------

    public function test_tamu_tidak_bisa_mengunggah(): void
    {
        $this->postJson('/user/post-images', ['image' => UploadedFile::fake()->image('a.jpg')])
            ->assertUnauthorized();
    }

    public function test_siswa_bisa_mengunggah_gambar(): void
    {
        Storage::fake('public');

        $res = $this->actingAs(User::factory()->create(['role' => 'user']))
            ->postJson('/user/post-images', ['image' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk();

        $url = $res->json('url');
        $this->assertStringStartsWith('/storage/post-images/', $url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));
    }

    public function test_file_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create(['role' => 'user']))
        ->postJson('/user/post-images', ['image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->assertStatus(422);
    }

    public function test_gambar_lebih_dari_2mb_ditolak(): void
    {
        Storage::fake('public');

       $this->actingAs(User::factory()->create(['role' => 'user']))
        ->postJson('/user/post-images', ['image' => UploadedFile::fake()->image('besar.jpg')->size(3000)])
        ->assertStatus(422);
        }
}