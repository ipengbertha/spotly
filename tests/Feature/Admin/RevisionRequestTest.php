<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostRevisionRequested;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RevisionRequestTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Kategori Uji', 'slug' => 'kategori-uji']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makePost(User $owner, string $status): Post
    {
        $post = $owner->posts()->create([
            'category_id' => $this->category->id,
            'title'       => 'Judul uji',
            'content'     => '<p>Isi uji</p>',
            'slug'        => 'judul-uji-' . Str::random(5),
        ]);
        $post->forceFill(['status' => $status])->save();

        return $post;
    }

    public function test_admin_bisa_meminta_revisi_dengan_catatan(): void
    {
        $post = $this->makePost($this->student(), 'submitted');

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/revision", ['revision_note' => 'Tolong tambahkan foto kegiatan.'])
            ->assertRedirect();

        $post->refresh();
        $this->assertSame('revision', $post->status);
        $this->assertSame('Tolong tambahkan foto kegiatan.', $post->rejection_reason);
    }

    public function test_catatan_revisi_wajib_dan_minimal_10_karakter(): void
    {
        $post = $this->makePost($this->student(), 'submitted');
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/reviews/{$post->id}/revision", [])
            ->assertSessionHasErrors('revision_note');
        $this->actingAs($admin)->post("/admin/reviews/{$post->id}/revision", ['revision_note' => 'pendek'])
            ->assertSessionHasErrors('revision_note');

        $this->assertSame('submitted', $post->fresh()->status);
    }

    public function test_hanya_postingan_diajukan_yang_bisa_diminta_revisi(): void
    {
        $post = $this->makePost($this->student(), 'published');

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/revision", ['revision_note' => 'Catatan cukup panjang.'])
            ->assertSessionHasErrors('post');

        $this->assertSame('published', $post->fresh()->status);
    }

    public function test_siswa_tidak_bisa_meminta_revisi(): void
    {
        $post = $this->makePost($this->student(), 'submitted');

        $this->actingAs($this->student())
            ->post("/admin/reviews/{$post->id}/revision", ['revision_note' => 'Catatan cukup panjang.'])
            ->assertForbidden();
    }

    public function test_penulis_bisa_mengedit_dan_mengirim_ulang_postingan_perlu_revisi(): void
    {
        $owner = $this->student();
        $post = $this->makePost($owner, 'revision');
        $post->forceFill(['rejection_reason' => 'Tolong perbaiki isinya.'])->save();

        $this->actingAs($owner)->get("/user/posts/{$post->id}/edit")->assertOk();

        $this->actingAs($owner)->post("/user/posts/{$post->id}/submit")->assertRedirect();

        $post->refresh();
        $this->assertSame('submitted', $post->status);
        $this->assertNull($post->rejection_reason);
    }

    public function test_postingan_ditolak_bersifat_final(): void
    {
        $owner = $this->student();
        $post = $this->makePost($owner, 'rejected');

        $this->actingAs($owner)->get("/user/posts/{$post->id}/edit")->assertForbidden();
        $this->actingAs($owner)->post("/user/posts/{$post->id}/submit")->assertForbidden();
        $this->assertSame('rejected', $post->fresh()->status);

        // Tetap boleh dihapus
        $this->actingAs($owner)->delete("/user/posts/{$post->id}")->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

        public function test_penulis_diberi_tahu_saat_diminta_revisi(): void
    {
        Notification::fake();
        $owner = $this->student();
        $post = $this->makePost($owner, 'submitted');

        $this->actingAs($this->admin())
            ->post("/admin/reviews/{$post->id}/revision", ['revision_note' => 'Tolong tambahkan foto kegiatan.'])
            ->assertRedirect();

        Notification::assertSentTo($owner, PostRevisionRequested::class);
    }
}