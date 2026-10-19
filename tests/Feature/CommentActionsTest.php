<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\CommentReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Menu titik tiga di komentar: edit (24 jam), laporkan, jempol, dan moderasi laporan oleh admin
class CommentActionsTest extends TestCase
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

    private function inactivePost(): Post
    {
        return $this->makePost(['status' => Post::ARCHIVED]);
    }

    // ---------- Edit ----------

    public function test_owner_can_edit_within_24_hours_and_it_is_marked_edited(): void
    {
        $c = $this->comment($this->makePost(), $this->student, 'Isi lama', now()->subHours(23));

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'Isi baru'])->assertRedirect();

        $c->refresh();
        $this->assertSame('Isi baru', $c->content);
        $this->assertNotNull($c->edited_at);
    }

    public function test_edit_is_rejected_after_24_hours(): void
    {
        $c = $this->comment($this->makePost(), $this->student, 'Isi lama', now()->subHours(25));

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'Isi baru'])
            ->assertSessionHasErrors('content');

        $c->refresh();
        $this->assertSame('Isi lama', $c->content);
        $this->assertNull($c->edited_at);
    }

    public function test_saving_unchanged_content_does_not_mark_edited(): void
    {
        $c = $this->comment($this->makePost(), $this->student, 'Isi sama');

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'Isi sama'])->assertRedirect();

        $this->assertNull($c->fresh()->edited_at);
    }

    public function test_edit_is_validated(): void
    {
        $c = $this->comment($this->makePost(), $this->student, 'Isi lama');

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'a'])->assertSessionHasErrors('content');
        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => str_repeat('a', 501)])->assertSessionHasErrors('content');
        $this->actingAs($this->student)->patch("/comments/{$c->id}", [])->assertSessionHasErrors('content');

        $this->assertSame('Isi lama', $c->fresh()->content);
    }

    public function test_only_the_owner_can_edit(): void
    {
        $c = $this->comment($this->makePost(), $this->student, 'Isi lama');

        $this->actingAs($this->other)->patch("/comments/{$c->id}", ['content' => 'Dibajak'])->assertForbidden();
        $this->actingAs($this->admin)->patch("/comments/{$c->id}", ['content' => 'Dibajak'])->assertForbidden();
        auth()->logout();
        $this->patch("/comments/{$c->id}", ['content' => 'Dibajak'])->assertRedirect('/login');

        $this->assertSame('Isi lama', $c->fresh()->content);
    }

    public function test_cannot_edit_comment_on_inactive_post(): void
    {
        $c = $this->comment($this->inactivePost(), $this->student, 'Isi lama');

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'Isi baru'])->assertNotFound();

        $this->assertSame('Isi lama', $c->fresh()->content);
    }

    // ---------- Jempol ----------

    public function test_student_can_like_and_unlike_someone_elses_comment(): void
    {
        $c = $this->comment($this->makePost(), $this->student);

        $this->actingAs($this->other)->post("/comments/{$c->id}/like")->assertRedirect();
        $this->assertSame(1, CommentLike::where('comment_id', $c->id)->count());

        $this->actingAs($this->other)->post("/comments/{$c->id}/like")->assertRedirect();
        $this->assertSame(0, CommentLike::count());
    }

    public function test_cannot_like_own_comment(): void
    {
        $c = $this->comment($this->makePost(), $this->student);

        $this->actingAs($this->student)->post("/comments/{$c->id}/like")->assertForbidden();

        $this->assertSame(0, CommentLike::count());
    }

    public function test_guest_admin_and_inactive_post_cannot_like(): void
    {
        $c        = $this->comment($this->makePost(), $this->student);
        $inactive = $this->comment($this->inactivePost(), $this->student);

        $this->post("/comments/{$c->id}/like")->assertRedirect('/login');
        $this->actingAs($this->admin)->post("/comments/{$c->id}/like")->assertForbidden();
        $this->actingAs($this->other)->post("/comments/{$inactive->id}/like")->assertNotFound();

        $this->assertSame(0, CommentLike::count());
    }

    // ---------- Laporan komentar ----------

    public function test_student_can_report_someone_elses_comment_and_admin_is_notified_once(): void
    {
        $c     = $this->comment($this->makePost(), $this->student);
        $third = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->other)->post("/comments/{$c->id}/report", ['reason' => 'spam', 'note' => 'Promosi'])->assertRedirect();
        $this->actingAs($third)->post("/comments/{$c->id}/report", ['reason' => 'hoax'])->assertRedirect();

        $this->assertSame(2, CommentReport::where('comment_id', $c->id)->pending()->count());
        // Hanya laporan pertama yang memberi tahu admin
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame('/admin/comments?filter=reported', $this->admin->notifications()->first()->data['url']);
    }

    public function test_same_student_cannot_report_the_same_comment_twice(): void
    {
        $c = $this->comment($this->makePost(), $this->student);

        $this->actingAs($this->other)->post("/comments/{$c->id}/report", ['reason' => 'spam']);
        $this->actingAs($this->other)->post("/comments/{$c->id}/report", ['reason' => 'hoax'])->assertSessionHasErrors('reason');

        $this->assertSame(1, CommentReport::count());
    }

    public function test_report_rules(): void
    {
        $c        = $this->comment($this->makePost(), $this->student);
        $inactive = $this->comment($this->inactivePost(), $this->student);

        $this->actingAs($this->student)->post("/comments/{$c->id}/report", ['reason' => 'spam'])->assertForbidden();
        $this->actingAs($this->other)->post("/comments/{$c->id}/report", ['reason' => 'ngawur'])->assertSessionHasErrors('reason');
        $this->actingAs($this->other)->post("/comments/{$c->id}/report", [])->assertSessionHasErrors('reason');
        $this->actingAs($this->other)->post("/comments/{$inactive->id}/report", ['reason' => 'spam'])->assertNotFound();
        $this->actingAs($this->admin)->post("/comments/{$c->id}/report", ['reason' => 'spam'])->assertForbidden();
        auth()->logout();
        $this->post("/comments/{$c->id}/report", ['reason' => 'spam'])->assertRedirect('/login');

        $this->assertSame(0, CommentReport::count());
    }

    // ---------- Data untuk halaman baca ----------

    public function test_post_page_flags_for_student(): void
    {
        $post   = $this->makePost();
        $mine   = $this->comment($post, $this->student, 'Punyaku', now()->subHours(2));
        $old    = $this->comment($post, $this->student, 'Punyaku lama', now()->subHours(30));
        $theirs = $this->comment($post, $this->other, 'Punya orang');
        CommentLike::create(['comment_id' => $theirs->id, 'user_id' => $this->student->id]);
        CommentLike::create(['comment_id' => $theirs->id, 'user_id' => $this->author->id]);
        CommentReport::create(['comment_id' => $theirs->id, 'user_id' => $this->student->id, 'reason' => 'spam']);

        $this->actingAs($this->student)->get('/posts/' . $post->slug)
            ->assertInertia(function (Assert $page) use ($mine, $old, $theirs) {
                $byId = collect($page->toArray()['props']['post']['comments'])->keyBy('id');

                $this->assertTrue($byId[$mine->id]['can_edit']);
                $this->assertTrue($byId[$mine->id]['is_mine']);
                $this->assertFalse($byId[$mine->id]['can_like']);
                $this->assertFalse($byId[$mine->id]['can_report']);

                $this->assertFalse($byId[$old->id]['can_edit']); // lewat 24 jam

                $this->assertFalse($byId[$theirs->id]['can_edit']);
                $this->assertTrue($byId[$theirs->id]['can_like']);
                $this->assertTrue($byId[$theirs->id]['liked']);
                $this->assertSame(2, $byId[$theirs->id]['likes_count']);
                $this->assertTrue($byId[$theirs->id]['reported']);
                $this->assertFalse($byId[$theirs->id]['can_report']); // sudah dilaporkan
            });
    }

    public function test_post_page_flags_for_guest_and_admin(): void
    {
        $post = $this->makePost();
        $c    = $this->comment($post, $this->student);
        CommentLike::create(['comment_id' => $c->id, 'user_id' => $this->other->id]);

        foreach ([null, $this->admin] as $viewer) {
            $request = $viewer ? $this->actingAs($viewer) : $this;
            $request->get('/posts/' . $post->slug)->assertInertia(function (Assert $page) use ($c) {
                $row = collect($page->toArray()['props']['post']['comments'])->firstWhere('id', $c->id);

                $this->assertFalse($row['can_edit']);
                $this->assertFalse($row['can_like']);
                $this->assertFalse($row['can_report']);
                $this->assertFalse($row['liked']);
                $this->assertSame(1, $row['likes_count']);
            });
        }
    }

    public function test_edited_flag_is_exposed(): void
    {
        $post = $this->makePost();
        $c    = $this->comment($post, $this->student, 'Isi lama');

        $this->actingAs($this->student)->patch("/comments/{$c->id}", ['content' => 'Isi baru']);

        $this->get('/posts/' . $post->slug)->assertInertia(fn (Assert $page) => $page
            ->where('post.comments.0.edited', true)
            ->where('post.comments.0.content', 'Isi baru'));
    }

    // ---------- Admin ----------

    public function test_admin_filter_shows_only_reported_comments(): void
    {
        $post     = $this->makePost();
        $clean    = $this->comment($post, $this->student, 'Komentar bersih');
        $reported = $this->comment($post, $this->student, 'Komentar dilaporkan');
        CommentReport::create(['comment_id' => $reported->id, 'user_id' => $this->other->id, 'reason' => 'spam', 'note' => 'Iklan']);

        $this->actingAs($this->admin)->get('/admin/comments?filter=reported')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.id', $reported->id)
                ->where('comments.data.0.pending_reports', 1)
                ->where('comments.data.0.reports.0.reason_label', 'Spam atau promosi')
                ->where('comments.data.0.reports.0.note', 'Iklan')
                ->where('counts.reported', 1)
                ->where('counts.all', 2));

        $this->actingAs($this->admin)->get('/admin/comments')
            ->assertInertia(fn (Assert $page) => $page->has('comments.data', 2));
    }

    public function test_admin_can_dismiss_reports_and_comment_stays(): void
    {
        $c = $this->comment($this->makePost(), $this->student);
        CommentReport::create(['comment_id' => $c->id, 'user_id' => $this->other->id, 'reason' => 'spam']);

        $this->actingAs($this->admin)->post("/admin/comments/{$c->id}/dismiss-reports")->assertRedirect();

        $this->assertNotNull(Comment::find($c->id));
        $report = CommentReport::first();
        $this->assertSame(CommentReport::DISMISSED, $report->status);
        $this->assertSame($this->admin->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);

        $this->actingAs($this->admin)->get('/admin/comments?filter=reported')
            ->assertInertia(fn (Assert $page) => $page->has('comments.data', 0)->where('counts.reported', 0));
    }

    public function test_only_admin_can_dismiss_reports(): void
    {
        $c = $this->comment($this->makePost(), $this->student);
        CommentReport::create(['comment_id' => $c->id, 'user_id' => $this->other->id, 'reason' => 'spam']);

        $this->actingAs($this->other)->post("/admin/comments/{$c->id}/dismiss-reports")->assertForbidden();

        $this->assertSame(CommentReport::PENDING, CommentReport::first()->status);
    }

    public function test_deleting_a_comment_removes_its_likes_and_reports(): void
    {
        $c = $this->comment($this->makePost(), $this->student);
        CommentLike::create(['comment_id' => $c->id, 'user_id' => $this->other->id]);
        CommentReport::create(['comment_id' => $c->id, 'user_id' => $this->other->id, 'reason' => 'spam']);

        $this->actingAs($this->admin)->delete("/admin/comments/{$c->id}")->assertRedirect();

        $this->assertSame(0, CommentLike::count());
        $this->assertSame(0, CommentReport::count());
    }
}
