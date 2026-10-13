<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LikeTest extends TestCase
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

    public function test_student_can_like_an_active_post(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/like")->assertRedirect();

        $this->assertDatabaseHas('likes', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_second_press_removes_the_like(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/like");
        $this->actingAs($user)->post("/posts/{$post->id}/like");

        $this->assertDatabaseMissing('likes', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_one_student_never_has_more_than_one_like_per_post(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        // like, batal, like lagi: tetap paling banyak satu baris
        $this->actingAs($user)->post("/posts/{$post->id}/like");
        $this->actingAs($user)->post("/posts/{$post->id}/like");
        $this->actingAs($user)->post("/posts/{$post->id}/like");

        $this->assertSame(1, Like::where('post_id', $post->id)->where('user_id', $user->id)->count());
    }

    public function test_likes_from_different_students_are_counted_separately(): void
    {
        $post = $this->makePost();
        $a = $this->student();
        $b = $this->student();

        $this->actingAs($a)->post("/posts/{$post->id}/like");
        $this->actingAs($b)->post("/posts/{$post->id}/like");

        $this->assertSame(2, $post->likes()->count());

        // batal suka milik A tidak menghapus milik B
        $this->actingAs($a)->post("/posts/{$post->id}/like");
        $this->assertSame(1, $post->likes()->count());
        $this->assertDatabaseHas('likes', ['post_id' => $post->id, 'user_id' => $b->id]);
    }

    public function test_posts_that_are_not_active_cannot_be_liked(): void
    {
        $user = $this->student();

        $cases = [
            ['status' => Post::DRAFT,     'published_at' => null, 'expired_at' => null],
            ['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null],
            ['status' => Post::REJECTED,  'published_at' => null, 'expired_at' => null],
            ['status' => Post::ARCHIVED],
            ['published_at' => now()->subDays(10), 'expired_at' => now()->subDay()], // kedaluwarsa
        ];

        foreach ($cases as $attrs) {
            $post = $this->makePost($attrs);

            $this->actingAs($user)->post("/posts/{$post->id}/like")->assertNotFound();
        }

        $this->assertSame(0, Like::count());
    }

    public function test_guest_is_redirected_to_login_and_nothing_is_saved(): void
    {
        $post = $this->makePost();

        $this->post("/posts/{$post->id}/like")->assertRedirect('/login');

        $this->assertSame(0, Like::count());
    }

    public function test_admin_cannot_like(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post  = $this->makePost();

        $this->actingAs($admin)->post("/posts/{$post->id}/like")->assertForbidden();

        $this->assertSame(0, Like::count());
    }

    public function test_feed_shows_like_count_and_whether_viewer_liked(): void
    {
        $viewer = $this->student();
        $other  = $this->student();
        $post   = $this->makePost();

        Like::create(['post_id' => $post->id, 'user_id' => $other->id]);

        $this->actingAs($viewer)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.likes_count', 1)
                ->where('posts.data.0.liked', false));

        Like::create(['post_id' => $post->id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.likes_count', 2)
                ->where('posts.data.0.liked', true));
    }

    public function test_guest_sees_count_on_detail_page_but_cannot_like(): void
    {
        $post = $this->makePost();
        Like::create(['post_id' => $post->id, 'user_id' => $this->student()->id]);
        Like::create(['post_id' => $post->id, 'user_id' => $this->student()->id]);

        $this->get('/posts/' . $post->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.likes_count', 2)
                ->where('post.liked', false)
                ->where('post.can_like', false));
    }

    public function test_detail_page_tells_student_whether_they_liked_and_can_like(): void
    {
        $user = $this->student();
        $post = $this->makePost();
        Like::create(['post_id' => $post->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.likes_count', 1)
                ->where('post.liked', true)
                ->where('post.can_like', true));
    }

    public function test_admin_sees_count_on_detail_page_but_cannot_like(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post  = $this->makePost();
        Like::create(['post_id' => $post->id, 'user_id' => $this->student()->id]);

        $this->actingAs($admin)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.likes_count', 1)
                ->where('post.can_like', false));
    }

    public function test_liked_page_requires_student_login(): void
    {
        $this->get('/user/disukai')->assertRedirect('/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/user/disukai')->assertForbidden();
    }

    public function test_liked_page_lists_only_my_active_liked_posts(): void
    {
        $me    = $this->student();
        $other = $this->student();

        $mine    = $this->makePost(['title' => 'Saya suka']);
        $notMine = $this->makePost(['title' => 'Tidak saya suka']);
        $expired = $this->makePost(['title' => 'Sudah kedaluwarsa']);

        Like::create(['post_id' => $mine->id,    'user_id' => $me->id]);
        Like::create(['post_id' => $notMine->id, 'user_id' => $other->id]);
        Like::create(['post_id' => $expired->id, 'user_id' => $me->id]);

        // masa tayang habis SETELAH disukai
        $expired->forceFill(['published_at' => now()->subDays(10), 'expired_at' => now()->subDay()])->save();

        $this->actingAs($me)->get('/user/disukai')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Liked')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Saya suka')
                ->where('posts.data.0.liked', true));
    }

    public function test_deleting_a_post_removes_its_likes(): void
    {
        $post = $this->makePost();
        Like::create(['post_id' => $post->id, 'user_id' => $this->student()->id]);

        $post->delete();

        $this->assertSame(0, Like::count());
    }
}
