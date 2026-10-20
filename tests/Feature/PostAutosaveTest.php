<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostAutosaveTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Kategori Uji', 'slug' => 'kategori-uji']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makePost(User $owner, string $status = 'draft'): Post
    {
        $post = $owner->posts()->create([
            'category_id' => $this->category->id,
            'title'       => 'Judul lama',
            'content'     => '<p>Isi lama</p>',
            'slug'        => 'judul-lama-' . Str::random(5),
        ]);
        $post->forceFill(['status' => $status])->save();

        return $post;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title'       => 'Judul baru',
            'content'     => '<p>Isi baru</p>',
            'category_id' => $this->category->id,
        ], $extra);
    }

    public function test_autosave_membuat_draf_baru(): void
    {
        $user = $this->student();

        $res = $this->actingAs($user)->postJson('/user/posts/autosave', $this->payload())->assertOk();

        $this->assertDatabaseHas('posts', [
            'id' => $res->json('id'), 'user_id' => $user->id, 'title' => 'Judul baru', 'status' => 'draft',
        ]);
    }

    public function test_autosave_memperbarui_draf_yang_sama_tanpa_membuat_baru(): void
    {
        $user = $this->student();
        $post = $this->makePost($user);

        $this->actingAs($user)
            ->postJson('/user/posts/autosave', $this->payload(['post_id' => $post->id]))
            ->assertOk()
            ->assertJson(['id' => $post->id]);

        $this->assertSame(1, Post::count());
        $this->assertSame('Judul baru', $post->fresh()->title);
        $this->assertSame('draft', $post->fresh()->status);
    }

    public function test_autosave_membersihkan_html_berbahaya(): void
    {
        $res = $this->actingAs($this->student())
            ->postJson('/user/posts/autosave', $this->payload(['content' => '<p>Halo</p><script>alert(1)</script>']))
            ->assertOk();

        $this->assertStringNotContainsString('<script', Post::find($res->json('id'))->content);
    }

    public function test_autosave_tidak_bisa_mengubah_postingan_terbit(): void
    {
        $user = $this->student();
        $post = $this->makePost($user, 'published');

        $this->actingAs($user)
            ->postJson('/user/posts/autosave', $this->payload(['post_id' => $post->id]))
            ->assertForbidden();

        $this->assertSame('Judul lama', $post->fresh()->title);
    }

    public function test_autosave_tidak_bisa_mengubah_postingan_orang_lain(): void
    {
        $post = $this->makePost($this->student());

        $this->actingAs($this->student())
            ->postJson('/user/posts/autosave', $this->payload(['post_id' => $post->id]))
            ->assertForbidden();

        $this->assertSame('Judul lama', $post->fresh()->title);
    }

    public function test_autosave_menolak_data_tidak_valid(): void
    {
        $this->actingAs($this->student())
            ->postJson('/user/posts/autosave', $this->payload(['title' => '']))
            ->assertStatus(422);
    }

    public function test_tamu_dan_admin_tidak_bisa_autosave(): void
    {
        $this->postJson('/user/posts/autosave', $this->payload())->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson('/user/posts/autosave', $this->payload())
            ->assertForbidden();
    }
}