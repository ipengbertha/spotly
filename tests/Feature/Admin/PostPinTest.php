<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPinTest extends TestCase
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

    private function pin(Post $post)
    {
        return $this->actingAs($this->admin)->post("/admin/posts/{$post->id}/pin");
    }

    public function test_admin_can_pin_up_to_three(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $this->pin($this->published($slug))->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Post::where('is_pinned', true)->count());
    }

    public function test_fourth_pin_is_rejected(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $this->pin($this->published($slug));
        }
        $fourth = $this->published('d');

        $this->pin($fourth)->assertSessionHasErrors('post');

        $this->assertFalse($fourth->fresh()->is_pinned);
    }

    public function test_unpin_frees_a_slot(): void
    {
        $first = $this->published('a');
        foreach ([$first, $this->published('b'), $this->published('c')] as $p) {
            $this->pin($p);
        }

        $this->actingAs($this->admin)->delete("/admin/posts/{$first->id}/pin");

        $fourth = $this->published('d');
        $this->pin($fourth)->assertSessionHasNoErrors();
        $this->assertTrue($fourth->fresh()->is_pinned);
    }

    public function test_cannot_pin_expired_post(): void
    {
        $post = $this->published('a');
        $post->forceFill(['expired_at' => now()->subDay()])->save();

        $this->pin($post)->assertSessionHasErrors('post');

        $this->assertFalse($post->fresh()->is_pinned);
    }

    public function test_expired_pins_do_not_use_slots(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            $old = $this->published($slug);
            $old->forceFill(['is_pinned' => true, 'expired_at' => now()->subDay()])->save();
        }

        $fresh = $this->published('d');
        $this->pin($fresh)->assertSessionHasNoErrors();

        $this->assertTrue($fresh->fresh()->is_pinned);
    }

    public function test_admin_can_update_expiry(): void
    {
        $post = $this->published('a');
        $newDate = now()->addDays(10)->toDateString();

        $this->actingAs($this->admin)
            ->patch("/admin/posts/{$post->id}/expiry", ['expired_at' => $newDate])
            ->assertSessionHasNoErrors();

        $this->assertSame($newDate, $post->fresh()->expired_at->toDateString());
    }

    public function test_expiry_rejects_past_date(): void
    {
        $post = $this->published('a');

        $this->actingAs($this->admin)
            ->patch("/admin/posts/{$post->id}/expiry", ['expired_at' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('expired_at');
    }

    public function test_regular_user_cannot_pin(): void
    {
        $post = $this->published('a');

        $this->actingAs($this->student)->post("/admin/posts/{$post->id}/pin");

        $this->assertFalse($post->fresh()->is_pinned);
    }
}