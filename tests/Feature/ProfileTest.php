<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(User $author, array $attrs = []): Post
    {
        $category = Category::query()->first()
            ?? Category::forceCreate(['name' => 'Pengumuman', 'slug' => 'pengumuman']);

        return Post::forceCreate(array_merge([
            'user_id'      => $author->id,
            'category_id'  => $category->id,
            'title'        => 'Judul Uji',
            'slug'         => 'judul-uji-' . uniqid(),
            'content'      => 'Isi postingan tes.',
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(5),
        ], $attrs));
    }

    private function student(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user'], $attrs));
    }

    public function test_guest_can_open_a_student_profile(): void
    {
        $author = $this->student(['name' => 'Citra', 'bio' => 'Suka menulis.']);

        $this->get("/profil/{$author->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->where('profile.name', 'Citra')
                ->where('profile.bio', 'Suka menulis.')
                ->where('viewer', 'guest'));
    }

    public function test_profile_lists_only_active_posts_of_that_author(): void
    {
        $author = $this->student();
        $other  = $this->student();

        $this->makePost($author, ['title' => 'Tayang']);
        $this->makePost($author, ['title' => 'Draft', 'status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]);
        $this->makePost($author, ['title' => 'Kedaluwarsa', 'published_at' => now()->subDays(9), 'expired_at' => now()->subDay()]);
        $this->makePost($other,  ['title' => 'Milik orang lain']);

        $this->get("/profil/{$author->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Tayang'));
    }

    public function test_profile_does_not_expose_private_data(): void
    {
        $author = $this->student(['email' => 'rahasia@spotly.test', 'phone' => '08123456789']);

        $response = $this->get("/profil/{$author->id}")->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('profile.email')
            ->missing('profile.phone')
            ->missing('profile.username')
            ->missing('profile.role'));

        $response->assertDontSee('rahasia@spotly.test', false);
        $response->assertDontSee('08123456789', false);
    }

    public function test_profile_counts_active_posts_and_received_likes(): void
    {
        $author = $this->student();
        $fan1   = $this->student();
        $fan2   = $this->student();

        $a = $this->makePost($author);
        $b = $this->makePost($author);
        $draft = $this->makePost($author, ['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]);

        Like::create(['post_id' => $a->id, 'user_id' => $fan1->id]);
        Like::create(['post_id' => $a->id, 'user_id' => $fan2->id]);
        Like::create(['post_id' => $b->id, 'user_id' => $fan1->id]);
        Like::create(['post_id' => $draft->id, 'user_id' => $fan1->id]); // tidak dihitung

        $this->get("/profil/{$author->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.posts_count', 2)
                ->where('profile.likes_count', 3));
    }

    public function test_is_me_is_true_only_for_own_profile(): void
    {
        $me    = $this->student();
        $other = $this->student();

        $this->actingAs($me)->get("/profil/{$me->id}")
            ->assertInertia(fn (Assert $page) => $page->where('profile.is_me', true)->where('viewer', 'user'));

        $this->actingAs($me)->get("/profil/{$other->id}")
            ->assertInertia(fn (Assert $page) => $page->where('profile.is_me', false));
    }

    public function test_admin_can_view_a_student_profile(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $author = $this->student();

        $this->actingAs($admin)->get("/profil/{$author->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('viewer', 'admin'));
    }

    public function test_admin_inactive_and_unknown_profiles_return_not_found(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $inactive = $this->student(['is_active' => false]);

        $this->get("/profil/{$admin->id}")->assertNotFound();
        $this->get("/profil/{$inactive->id}")->assertNotFound();
        $this->get('/profil/999999')->assertNotFound();
    }

    public function test_cards_carry_author_id_and_avatar(): void
    {
        $viewer = $this->student();
        $author = $this->student(['name' => 'Budi', 'avatar' => 'avatars/budi.jpg']);
        $this->makePost($author);

        $this->actingAs($viewer)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.author', 'Budi')
                ->where('posts.data.0.author_id', $author->id)
                ->where('posts.data.0.author_avatar', '/storage/avatars/budi.jpg'));
    }

    public function test_detail_page_links_to_author(): void
    {
        $author = $this->student(['name' => 'Dimas']);
        $post   = $this->makePost($author);

        $this->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.author_id', $author->id)
                ->where('post.author_avatar', null));
    }
}
