<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'user']);
    }

    private function makePost(string $status): Post
    {
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        return Post::factory()->create([
            'user_id'     => $this->student->id,
            'category_id' => $category->id,
            'status'      => $status,
        ]);
    }

    private function setPrefs(User $user, array $prefs): void
    {
        $user->forceFill(['notification_preferences' => $prefs])->save();
    }

    public function test_no_approved_notification_when_student_turned_it_off(): void
    {
        $this->setPrefs($this->student, ['post_approved' => false]);
        $post = $this->makePost('submitted');

        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/approve", [
            'expired_at' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame(0, $this->student->notifications()->count());
    }

    public function test_no_rejected_notification_when_student_turned_it_off(): void
    {
        $this->setPrefs($this->student, ['post_rejected' => false]);
        $post = $this->makePost('submitted');

        $this->actingAs($this->admin)->post("/admin/reviews/{$post->id}/reject", [
            'rejection_reason' => 'Gambar kurang jelas, mohon diganti.',
        ]);

        $this->assertSame('rejected', $post->fresh()->status);
        $this->assertSame(0, $this->student->notifications()->count());
    }

    public function test_only_admins_who_want_it_get_submitted_notification(): void
    {
        $other = User::factory()->create(['role' => 'admin']);
        $this->setPrefs($this->admin, ['post_submitted' => false]);
        $post = $this->makePost('draft');

        $this->actingAs($this->student)->post("/user/posts/{$post->id}/submit");

        $this->assertSame(0, $this->admin->notifications()->count());
        $this->assertSame(1, $other->notifications()->count());
    }

    public function test_student_can_save_preferences(): void
    {
        $this->actingAs($this->student)->put('/settings/notifications', [
            'post_approved'    => false,
            'post_rejected'    => true,
            'comment_received' => true,
        ])->assertSessionHasNoErrors();

        $fresh = $this->student->fresh();
        $this->assertFalse($fresh->wantsNotification('post_approved'));
        $this->assertTrue($fresh->wantsNotification('post_rejected'));
    }

    public function test_student_cannot_store_admin_preference_key(): void
    {
        $this->actingAs($this->student)->put('/settings/notifications', [
            'post_approved'    => true,
            'post_rejected'    => true,
            'comment_received' => true,
            'post_submitted'   => false,
        ]);

        $this->assertArrayNotHasKey('post_submitted', $this->student->fresh()->notification_preferences);
    }

    public function test_preferences_are_shown_in_settings_page(): void
    {
        $this->setPrefs($this->admin, ['post_submitted' => false]);

        $this->actingAs($this->admin)->get('/settings')
            ->assertInertia(fn ($page) => $page->where('preferences.post_submitted', false));
    }
}