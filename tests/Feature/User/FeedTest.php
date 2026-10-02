<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::unguarded(fn () => Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']));
    }

    private function makePost(User $author, Category $category, string $slug): Post
    {
        return Post::create([
            'user_id'     => $author->id,
            'category_id' => $category->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi postingan tes.',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/user/beranda')->assertRedirect('/login');
    }

    public function test_admin_can_open_feed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/user/beranda')->assertOk();
    }

    public function test_feed_shows_only_active_posts(): void
    {
        $user     = User::factory()->create(['role' => 'user']);
        $category = $this->category();

        $live = $this->makePost($user, $category, 'tayang');
        $live->markPublished(now()->addDays(3));

        $this->makePost($user, $category, 'menunggu')->markSubmitted();
        $this->makePost($user, $category, 'draft');

        $this->actingAs($user)->get('/user/beranda')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Beranda')
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $live->id));
    }

    public function test_posts_older_than_three_months_only_appear_when_searching(): void
    {
        $user     = User::factory()->create(['role' => 'user']);
        $category = $this->category();

        $old = $this->makePost($user, $category, 'lama');
        $old->forceFill([
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subMonths(4),
            'expired_at'   => now()->addDay(),
        ])->save();

        $this->actingAs($user)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));

        $this->actingAs($user)->get('/user/beranda?q=lama')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $old->id));
    }

    public function test_feed_is_paginated_by_twelve(): void
    {
        $user     = User::factory()->create(['role' => 'user']);
        $category = $this->category();

        foreach (range(1, 13) as $i) {
            $this->makePost($user, $category, "post-{$i}")->markPublished(now()->addDays(3));
        }

        $this->actingAs($user)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 12)
                ->where('posts.total', 13));

        $this->actingAs($user)->get('/user/beranda?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
    }
}