<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPostTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makePost(User $owner, string $status): Post
    {
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        return Post::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'status' => $status,
        ]);
    }

    public function test_new_post_is_always_draft_and_status_from_form_is_ignored(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $cat = Category::create(['name' => 'Umum', 'slug' => 'umum']);

        $this->actingAs($user)->post('/user/posts', [
            'title' => 'Judul',
            'content' => 'Isi',
            'category_id' => $cat->id,
            'status' => 'published', // percobaan curang
            'is_pinned' => true,
            'image' => UploadedFile::fake()->image('poster.jpg'),
        ])->assertRedirect('/user/dashboard');

        $post = Post::first();
        $this->assertSame('draft', $post->status);
        $this->assertFalse($post->is_pinned);
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_upload_rejects_wrong_type_and_big_file(): void
    {
        $user = $this->makeUser();
        $cat = Category::create(['name' => 'Umum', 'slug' => 'umum']);
        $base = ['title' => 'J', 'content' => 'I', 'category_id' => $cat->id];

        $this->actingAs($user)->post('/user/posts', $base + [
            'image' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->actingAs($user)->post('/user/posts', $base + [
            'image' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertSessionHasErrors('image');
    }

    public function test_submit_draft_becomes_submitted(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user, 'draft');

        $this->actingAs($user)->post("/user/posts/{$post->id}/submit");

        $this->assertSame('submitted', $post->fresh()->status);
    }

    public function test_rejected_post_can_be_resubmitted_and_reason_cleared(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user, 'rejected');
        $post->forceFill(['rejection_reason' => 'Kurang jelas'])->save();

        $this->actingAs($user)->post("/user/posts/{$post->id}/submit");

        $fresh = $post->fresh();
        $this->assertSame('submitted', $fresh->status);
        $this->assertNull($fresh->rejection_reason);
    }

    public function test_cannot_edit_or_submit_published_or_submitted_post(): void
    {
        $user = $this->makeUser();

        foreach (['submitted', 'published', 'archived'] as $status) {
            $post = $this->makePost($user, $status);
            $this->actingAs($user)->get("/user/posts/{$post->id}/edit")->assertForbidden();
            $this->actingAs($user)->post("/user/posts/{$post->id}/submit")->assertForbidden();
        }
    }

    public function test_cannot_touch_other_users_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner, 'draft');

        $this->actingAs($other)->get("/user/posts/{$post->id}/edit")->assertForbidden();
        $this->actingAs($other)->post("/user/posts/{$post->id}/submit")->assertForbidden();
        $this->actingAs($other)->delete("/user/posts/{$post->id}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_owner_can_delete_draft_rejected_published_archived(): void
    {
        $user = $this->makeUser();

        foreach (['draft', 'rejected', 'published', 'archived'] as $status) {
            $post = $this->makePost($user, $status);
            $this->actingAs($user)->delete("/user/posts/{$post->id}")->assertRedirect();
            $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        }
    }

    public function test_cannot_delete_submitted_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user, 'submitted');

        $this->actingAs($user)->delete("/user/posts/{$post->id}")->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}