<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostShowTest extends TestCase
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
            'content'      => "Paragraf satu.\n\nParagraf dua.",
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(5),
        ], $attrs));
    }

    private function url(Post $post): string
    {
        return '/posts/' . $post->slug;
    }

    public function test_guest_can_view_active_post(): void
    {
        $post = $this->makePost();

        $this->get($this->url($post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->where('post.title', 'Judul Uji')
                ->where('post.is_active', true)
                ->where('viewer', 'guest'));
    }

    public function test_guest_cannot_view_posts_that_are_not_active(): void
    {
        $cases = [
            ['status' => Post::DRAFT,     'published_at' => null, 'expired_at' => null],
            ['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null],
            ['status' => Post::REJECTED,  'published_at' => null, 'expired_at' => null],
            ['status' => Post::ARCHIVED],
            ['status' => Post::PUBLISHED, 'expired_at' => now()->subDay()],
        ];

        foreach ($cases as $attrs) {
            $post = $this->makePost($attrs);
            $this->get($this->url($post))->assertNotFound();
        }
    }

    public function test_other_student_cannot_view_unpublished_post(): void
    {
        $post  = $this->makePost(['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]);
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($other)->get($this->url($post))->assertNotFound();
    }

    public function test_owner_can_view_own_draft_and_gets_edit_link(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $post  = $this->makePost([
            'user_id' => $owner->id, 'status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null,
        ]);

        $this->actingAs($owner)->get($this->url($post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('viewer', 'user')
                ->where('post.is_active', false)
                ->where('post.edit_url', "/user/posts/{$post->id}/edit"));
    }

    public function test_published_post_has_no_edit_link(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $post  = $this->makePost(['user_id' => $owner->id]);

        $this->actingAs($owner)->get($this->url($post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.edit_url', null));
    }

    public function test_admin_can_view_submitted_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post  = $this->makePost(['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null]);

        $this->actingAs($admin)->get($this->url($post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('viewer', 'admin')
                ->where('post.status', Post::SUBMITTED));
    }

    public function test_unknown_slug_returns_not_found(): void
    {
        $this->get('/posts/tidak-ada')->assertNotFound();
    }

    public function test_primary_and_extra_categories_are_listed(): void
    {
        $post  = $this->makePost();
        $extra = Category::forceCreate(['name' => 'Prestasi', 'slug' => 'prestasi']);
        $post->extraCategories()->attach($extra->id);

        $this->get($this->url($post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.categories', ['Pengumuman', 'Prestasi']));
    }
}