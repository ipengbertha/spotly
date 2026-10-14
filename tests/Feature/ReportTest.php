<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $author;
    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->author   = User::factory()->create(['role' => 'user']);
        $this->reporter = User::factory()->create(['role' => 'user']);
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

    private function report(Post $post, ?User $by = null, string $reason = 'hoax', ?string $note = null): Report
    {
        return Report::forceCreate([
            'post_id' => $post->id,
            'user_id' => ($by ?? $this->reporter)->id,
            'reason'  => $reason,
            'note'    => $note,
        ]);
    }

    // ---------- Siswa melaporkan ----------

    public function test_student_can_report_an_active_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->reporter)
            ->post("/posts/{$post->id}/report", ['reason' => 'hoax', 'note' => 'Isinya tidak benar.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'post_id' => $post->id,
            'user_id' => $this->reporter->id,
            'reason'  => 'hoax',
            'note'    => 'Isinya tidak benar.',
            'status'  => 'pending',
        ]);
    }

    public function test_same_student_cannot_report_the_same_post_twice(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'hoax']);
        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertSessionHasErrors('reason');

        $this->assertSame(1, Report::where('post_id', $post->id)->count());
    }

    public function test_admins_are_notified_only_on_the_first_pending_report(): void
    {
        $post   = $this->makePost();
        $second = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'hoax']);
        $this->actingAs($second)->post("/posts/{$post->id}/report", ['reason' => 'spam']);

        $this->assertSame(2, Report::count());
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame('report_submitted', $this->admin->notifications()->first()->data['type']);
        $this->assertSame('/admin/reports', $this->admin->notifications()->first()->data['url']);
    }

    public function test_student_cannot_report_own_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->author)->post("/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertForbidden();

        $this->assertSame(0, Report::count());
    }

    public function test_posts_that_are_not_active_cannot_be_reported(): void
    {
        $cases = [
            ['status' => Post::DRAFT, 'published_at' => null, 'expired_at' => null],
            ['status' => Post::ARCHIVED],
            ['status' => Post::SUSPENDED],
            ['published_at' => now()->subDays(10), 'expired_at' => now()->subDay()],
        ];

        foreach ($cases as $attrs) {
            $post = $this->makePost($attrs);
            $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'spam'])
                ->assertNotFound();
        }

        $this->assertSame(0, Report::count());
    }

    public function test_guest_and_admin_cannot_report(): void
    {
        $post = $this->makePost();

        $this->post("/posts/{$post->id}/report", ['reason' => 'spam'])->assertRedirect('/login');
        $this->actingAs($this->admin)->post("/posts/{$post->id}/report", ['reason' => 'spam'])->assertForbidden();

        $this->assertSame(0, Report::count());
    }

    public function test_report_validation(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", [])
            ->assertSessionHasErrors('reason');
        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'tidak-ada'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($this->reporter)->post("/posts/{$post->id}/report", ['reason' => 'spam', 'note' => str_repeat('a', 301)])
            ->assertSessionHasErrors('note');

        $this->assertSame(0, Report::count());
    }

    public function test_detail_page_tells_who_can_report(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->reporter)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.can_report', true)
                ->where('post.reported', false)
                ->has('post.report_reasons', 5));

        $this->actingAs($this->author)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page->where('post.can_report', false));

        $this->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page->where('post.can_report', false));

        $this->actingAs($this->admin)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page->where('post.can_report', false));

        $this->report($post);

        $this->actingAs($this->reporter)->get('/posts/' . $post->slug)
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.can_report', false)
                ->where('post.reported', true));
    }

    // ---------- Admin menilai ----------

    public function test_admin_sees_posts_with_pending_reports(): void
    {
        $post  = $this->makePost(['title' => 'Dilaporkan']);
        $clean = $this->makePost(['title' => 'Bersih']);
        $other = User::factory()->create(['role' => 'user']);

        $this->report($post, $this->reporter, 'hoax', 'Tidak benar');
        $this->report($post, $other, 'hoax');

        $this->actingAs($this->admin)->get('/admin/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports/Index')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Dilaporkan')
                ->where('posts.data.0.pending_count', 2)
                ->where('posts.data.0.top_reason', 'hoax')
                ->where('posts.data.0.can_suspend', true)
                ->has('posts.data.0.reports', 2)
                ->where('counts.pending', 1));
    }

    public function test_pending_reports_count_is_shared_with_admin_only(): void
    {
        $this->report($this->makePost());
        $this->report($this->makePost(), $this->reporter, 'spam');

        $this->actingAs($this->admin)->get('/admin/reports')
            ->assertInertia(fn (Assert $page) => $page->where('pendingReports', 2));

        $this->actingAs($this->reporter)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->where('pendingReports', 0));
    }

    public function test_admin_can_suspend_a_reported_post(): void
    {
        $post  = $this->makePost(['is_pinned' => true]);
        $other = User::factory()->create(['role' => 'user']);
        $this->report($post, $this->reporter, 'hoax');
        $this->report($post, $other, 'spam');

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", [
            'reason'     => 'hoax',
            'admin_note' => 'Mohon cek ulang sumbernya.',
        ])->assertRedirect();

        $post->refresh();
        $this->assertSame('suspended', $post->status);
        $this->assertSame('Informasi palsu atau hoaks. Mohon cek ulang sumbernya.', $post->suspension_reason);
        $this->assertFalse($post->is_pinned);
        $this->assertFalse($post->isActive());

        // semua laporan menunggu menjadi "ditindaklanjuti", lengkap dengan peninjau
        $this->assertSame(2, Report::where('status', 'upheld')->where('reviewed_by', $this->admin->id)->count());
        $this->assertSame(0, Report::pending()->count());

        // penulis diberi tahu, begitu juga kedua pelapor
        $this->assertSame('suspended', $this->author->notifications()->first()->data['type']);
        $this->assertSame('/posts/' . $post->slug, $this->author->notifications()->first()->data['url']);
        $this->assertSame('report_upheld', $this->reporter->notifications()->first()->data['type']);
        $this->assertSame(1, $other->notifications()->count());
    }

    public function test_suspended_post_leaves_the_feed(): void
    {
        $post = $this->makePost();
        $this->report($post);

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", ['reason' => 'spam']);

        $this->actingAs($this->reporter)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 0));
        $this->get('/posts/' . $post->slug)->assertNotFound();
    }

    public function test_suspend_is_rejected_without_pending_reports_or_for_unpublished_posts(): void
    {
        $clean = $this->makePost();
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$clean->id}/suspend", ['reason' => 'spam'])
            ->assertSessionHasErrors('post');
        $this->assertSame('published', $clean->fresh()->status);

        $archived = $this->makePost(['status' => Post::ARCHIVED]);
        $this->report($archived);
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$archived->id}/suspend", ['reason' => 'spam'])
            ->assertSessionHasErrors('post');
        $this->assertSame('archived', $archived->fresh()->status);
        $this->assertSame(1, Report::pending()->count());
    }

    public function test_suspend_requires_a_valid_reason(): void
    {
        $post = $this->makePost();
        $this->report($post);

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", [])
            ->assertSessionHasErrors('reason');
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", ['reason' => 'xxx'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('published', $post->fresh()->status);
    }

    public function test_admin_can_dismiss_reports_and_reporters_are_told(): void
    {
        $post  = $this->makePost();
        $other = User::factory()->create(['role' => 'user']);
        $r1    = $this->report($post, $this->reporter, 'hoax');
        $r2    = $this->report($post, $other, 'spam');

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/dismiss", [
            'admin_note' => 'Sudah dicek, informasinya benar.',
        ])->assertRedirect();

        $this->assertSame('published', $post->fresh()->status);
        $this->assertTrue($post->fresh()->isActive());
        $this->assertSame(2, Report::where('status', 'dismissed')->where('reviewed_by', $this->admin->id)->count());

        $n = $this->reporter->notifications()->first();
        $this->assertSame('report_dismissed', $n->data['type']);
        $this->assertSame("/laporan/{$r1->id}", $n->data['url']);
        $this->assertSame(1, $other->notifications()->count());

        // penulis tidak diganggu
        $this->assertSame(0, $this->author->notifications()->count());
    }

    public function test_dismiss_without_pending_reports_is_rejected(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/dismiss")
            ->assertSessionHasErrors('post');
    }

    public function test_history_lists_reviewed_reports(): void
    {
        $post = $this->makePost();
        $this->report($post);
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/dismiss", ['admin_note' => 'Aman']);

        $this->actingAs($this->admin)->get('/admin/reports?tab=history')
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'history')
                ->has('history.data', 1)
                ->where('history.data.0.status', 'dismissed')
                ->where('history.data.0.admin_note', 'Aman')
                ->where('counts.pending', 0)
                ->where('counts.history', 1));
    }

    public function test_students_and_guests_cannot_use_admin_report_pages(): void
    {
        $post = $this->makePost();
        $this->report($post);

        $this->actingAs($this->reporter)->get('/admin/reports')->assertForbidden();
        $this->actingAs($this->reporter)->post("/admin/reports/posts/{$post->id}/suspend", ['reason' => 'spam'])->assertForbidden();
        $this->actingAs($this->reporter)->post("/admin/reports/posts/{$post->id}/dismiss")->assertForbidden();
        $this->app['auth']->forgetGuards(); // kembali menjadi tamu
        $this->get('/admin/reports')->assertRedirect('/login');

        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame(1, Report::pending()->count());
    }

    // ---------- Sisi penulis dan pelapor ----------

    public function test_author_of_suspended_post_cannot_edit_or_resubmit_but_can_delete(): void
    {
        $post = $this->makePost();
        $this->report($post);
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", ['reason' => 'spam']);

        $this->actingAs($this->author)->get("/user/posts/{$post->id}/edit")->assertForbidden();
        $this->actingAs($this->author)->put("/user/posts/{$post->id}", [
            'title' => 'Baru', 'content' => 'Isi baru yang cukup panjang.', 'category_id' => $post->category_id,
        ])->assertForbidden();
        $this->actingAs($this->author)->post("/user/posts/{$post->id}/submit")->assertForbidden();

        $this->assertSame('suspended', $post->fresh()->status);

        $this->actingAs($this->author)->delete("/user/posts/{$post->id}")->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_author_sees_suspension_reason_in_list_and_detail(): void
    {
        $post = $this->makePost();
        $this->report($post);
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/suspend", [
            'reason' => 'inappropriate', 'admin_note' => 'Bahasanya kasar.',
        ]);

        $this->actingAs($this->author)->get('/user/posts')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.display_status', 'suspended')
                ->where('posts.0.suspension_reason', 'Konten tidak pantas. Bahasanya kasar.')
                ->where('posts.0.can.edit', false)
                ->where('posts.0.can.submit', false)
                ->where('posts.0.can.delete', true));

        $this->actingAs($this->author)->get('/posts/' . $post->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.status', 'suspended')
                ->where('post.suspension_reason', 'Konten tidak pantas. Bahasanya kasar.')
                ->where('post.edit_url', null));

        // orang lain tidak bisa melihatnya
        $this->actingAs($this->reporter)->get('/posts/' . $post->slug)->assertNotFound();
    }

    public function test_reporter_can_open_own_report_result_but_not_others(): void
    {
        $post   = $this->makePost();
        $report = $this->report($post);
        $other  = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/dismiss", ['admin_note' => 'Aman']);

        $this->actingAs($this->reporter)->get("/laporan/{$report->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Show')
                ->where('report.status', 'dismissed')
                ->where('report.admin_note', 'Aman')
                ->where('report.post_title', 'Judul Uji'));

        $this->actingAs($other)->get("/laporan/{$report->id}")->assertNotFound();
        $this->actingAs($this->admin)->get("/laporan/{$report->id}")->assertForbidden();
        $this->app['auth']->forgetGuards(); // kembali menjadi tamu
        $this->get("/laporan/{$report->id}")->assertRedirect('/login');
    }

    public function test_opening_the_notification_leads_to_the_report_result(): void
    {
        $post = $this->makePost();
        $this->report($post);
        $this->actingAs($this->admin)->post("/admin/reports/posts/{$post->id}/dismiss");

        $notification = $this->reporter->notifications()->first();

        $this->actingAs($this->reporter)->post("/notifications/{$notification->id}/read")
            ->assertRedirect($notification->data['url']);
        $this->actingAs($this->reporter)->get($notification->data['url'])->assertOk();
    }

    public function test_deleting_a_post_removes_its_reports(): void
    {
        $post = $this->makePost();
        $this->report($post);

        $post->delete();

        $this->assertSame(0, Report::count());
    }
}
