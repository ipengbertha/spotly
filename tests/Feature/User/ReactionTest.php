<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attrs = []): Post
    {
        $category = Category::query()->first()
            ?? Category::forceCreate(['name' => 'Pengumuman', 'slug' => 'pengumuman']);

        if (! array_key_exists('user_id', $attrs)) {
            $attrs['user_id'] = User::factory()->create(['role' => 'user'])->id;
        }

        return Post::forceCreate(array_merge([
            'category_id'  => $category->id,
            'title'        => 'Judul Uji',
            'slug'         => 'judul-uji-' . uniqid(),
            'content'      => 'Isi postingan tes.',
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(5),
        ], $attrs));
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_student_can_react(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'fire'])->assertRedirect();

        $this->assertDatabaseHas('post_reactions', ['post_id' => $post->id, 'user_id' => $user->id, 'type' => 'fire']);
    }

    public function test_pressing_another_emoji_replaces_the_reaction(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'fire']);
        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'clap']);

        $this->assertSame(1, PostReaction::where('post_id', $post->id)->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('post_reactions', ['post_id' => $post->id, 'user_id' => $user->id, 'type' => 'clap']);
    }

    public function test_pressing_the_same_emoji_removes_the_reaction(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'fire']);
        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'fire']);

        $this->assertSame(0, PostReaction::count());
    }

    public function test_unknown_emoji_type_is_rejected(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'poop'])
            ->assertSessionHasErrors('type');
        $this->actingAs($user)->post("/posts/{$post->id}/reaction")
            ->assertSessionHasErrors('type');

        $this->assertSame(0, PostReaction::count());
    }

    public function test_posts_that_are_not_active_cannot_be_reacted_to(): void
    {
        $user = $this->student();

        $cases = [
            ['status' => Post::DRAFT,     'published_at' => null, 'expired_at' => null],
            ['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null],
            ['status' => Post::REJECTED,  'published_at' => null, 'expired_at' => null],
            ['status' => Post::ARCHIVED],
            ['published_at' => now()->subDays(10), 'expired_at' => now()->subDay()],
        ];

        foreach ($cases as $attrs) {
            $post = $this->makePost($attrs);
            $this->actingAs($user)->post("/posts/{$post->id}/reaction", ['type' => 'fire'])->assertNotFound();
        }

        $this->assertSame(0, PostReaction::count());
    }

    public function test_guest_is_redirected_and_admin_is_forbidden(): void
    {
        $post  = $this->makePost();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post("/posts/{$post->id}/reaction", ['type' => 'fire'])->assertRedirect('/login');
        $this->actingAs($admin)->post("/posts/{$post->id}/reaction", ['type' => 'fire'])->assertForbidden();

        $this->assertSame(0, PostReaction::count());
    }

    public function test_detail_page_shows_counts_and_my_reaction(): void
    {
        $me   = $this->student();
        $post = $this->makePost();

        PostReaction::create(['post_id' => $post->id, 'user_id' => $me->id, 'type' => 'fire']);
        PostReaction::create(['post_id' => $post->id, 'user_id' => $this->student()->id, 'type' => 'fire']);
        PostReaction::create(['post_id' => $post->id, 'user_id' => $this->student()->id, 'type' => 'clap']);

        $this->actingAs($me)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->has('post.reactions', 5)
                ->where('post.my_reaction', 'fire')
                ->where('post.reactions.1.type', 'clap')
                ->where('post.reactions.1.count', 1)
                ->where('post.reactions.4.type', 'fire')
                ->where('post.reactions.4.count', 2));
    }

    public function test_guest_sees_counts_but_has_no_reaction(): void
    {
        $post = $this->makePost();
        PostReaction::create(['post_id' => $post->id, 'user_id' => $this->student()->id, 'type' => 'wow']);

        $this->get('/posts/' . $post->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.my_reaction', null)
                ->where('post.reactions.3.count', 1));
    }

    public function test_deleting_a_post_removes_its_reactions(): void
    {
        $post = $this->makePost();
        PostReaction::create(['post_id' => $post->id, 'user_id' => $this->student()->id, 'type' => 'love']);

        $post->delete();

        $this->assertSame(0, PostReaction::count());
    }
}