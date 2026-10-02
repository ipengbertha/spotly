<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->student  = User::factory()->create(['role' => 'user']);
        $this->category = Category::unguarded(fn () => Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']));
    }

    private function published(string $slug): Post
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi tes.',
        ]);
        $post->markSubmitted();
        $post->markPublished(now()->addDays(5));

        return $post;
    }

    public function test_admin_can_archive_active_post(): void
    {
        $post = $this->published('a');

        $this->actingAs($this->admin)
            ->post("/admin/posts/{$post->id}/archive")
            ->assertSessionHasNoErrors();

        $this->assertSame(Post::ARCHIVED, $post->fresh()->status);
        $this->assertFalse($post->fresh()->isActive());
    }

    public function test_archiving_clears_pin(): void
    {
        $post = $this->published('a');
        $post->setPinned(true);

        $this->actingAs($this->admin)->post("/admin/posts/{$post->id}/archive");

        $this->assertFalse($post->fresh()->is_pinned);
    }

    public function test_cannot_archive_submitted_post(): void
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => 'Menunggu',
            'slug'        => 'menunggu',
            'content'     => 'Isi.',
        ]);
        $post->markSubmitted();

        $this->actingAs($this->admin)
            ->post("/admin/posts/{$post->id}/archive")
            ->assertSessionHasErrors('post');

        $this->assertSame(Post::SUBMITTED, $post->fresh()->status);
    }

    public function test_admin_can_republish_archived_post(): void
    {
        $post = $this->published('a');
        $post->markArchived();
        $newDate = now()->addDays(10)->toDateString();

        $this->actingAs($this->admin)
            ->post("/admin/archive/{$post->id}/republish", ['expired_at' => $newDate])
            ->assertSessionHasNoErrors();

        $fresh = $post->fresh();
        $this->assertSame(Post::PUBLISHED, $fresh->status);
        $this->assertSame($newDate, $fresh->expired_at->toDateString());
        $this->assertFalse($fresh->is_pinned);
        $this->assertTrue($fresh->isActive());
    }

    public function test_republish_works_for_expired_published_post(): void
    {
        $post = $this->published('a');
        $post->forceFill(['expired_at' => now()->subDay()])->save();
        $newDate = now()->addDays(3)->toDateString();

        $this->actingAs($this->admin)
            ->post("/admin/archive/{$post->id}/republish", ['expired_at' => $newDate])
            ->assertSessionHasNoErrors();

        $this->assertTrue($post->fresh()->isActive());
    }

    public function test_republish_rejects_past_date(): void
    {
        $post = $this->published('a');
        $post->markArchived();

        $this->actingAs($this->admin)
            ->post("/admin/archive/{$post->id}/republish", ['expired_at' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('expired_at');

        $this->assertSame(Post::ARCHIVED, $post->fresh()->status);
    }

    public function test_cannot_republish_active_post(): void
    {
        $post = $this->published('a');

        $this->actingAs($this->admin)
            ->post("/admin/archive/{$post->id}/republish", ['expired_at' => now()->addDays(3)->toDateString()])
            ->assertSessionHasErrors('post');
    }

    public function test_admin_can_delete_active_post_and_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('posts/foto.jpg', 'isi');

        $post = $this->published('a');
        $post->forceFill(['image' => 'posts/foto.jpg'])->save();

        $this->actingAs($this->admin)
            ->delete("/admin/posts/{$post->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('posts/foto.jpg');
    }

    public function test_cannot_delete_submitted_post(): void
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => 'Menunggu',
            'slug'        => 'menunggu',
            'content'     => 'Isi.',
        ]);
        $post->markSubmitted();

        $this->actingAs($this->admin)
            ->delete("/admin/posts/{$post->id}")
            ->assertSessionHasErrors('post');

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_regular_user_cannot_archive_or_delete(): void
    {
        $post = $this->published('a');

        $this->actingAs($this->student)->post("/admin/posts/{$post->id}/archive");
        $this->actingAs($this->student)->delete("/admin/posts/{$post->id}");

        $this->assertSame(Post::PUBLISHED, $post->fresh()->status);
    }

    public function test_archive_search_filters_by_title(): void
    {
        $a = $this->published('a');
        $a->markArchived();
        $b = $this->published('b');
        $b->markArchived();

        $this->actingAs($this->admin)
            ->get('/admin/archive?q=Judul a')
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));
    }
}