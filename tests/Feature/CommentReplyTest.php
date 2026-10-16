<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommentReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $author;   // penulis postingan
    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->create(['role' => 'admin']);
        $this->author = User::factory()->create(['role' => 'user', 'name' => 'Penulis Post']);
        $this->alice  = User::factory()->create(['role' => 'user', 'name' => 'Alice']);
        $this->bob    = User::factory()->create(['role' => 'user', 'name' => 'Bob']);
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

    private function comment(Post $post, User $by, ?Comment $parent = null, string $content = 'Komentar uji.', $at = null): Comment
    {
        return Comment::forceCreate([
            'post_id'    => $post->id,
            'user_id'    => $by->id,
            'parent_id'  => $parent?->id,
            'content'    => $content,
            'created_at' => $at ?? now(),
            'updated_at' => $at ?? now(),
        ]);
    }

    // ---------- Membalas ----------

    public function test_student_can_reply_to_a_comment(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice);

        $this->actingAs($this->bob)
            ->post("/posts/{$post->id}/comments", ['content' => 'Setuju!', 'parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id, 'user_id' => $this->bob->id, 'parent_id' => $parent->id, 'content' => 'Setuju!',
        ]);
    }

    public function test_a_reply_can_be_replied_to_without_limit(): void
    {
        $post = $this->makePost();
        $c    = $this->comment($post, $this->alice);

        // 6 tingkat balasan, bergantian bob dan alice
        for ($i = 1; $i <= 6; $i++) {
            $by = $i % 2 ? $this->bob : $this->alice;
            $this->actingAs($by)->post("/posts/{$post->id}/comments", ['content' => "Balasan {$i}", 'parent_id' => $c->id]);
            $c = Comment::latest('id')->first();
        }

        $this->assertSame(7, Comment::count());
        $this->assertNotNull($c->parent_id);
    }

    public function test_cannot_reply_to_a_comment_from_another_post(): void
    {
        $post  = $this->makePost();
        $other = $this->makePost();
        $elsewhere = $this->comment($other, $this->alice);

        $this->actingAs($this->bob)
            ->post("/posts/{$post->id}/comments", ['content' => 'Nyasar', 'parent_id' => $elsewhere->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertSame(1, Comment::count());
    }

    public function test_cannot_reply_to_a_comment_that_does_not_exist(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->bob)
            ->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => 9999])
            ->assertSessionHasErrors('parent_id');

        $this->assertSame(0, Comment::count());
    }

    public function test_reply_follows_the_same_rules_as_a_comment(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice);

        // tamu -> login, admin -> 403
        $this->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => $parent->id])->assertRedirect('/login');
        $this->actingAs($this->admin)->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => $parent->id])->assertForbidden();

        // komentar dimatikan penulis -> 403
        $post->setCommentsEnabled(false);
        $this->actingAs($this->bob)->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => $parent->id])->assertForbidden();

        $this->assertSame(1, Comment::count());
    }

    public function test_reply_on_a_post_that_is_not_active_is_not_found(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice);
        $post->forceFill(['status' => Post::ARCHIVED])->save();

        $this->actingAs($this->bob)
            ->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => $parent->id])
            ->assertNotFound();

        $this->assertSame(1, Comment::count());
    }

    // ---------- Menghapus: balasan ikut terhapus ----------

    public function test_deleting_a_top_comment_deletes_all_replies_under_it(): void
    {
        $post = $this->makePost();
        $top  = $this->comment($post, $this->alice);
        $r1   = $this->comment($post, $this->bob, $top);
        $r2   = $this->comment($post, $this->author, $r1);   // balasan dari balasan
        $this->comment($post, $this->alice, $r2);            // tiga tingkat ke bawah
        $keep = $this->comment($post, $this->bob);           // komentar lain, tidak terkait

        $this->actingAs($this->alice)->delete("/comments/{$top->id}")->assertRedirect();

        $this->assertSame([$keep->id], Comment::pluck('id')->all());
    }

    public function test_deleting_a_reply_deletes_its_own_replies_but_not_parent_or_siblings(): void
    {
        $post    = $this->makePost();
        $top     = $this->comment($post, $this->alice);
        $mine    = $this->comment($post, $this->bob, $top);
        $under   = $this->comment($post, $this->alice, $mine);   // membalas $mine
        $sibling = $this->comment($post, $this->author, $top);   // saudara $mine

        $this->actingAs($this->bob)->delete("/comments/{$mine->id}")->assertRedirect();

        $this->assertNull(Comment::find($mine->id));
        $this->assertNull(Comment::find($under->id));
        $this->assertNotNull(Comment::find($top->id));
        $this->assertNotNull(Comment::find($sibling->id));
    }

    public function test_student_still_cannot_delete_someone_elses_reply(): void
    {
        $post  = $this->makePost();
        $top   = $this->comment($post, $this->alice);
        $reply = $this->comment($post, $this->bob, $top);

        $this->actingAs($this->alice)->delete("/comments/{$reply->id}")->assertForbidden();

        $this->assertNotNull(Comment::find($reply->id));
    }

    public function test_admin_deleting_a_comment_also_deletes_its_replies(): void
    {
        $post = $this->makePost();
        $top  = $this->comment($post, $this->alice);
        $r1   = $this->comment($post, $this->bob, $top);
        $this->comment($post, $this->alice, $r1);

        $this->actingAs($this->admin)->delete("/admin/comments/{$top->id}")->assertRedirect();

        $this->assertSame(0, Comment::count());
    }

    public function test_deleting_a_post_removes_all_its_comments_and_replies(): void
    {
        $post = $this->makePost();
        $top  = $this->comment($post, $this->alice);
        $this->comment($post, $this->bob, $top);

        $post->delete();

        $this->assertSame(0, Comment::count());
    }

    // ---------- Data halaman baca ----------

    public function test_post_page_returns_comments_as_a_tree_with_the_right_order(): void
    {
        $post = $this->makePost();
        $old  = $this->comment($post, $this->alice, null, 'Utama lama', now()->subHours(5));
        $new  = $this->comment($post, $this->bob,   null, 'Utama baru', now()->subHour());

        // di bawah komentar lama: dua balasan (urutan terlama dulu) dan satu balasan dari balasan
        $first  = $this->comment($post, $this->bob,    $old, 'Balasan pertama', now()->subHours(4));
        $second = $this->comment($post, $this->author, $old, 'Balasan kedua',   now()->subHours(3));
        $this->comment($post, $this->alice, $first, 'Balasan dari balasan', now()->subHours(2));

        $this->actingAs($this->bob)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->has('post.comments', 2)
                ->where('post.comments_count', 5)                       // semua komentar dihitung
                ->where('post.comments.0.content', 'Utama baru')        // utama: terbaru di atas
                ->where('post.comments.0.replies_total', 0)
                ->where('post.comments.1.content', 'Utama lama')
                ->where('post.comments.1.replies_total', 3)
                ->has('post.comments.1.replies', 2)
                ->where('post.comments.1.replies.0.content', 'Balasan pertama')   // balasan: terlama di atas
                ->where('post.comments.1.replies.0.reply_to', 'Alice')
                ->where('post.comments.1.replies.0.replies.0.content', 'Balasan dari balasan')
                ->where('post.comments.1.replies.0.replies.0.reply_to', 'Bob')
                ->where('post.comments.1.replies.1.content', 'Balasan kedua')
                ->where('post.comments.1.reply_to', null));
    }

    public function test_delete_links_on_replies_follow_the_same_rules(): void
    {
        $post  = $this->makePost();
        $top   = $this->comment($post, $this->alice);
        $reply = $this->comment($post, $this->bob, $top);

        $this->actingAs($this->bob)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.comments.0.delete_url', null)
                ->where('post.comments.0.replies.0.delete_url', "/comments/{$reply->id}"));

        $this->actingAs($this->admin)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.comments.0.delete_url', "/admin/comments/{$top->id}")
                ->where('post.comments.0.replies.0.delete_url', "/admin/comments/{$reply->id}"));
    }

    // ---------- Notifikasi ----------

    public function test_parent_comment_author_is_notified_of_a_reply(): void
    {
        $post   = $this->makePost(['title' => 'Lomba Futsal']);
        $parent = $this->comment($post, $this->alice);

        $this->actingAs($this->bob)->post("/posts/{$post->id}/comments", ['content' => 'Aku juga!', 'parent_id' => $parent->id]);

        $this->assertSame(1, $this->alice->notifications()->count());
        $data = $this->alice->notifications()->first()->data;
        $this->assertSame('comment_received', $data['type']);
        $this->assertSame("/posts/{$post->slug}", $data['url']);
        $this->assertStringContainsString('Bob', $data['message']);
        $this->assertStringContainsString('membalas', $data['message']);
        $this->assertStringContainsString('Aku juga!', $data['message']);

        // penulis postingan tetap diberi tahu soal komentar baru di postingannya
        $this->assertSame(1, $this->author->notifications()->count());
    }

    public function test_no_notification_when_replying_to_your_own_comment(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice);

        $this->actingAs($this->alice)->post("/posts/{$post->id}/comments", ['content' => 'Tambahan', 'parent_id' => $parent->id]);

        $this->assertSame(0, $this->alice->notifications()->count());
    }

    public function test_post_author_gets_only_one_notification_when_also_the_parent_author(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->author);   // penulis postingan berkomentar sendiri

        $this->actingAs($this->bob)->post("/posts/{$post->id}/comments", ['content' => 'Siap kak', 'parent_id' => $parent->id]);

        $this->assertSame(1, $this->author->notifications()->count());
        $this->assertStringContainsString('membalas', $this->author->notifications()->first()->data['message']);
    }

    public function test_no_reply_notification_when_the_user_turned_it_off(): void
    {
        $this->alice->forceFill(['notification_preferences' => ['comment_received' => false]])->save();
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice);

        $this->actingAs($this->bob)->post("/posts/{$post->id}/comments", ['content' => 'Halo', 'parent_id' => $parent->id]);

        $this->assertSame(0, $this->alice->notifications()->count());
    }

    // ---------- Halaman admin ----------

    public function test_admin_comment_list_shows_who_a_reply_answers_and_reply_count(): void
    {
        $post   = $this->makePost();
        $parent = $this->comment($post, $this->alice, null, 'Utama', now()->subHour());
        $this->comment($post, $this->bob, $parent, 'Balasan', now());

        $this->actingAs($this->admin)->get('/admin/comments')
            ->assertInertia(fn (Assert $page) => $page
                ->where('comments.data.0.content', 'Balasan')
                ->where('comments.data.0.reply_to', 'Alice')
                ->where('comments.data.1.content', 'Utama')
                ->where('comments.data.1.replies_count', 1)
                ->where('comments.data.1.reply_to', null));
    }
}