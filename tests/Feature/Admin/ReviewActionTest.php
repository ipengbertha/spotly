<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewActionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'user']);
        $category = Category::unguarded(fn () => Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']));

        $this->post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $category->id,
            'title'       => 'Judul Tes',
            'slug'        => 'judul-tes',
            'content'     => 'Isi postingan tes.',
        ]);
        $this->post->markSubmitted();
    }

    public function test_admin_can_approve_with_expiry(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/approve", [
                'expired_at' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect(route('admin.reviews.index'));

        $post = $this->post->fresh();
        $this->assertSame(Post::PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->expired_at);
    }

    public function test_approve_requires_expiry_date(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/approve", [])
            ->assertSessionHasErrors('expired_at');

        $this->assertSame(Post::SUBMITTED, $this->post->fresh()->status);
    }

    public function test_approve_rejects_past_date(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/approve", [
                'expired_at' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('expired_at');
    }

    public function test_admin_can_reject_with_reason(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/reject", [
                'rejection_reason' => 'Gambar kurang jelas, mohon diganti.',
            ])
            ->assertRedirect(route('admin.reviews.index'));

        $post = $this->post->fresh();
        $this->assertSame(Post::REJECTED, $post->status);
        $this->assertSame('Gambar kurang jelas, mohon diganti.', $post->rejection_reason);
    }

    public function test_reject_requires_reason(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/reject", ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(Post::SUBMITTED, $this->post->fresh()->status);
    }

    public function test_only_submitted_posts_can_be_processed(): void
    {
        $this->post->markPublished(now()->addDays(3));

        $this->actingAs($this->admin)
            ->post("/admin/reviews/{$this->post->id}/reject", [
                'rejection_reason' => 'Alasan yang cukup panjang.',
            ])
            ->assertSessionHasErrors('post');

        $this->assertSame(Post::PUBLISHED, $this->post->fresh()->status);
    }

        public function test_regular_user_cannot_approve(): void
    {
        $this->actingAs($this->student)
            ->post("/admin/reviews/{$this->post->id}/approve", [
                'expired_at' => now()->addDays(7)->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(Post::SUBMITTED, $this->post->fresh()->status);
    }
}