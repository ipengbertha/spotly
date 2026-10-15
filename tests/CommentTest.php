<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $author;
    private User $student;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin', 'name' => 'Pak Admin']);
        $this->author  = User::factory()->create(['role' => 'user', 'name' => 'Penulis Post']);
        $this->student = User::factory()->create(['role' => 'user', 'name' => 'Siswa Pengomentar']);
        $this->other   = User::factory()->create(['role' => 'user', 'name' => 'Siswa Lain']);
    }

    private function makePost(array $attrs = []): Post
    {
        $category = Category::query()->first()
            ?? Category::forceCreate(['name' => 'Pengumuman', 'slug' => 'pengumuman']);

        return Post::forceCreate(array_merge([
            'user_id'      => $this->author->id,
            'category_id'  => $category->id,
            'title'        => 'Judul Uji',
            'slug'         => 'judul-uji-' . uniqid(),
            'content'      => 'Isi postingan tes.',
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(5),
        ], $attrs));
    }

    private function comment(Post $post, ?User $by = null, string $content = 'Komentar uji.', $at = null): Comment
    {
        return Comment::forceCreate([
            'post_id'    => $post->id,
            'user_id'    => ($by ?? $this->student)->id,
            'content'    => $content,
            'created_at' => $at ?? now(),
            'updated_at' => $at ?? now(),
        ]);
    }

    // Postingan yang TIDAK tayang, untuk menguji "404 untuk semua aksi komentar"
    private function inactivePosts(): array
    {
        return [
            'draft'     => $this->makePost(['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]),
            'submitted' => $this->makePost(['status' => Post::SUBMITTED, 'published_at' => null, 'expired_at' => null]),
            'rejected'  => $this->makePost(['status' => Post::REJECTED, 'published_at' => null, 'expired_at' => null]),
            'archived'  => $this->makePost(['status' => Post::ARCHIVED]),
            'suspended' => $this->makePost(['status' => Post::SUSPENDED]),
            'expired'   => $this->makePost(['expired_at' => now()->subHour()]),
        ];
    }

    // ---------- Menulis komentar ----------

    public function test_student_can_comment_on_an_active_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->student)
            ->post("/posts/{$post->id}/comments", ['content' => 'Terima kasih infonya!'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $this->student->id,
            'content' => 'Terima kasih infonya!',
        ]);
    }

    public function test_comment_text_is_trimmed(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->student)->post("/posts/{$post->id}/comments", ['content' => "   halo semua  \n"]);

        $this->assertSame('halo semua', Comment::first()->content);
    }

    public function test_post_author_can_comment_on_own_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->author)
            ->post("/posts/{$post->id}/comments", ['content' => 'Terima kasih semuanya.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Comment::where('post_id', $post->id)->count());
    }

    public function test_guest_is_sent_to_login_and_nothing_is_saved(): void
    {
        $post = $this->makePost();

        $this->post("/posts/{$post->id}/comments", ['content' => 'Halo'])->assertRedirect('/login');

        $this->assertSame(0, Comment::count());
    }

    public function test_admin_cannot_write_comments(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->admin)
            ->post("/posts/{$post->id}/comments", ['content' => 'Balasan resmi'])
            ->assertForbidden();

        $this->assertSame(0, Comment::count());
    }

    public function test_comment_on_a_post_that_is_not_active_is_not_found(): void
    {
        foreach ($this->inactivePosts() as $label => $post) {
            $this->actingAs($this->student)
                ->post("/posts/{$post->id}/comments", ['content' => 'Halo'])
                ->assertNotFound("Status {$label} seharusnya 404");
        }

        $this->assertSame(0, Comment::count());
    }

    public function test_comment_text_is_required_and_limited_to_2_to_500_characters(): void
    {
        $post = $this->makePost();
        $url  = "/posts/{$post->id}/comments";

        $this->actingAs($this->student)->post($url, ['content' => ''])->assertSessionHasErrors('content');
        $this->actingAs($this->student)->post($url, ['content' => '   '])->assertSessionHasErrors('content');
        $this->actingAs($this->student)->post($url, ['content' => 'a'])->assertSessionHasErrors('content');
        $this->actingAs($this->student)->post($url, ['content' => str_repeat('a', 501)])->assertSessionHasErrors('content');
        $this->assertSame(0, Comment::count());

        // batas atas dan bawah masih diterima
        $this->actingAs($this->student)->post($url, ['content' => 'ok'])->assertSessionHasNoErrors();
        $this->actingAs($this->other)->post($url, ['content' => str_repeat('a', 500)])->assertSessionHasNoErrors();
        $this->assertSame(2, Comment::count());
    }

    public function test_student_can_send_5_comments_per_minute_but_not_the_6th(): void
    {
        $post = $this->makePost();
        $url  = "/posts/{$post->id}/comments";

        foreach (range(1, 5) as $i) {
            $this->actingAs($this->student)->post($url, ['content' => "Komentar ke-{$i}"])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($this->student)->post($url, ['content' => 'Komentar ke-6'])
            ->assertSessionHasErrors('content');

        $this->assertSame(5, Comment::count());
    }

    public function test_rate_limit_is_per_student(): void
    {
        $post = $this->makePost();
        $url  = "/posts/{$post->id}/comments";

        foreach (range(1, 5) as $i) {
            $this->actingAs($this->student)->post($url, ['content' => "Komentar ke-{$i}"]);
        }

        // siswa lain tidak ikut terkena batas
        $this->actingAs($this->other)->post($url, ['content' => 'Halo dari siswa lain'])
            ->assertSessionHasNoErrors();

        $this->assertSame(6, Comment::count());
    }

    // ---------- Menghapus komentar (siswa) ----------

    public function test_student_can_delete_own_comment(): void
    {
        $post    = $this->makePost();
        $comment = $this->comment($post, $this->student);

        $this->actingAs($this->student)->delete("/comments/{$comment->id}")->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_student_cannot_delete_someone_elses_comment(): void
    {
        $post    = $this->makePost();
        $comment = $this->comment($post, $this->student);

        $this->actingAs($this->other)->delete("/comments/{$comment->id}")->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_deleting_a_comment_on_a_post_that_is_not_active_is_not_found(): void
    {
        foreach ($this->inactivePosts() as $label => $post) {
            $comment = $this->comment($post, $this->student);

            $this->actingAs($this->student)->delete("/comments/{$comment->id}")
                ->assertNotFound("Status {$label} seharusnya 404");

            $this->assertModelExists($comment);
        }
    }

    public function test_guest_cannot_delete_and_admin_cannot_use_the_student_route(): void
    {
        $post    = $this->makePost();
        $comment = $this->comment($post, $this->student);

        $this->delete("/comments/{$comment->id}")->assertRedirect('/login');
        $this->actingAs($this->admin)->delete("/comments/{$comment->id}")->assertForbidden();

        $this->assertModelExists($comment);
    }

    // ---------- Menghapus komentar (admin) ----------

    public function test_admin_can_delete_any_comment(): void
    {
        $post    = $this->makePost();
        $comment = $this->comment($post, $this->student);

        $this->actingAs($this->admin)->delete("/admin/comments/{$comment->id}")->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_admin_can_delete_comments_on_posts_that_are_no_longer_active(): void
    {
        foreach ($this->inactivePosts() as $post) {
            $comment = $this->comment($post, $this->student);

            $this->actingAs($this->admin)->delete("/admin/comments/{$comment->id}")->assertRedirect();

            $this->assertModelMissing($comment);
        }
    }

    public function test_student_and_guest_cannot_use_the_admin_delete_route(): void
    {
        $post    = $this->makePost();
        $comment = $this->comment($post, $this->student);

        $this->delete("/admin/comments/{$comment->id}")->assertRedirect('/login');
        $this->actingAs($this->student)->delete("/admin/comments/{$comment->id}")->assertForbidden();

        $this->assertModelExists($comment);
    }

    // ---------- Notifikasi ----------

    public function test_post_author_is_notified_of_a_new_comment(): void
    {
        $post = $this->makePost(['title' => 'Lomba Futsal']);

        $this->actingAs($this->student)->post("/posts/{$post->id}/comments", ['content' => 'Aku ikut ya!']);

        $this->assertSame(1, $this->author->notifications()->count());

        $data = $this->author->notifications()->first()->data;
        $this->assertSame('comment_received', $data['type']);
        $this->assertSame("/posts/{$post->slug}", $data['url']);
        $this->assertStringContainsString('Siswa Pengomentar', $data['message']);
        $this->assertStringContainsString('Lomba Futsal', $data['message']);
        $this->assertStringContainsString('Aku ikut ya!', $data['message']);
    }

    public function test_no_notification_when_commenting_on_own_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->author)->post("/posts/{$post->id}/comments", ['content' => 'Catatan tambahan.']);

        $this->assertSame(1, Comment::count());
        $this->assertSame(0, $this->author->notifications()->count());
    }

    public function test_no_notification_when_author_turned_it_off(): void
    {
        $this->author->forceFill(['notification_preferences' => ['comment_received' => false]])->save();
        $post = $this->makePost();

        $this->actingAs($this->student)->post("/posts/{$post->id}/comments", ['content' => 'Halo penulis']);

        $this->assertSame(1, Comment::count());
        $this->assertSame(0, $this->author->notifications()->count());
    }

    public function test_student_can_turn_the_comment_preference_on_and_off(): void
    {
        $this->actingAs($this->student)->put('/settings/notifications', [
            'post_approved'    => true,
            'post_rejected'    => true,
            'comment_received' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->student->fresh()->wantsNotification('comment_received'));
    }

    public function test_comment_preference_is_for_students_only(): void
    {
        $this->actingAs($this->student)->get('/settings')
            ->assertInertia(fn (Assert $page) => $page->where('preferences.comment_received', true));

        $this->actingAs($this->admin)->get('/settings')
            ->assertInertia(fn (Assert $page) => $page->missing('preferences.comment_received'));

        $this->actingAs($this->admin)->put('/settings/notifications', [
            'post_submitted'   => true,
            'comment_received' => false,
        ])->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('comment_received', $this->admin->fresh()->notification_preferences);
    }

    // ---------- Tampilan di halaman detail ----------

    public function test_guest_sees_comments_newest_first_but_cannot_write_or_delete(): void
    {
        $post = $this->makePost();
        $this->comment($post, $this->student, 'Komentar lama', now()->subHours(2));
        $this->comment($post, $this->other, 'Komentar baru', now()->subHour());

        $this->get("/posts/{$post->slug}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->where('post.comments_count', 2)
                ->where('post.can_comment', false)
                ->has('post.comments', 2)
                ->where('post.comments.0.content', 'Komentar baru')
                ->where('post.comments.0.author', 'Siswa Lain')
                ->where('post.comments.0.delete_url', null)
                ->where('post.comments.1.content', 'Komentar lama')
                ->where('post.comments.1.delete_url', null));
    }

    public function test_student_can_write_and_gets_delete_link_only_on_own_comments(): void
    {
        $post = $this->makePost();
        $mine = $this->comment($post, $this->student, 'Punyaku', now()->subHour());
        $this->comment($post, $this->other, 'Punya orang lain', now()->subMinutes(30));

        $this->actingAs($this->student)->get("/posts/{$post->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.can_comment', true)
                ->where('post.comments.0.content', 'Punya orang lain')
                ->where('post.comments.0.delete_url', null)
                ->where('post.comments.1.content', 'Punyaku')
                ->where('post.comments.1.delete_url', "/comments/{$mine->id}"));
    }

    public function test_admin_can_delete_every_comment_but_cannot_write(): void
    {
        $post = $this->makePost();
        $a    = $this->comment($post, $this->student, 'Satu', now()->subHour());
        $b    = $this->comment($post, $this->other, 'Dua', now()->subMinutes(30));

        $this->actingAs($this->admin)->get("/posts/{$post->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.can_comment', false)
                ->where('post.comments.0.delete_url', "/admin/comments/{$b->id}")
                ->where('post.comments.1.delete_url', "/admin/comments/{$a->id}"));
    }

    public function test_comments_are_hidden_on_a_post_that_is_not_active(): void
    {
        $draft = $this->makePost(['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null]);
        $this->comment($draft, $this->student);

        // pemilik dan admin boleh membuka halamannya, tapi bagian komentar tertutup
        foreach ([$this->author, $this->admin] as $viewer) {
            $this->actingAs($viewer)->get("/posts/{$draft->slug}")
                ->assertInertia(fn (Assert $page) => $page
                    ->where('post.can_comment', false)
                    ->has('post.comments', 0));
        }
    }

    // ---------- Jumlah komentar di kartu ----------

    public function test_mading_cards_show_the_comment_count(): void
    {
        $post = $this->makePost();
        $this->comment($post, $this->student);
        $this->comment($post, $this->other);

        $this->actingAs($this->student)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.data.0.id', $post->id)
                ->where('posts.data.0.comments_count', 2));
    }

    public function test_card_data_has_zero_comments_when_there_are_none(): void
    {
        $this->makePost();

        $this->actingAs($this->student)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->where('posts.data.0.comments_count', 0));
    }

    // ---------- Halaman admin ----------

    public function test_admin_sees_latest_comments_first(): void
    {
        $post = $this->makePost(['title' => 'Bazar Buku']);
        $this->comment($post, $this->student, 'Paling lama', now()->subHours(3));
        $this->comment($post, $this->other, 'Paling baru', now()->subMinutes(5));

        $this->actingAs($this->admin)->get('/admin/comments')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Comments/Index')
                ->where('comments.total', 2)
                ->where('comments.data.0.content', 'Paling baru')
                ->where('comments.data.0.author', 'Siswa Lain')
                ->where('comments.data.0.post_title', 'Bazar Buku')
                ->where('comments.data.1.content', 'Paling lama'));
    }

    public function test_admin_comment_page_also_lists_comments_on_posts_that_are_not_active(): void
    {
        $archived = $this->makePost(['status' => Post::ARCHIVED]);
        $this->comment($archived, $this->student);

        $this->actingAs($this->admin)->get('/admin/comments')
            ->assertInertia(fn (Assert $page) => $page
                ->where('comments.total', 1)
                ->where('comments.data.0.post_status', 'archived'));
    }

    public function test_admin_can_search_comments_by_text_author_or_post_title(): void
    {
        $post = $this->makePost(['title' => 'Pentas Seni Tahunan']);
        $this->comment($post, $this->student, 'Kostumnya bagus sekali');
        $this->comment($post, $this->other, 'Jam berapa mulai?');

        $this->actingAs($this->admin)->get('/admin/comments?q=kostum')
            ->assertInertia(fn (Assert $page) => $page->where('comments.total', 1)->where('filters.q', 'kostum'));

        $this->actingAs($this->admin)->get('/admin/comments?q=Siswa Lain')
            ->assertInertia(fn (Assert $page) => $page
                ->where('comments.total', 1)
                ->where('comments.data.0.content', 'Jam berapa mulai?'));

        $this->actingAs($this->admin)->get('/admin/comments?q=Pentas Seni')
            ->assertInertia(fn (Assert $page) => $page->where('comments.total', 2));

        $this->actingAs($this->admin)->get('/admin/comments?q=tidak-ada-yang-cocok')
            ->assertInertia(fn (Assert $page) => $page->where('comments.total', 0));
    }

    public function test_admin_comment_page_is_for_admins_only(): void
    {
        $this->get('/admin/comments')->assertRedirect('/login');
        $this->actingAs($this->student)->get('/admin/comments')->assertForbidden();
    }

    // ---------- Data demo ----------

    public function test_seeded_demo_has_comments_on_active_posts_and_notifies_authors(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(8, Comment::count());

        // hanya di postingan tayang, ditulis siswa, dan bukan oleh pemilik postingan
        Comment::with(['post', 'user'])->get()->each(function (Comment $c) {
            $this->assertTrue($c->post->isActive());
            $this->assertSame('user', $c->user->role);
            $this->assertNotSame($c->post->user_id, $c->user_id);
        });

        $notified = User::where('role', 'user')->get()
            ->sum(fn (User $u) => $u->notifications()->where('data->type', 'comment_received')->count());
        $this->assertSame(Comment::count(), $notified);
    }
}
