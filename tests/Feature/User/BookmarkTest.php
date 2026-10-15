<?php

namespace Tests\Feature\User;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Tahap 1A paket 2: simpan artikel (bookmark) dan halaman Tersimpan.
class BookmarkTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---- Simpan / batal simpan ----

    public function test_student_can_bookmark_an_active_post(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/bookmark")->assertRedirect();

        $this->assertDatabaseHas('bookmarks', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_second_press_removes_the_bookmark(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/bookmark");
        $this->actingAs($user)->post("/posts/{$post->id}/bookmark");

        $this->assertDatabaseMissing('bookmarks', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_bookmark_is_unique_per_student_and_post(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        Bookmark::create(['post_id' => $post->id, 'user_id' => $user->id]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Bookmark::create(['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_students_bookmarks_are_independent_of_each_other(): void
    {
        $a = $this->student();
        $b = $this->student();
        $post = $this->makePost();

        $this->actingAs($a)->post("/posts/{$post->id}/bookmark");
        $this->actingAs($b)->post("/posts/{$post->id}/bookmark");
        $this->actingAs($a)->post("/posts/{$post->id}/bookmark"); // a membatalkan

        $this->assertDatabaseMissing('bookmarks', ['post_id' => $post->id, 'user_id' => $a->id]);
        $this->assertDatabaseHas('bookmarks', ['post_id' => $post->id, 'user_id' => $b->id]);
    }

    public function test_bookmark_and_like_do_not_affect_each_other(): void
    {
        $user = $this->student();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/like");
        $this->actingAs($user)->post("/posts/{$post->id}/bookmark");
        $this->actingAs($user)->post("/posts/{$post->id}/bookmark"); // batal simpan

        $this->assertDatabaseHas('likes', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('bookmarks', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertSame(1, Like::count());
    }

    public function test_cannot_bookmark_posts_that_are_not_live(): void
    {
        $user = $this->student();

        $cases = [
            'draft'     => ['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null],
            'submitted' => ['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null],
            'rejected'  => ['status' => Post::REJECTED, 'published_at' => null, 'expired_at' => null],
            'archived'  => ['status' => Post::ARCHIVED],
            'expired'   => ['expired_at' => now()->subHour()],
            'suspended' => ['status' => Post::SUSPENDED],
            'scheduled' => ['published_at' => now()->addDay()],
        ];

        foreach ($cases as $label => $attrs) {
            $post = $this->makePost($attrs);

            $this->actingAs($user)->post("/posts/{$post->id}/bookmark")
                ->assertNotFound();
        }

        $this->assertSame(0, Bookmark::count());
    }

    public function test_guest_is_sent_to_login(): void
    {
        $post = $this->makePost();

        $this->post("/posts/{$post->id}/bookmark")->assertRedirect('/login');
        $this->assertSame(0, Bookmark::count());
    }

    public function test_admin_cannot_bookmark(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->admin())->post("/posts/{$post->id}/bookmark")->assertForbidden();
        $this->assertSame(0, Bookmark::count());
    }

    public function test_deactivated_student_cannot_bookmark(): void
    {
        $user = $this->student();
        $user->forceFill(['is_active' => false])->save();
        $post = $this->makePost();

        $this->actingAs($user)->post("/posts/{$post->id}/bookmark");

        $this->assertSame(0, Bookmark::count());
    }

    public function test_bookmarks_are_removed_with_the_post_and_with_the_student(): void
    {
        $user  = $this->student();
        $post1 = $this->makePost();
        $post2 = $this->makePost();
        Bookmark::create(['post_id' => $post1->id, 'user_id' => $user->id]);
        Bookmark::create(['post_id' => $post2->id, 'user_id' => $user->id]);

        $post1->delete();
        $this->assertSame(1, Bookmark::count());

        $user->delete();
        $this->assertSame(0, Bookmark::count());
    }

    // ---- Halaman Tersimpan ----

    public function test_saved_page_lists_only_my_live_saved_posts_newest_saved_first(): void
    {
        $me    = $this->student();
        $other = $this->student();

        $first   = $this->makePost(['title' => 'Pertama disimpan']);
        $second  = $this->makePost(['title' => 'Kedua disimpan']);
        $notMine = $this->makePost(['title' => 'Disimpan orang lain']);
        $expired = $this->makePost(['title' => 'Sudah kedaluwarsa']);
        $unsaved = $this->makePost(['title' => 'Tidak disimpan']);

        Bookmark::forceCreate(['post_id' => $first->id,   'user_id' => $me->id,    'created_at' => now()->subHours(3)]);
        Bookmark::forceCreate(['post_id' => $second->id,  'user_id' => $me->id,    'created_at' => now()->subHour()]);
        Bookmark::forceCreate(['post_id' => $expired->id, 'user_id' => $me->id,    'created_at' => now()->subHours(2)]);
        Bookmark::forceCreate(['post_id' => $notMine->id, 'user_id' => $other->id, 'created_at' => now()]);
        $expired->forceFill(['expired_at' => now()->subMinute()])->save();

        $this->actingAs($me)->get('/user/tersimpan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Saved')
                ->has('posts.data', 2)
                ->where('posts.data.0.id', $second->id)
                ->where('posts.data.1.id', $first->id)
                ->where('posts.data.0.bookmarked', true));
    }

    public function test_saved_page_is_empty_when_nothing_is_saved(): void
    {
        $this->makePost();

        $this->actingAs($this->student())->get('/user/tersimpan')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
    }

    public function test_saved_page_is_for_students_only(): void
    {
        $this->get('/user/tersimpan')->assertRedirect('/login');
        $this->actingAs($this->admin())->get('/user/tersimpan')->assertForbidden();
    }

    // ---- Penanda di kartu dan halaman baca ----

    public function test_feed_cards_mark_which_posts_i_saved(): void
    {
        $me    = $this->student();
        $other = $this->student();
        $saved = $this->makePost(['title' => 'Saya simpan', 'published_at' => now()->subHour()]);
        $plain = $this->makePost(['title' => 'Tidak disimpan', 'published_at' => now()->subHours(2)]);
        Bookmark::create(['post_id' => $saved->id, 'user_id' => $other->id]);

        // Simpanan orang lain tidak terbaca sebagai simpanan saya
        $this->actingAs($me)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.bookmarked', false)
                ->where('posts.data.1.bookmarked', false));

        Bookmark::create(['post_id' => $saved->id, 'user_id' => $me->id]);

        $this->actingAs($me)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.id', $saved->id)
                ->where('posts.data.0.bookmarked', true)
                ->where('posts.data.1.bookmarked', false));
    }

    public function test_post_page_reports_bookmark_state_per_viewer(): void
    {
        $me   = $this->student();
        $post = $this->makePost();
        $url  = "/posts/{$post->slug}";

        $this->actingAs($me)->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('post.can_bookmark', true)
            ->where('post.bookmarked', false));

        Bookmark::create(['post_id' => $post->id, 'user_id' => $me->id]);

        $this->actingAs($me)->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('post.bookmarked', true));

        // Tamu dan admin tidak melihat tombol simpan
        auth()->logout();
        $this->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('post.can_bookmark', false)
            ->where('post.bookmarked', false));

        $this->actingAs($this->admin())->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('post.can_bookmark', false)
            ->where('post.bookmarked', false));
    }

    public function test_owner_has_no_bookmark_button_on_own_draft(): void
    {
        $me   = $this->student();
        $post = $this->makePost(['user_id' => $me->id, 'status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]);

        $this->actingAs($me)->get("/posts/{$post->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('post.can_bookmark', false));
    }
}
