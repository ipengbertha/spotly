<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function makePost(string $slug): Post
    {
        return Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi tes.',
        ]);
    }

    public function test_student_is_notified_when_post_is_approved(): void
    {
        $post = $this->makePost('a');
        $post->markSubmitted();

        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/approve", [
            'expired_at' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertSame(1, $this->student->notifications()->count());
        $this->assertSame('approved', $this->student->notifications()->first()->data['type']);
    }

    public function test_student_is_notified_when_post_is_rejected(): void
    {
        $post = $this->makePost('a');
        $post->markSubmitted();

        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/reject", [
            'rejection_reason' => 'Gambar kurang jelas, mohon diganti.',
        ]);

        $this->assertSame('rejected', $this->student->notifications()->first()->data['type']);
    }

    public function test_admin_is_notified_when_student_submits(): void
    {
        $this->withoutExceptionHandling();

        $post = $this->makePost('a');

        $this->actingAs($this->student)->post("/user/posts/{$post->id}/submit");

        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame('submitted', $this->admin->notifications()->first()->data['type']);
    }

    public function test_unread_count_is_shared_and_list_shown(): void
    {
        $post = $this->makePost('a');
        $post->markSubmitted();
        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/reject", [
            'rejection_reason' => 'Gambar kurang jelas, mohon diganti.',
        ]);

        $this->actingAs($this->student)
            ->get('/notifications')
            ->assertInertia(fn ($page) => $page
                ->where('unreadNotifications', 1)
                ->has('notifications.data', 1));
    }

    public function test_opening_notification_marks_it_read_and_redirects(): void
    {
        $post = $this->makePost('a');
        $post->markSubmitted();
        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/reject", [
            'rejection_reason' => 'Gambar kurang jelas, mohon diganti.',
        ]);
        $notification = $this->student->notifications()->first();

        $this->actingAs($this->student)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect('/user/posts');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_open_other_users_notification(): void
    {
        $post = $this->makePost('a');
        $this->actingAs($this->student)->post("/user/posts/{$post->id}/submit");
        $adminNotification = $this->admin->notifications()->first();

        $this->actingAs($this->student)
            ->post("/notifications/{$adminNotification->id}/read")
            ->assertNotFound();

        $this->assertNull($adminNotification->fresh()->read_at);
    }

    public function test_read_all_marks_everything_read(): void
    {
        foreach (['a', 'b'] as $slug) {
            $post = $this->makePost($slug);
            $this->actingAs($this->student)->post("/user/posts/{$post->id}/submit");
        }
        $this->assertSame(2, $this->admin->unreadNotifications()->count());

        $this->actingAs($this->admin)->post('/notifications/read-all');

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }
}