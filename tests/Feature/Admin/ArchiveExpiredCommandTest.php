<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveExpiredCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student  = User::factory()->create(['role' => 'user']);
        $this->category = Category::unguarded(fn () => Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']));
    }

    private function makePost(string $slug, string $status, ?\DateTimeInterface $expiredAt, bool $pinned = false): Post
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => "Judul {$slug}",
            'slug'        => $slug,
            'content'     => 'Isi tes.',
        ]);
        $post->forceFill([
            'status'       => $status,
            'published_at' => $status === Post::PUBLISHED ? now()->subDays(10) : null,
            'expired_at'   => $expiredAt,
            'is_pinned'    => $pinned,
        ])->save();

        return $post->fresh();
    }

    public function test_expired_published_post_is_archived(): void
    {
        $post = $this->makePost('lewat', Post::PUBLISHED, now()->subHour());

        $this->artisan('posts:archive-expired')
            ->expectsOutput('1 postingan diarsipkan.')
            ->assertSuccessful();

        $this->assertSame(Post::ARCHIVED, $post->fresh()->status);
    }

    public function test_pin_is_released_when_archived(): void
    {
        $post = $this->makePost('pin', Post::PUBLISHED, now()->subMinute(), pinned: true);

        $this->artisan('posts:archive-expired');

        $this->assertFalse($post->fresh()->is_pinned);
    }

    public function test_expiry_exactly_now_counts_as_expired(): void
    {
        $this->travelTo(now()->startOfSecond());
        $post = $this->makePost('pas', Post::PUBLISHED, now());

        $this->artisan('posts:archive-expired');

        $this->assertSame(Post::ARCHIVED, $post->fresh()->status);
    }

    public function test_active_post_is_not_touched(): void
    {
        $post = $this->makePost('tayang', Post::PUBLISHED, now()->addDay(), pinned: true);

        $this->artisan('posts:archive-expired')->expectsOutput('0 postingan diarsipkan.');

        $fresh = $post->fresh();
        $this->assertSame(Post::PUBLISHED, $fresh->status);
        $this->assertTrue($fresh->is_pinned);
    }

    public function test_post_without_end_date_is_not_touched(): void
    {
        $post = $this->makePost('tanpa-akhir', Post::PUBLISHED, null);

        $this->artisan('posts:archive-expired');

        $this->assertSame(Post::PUBLISHED, $post->fresh()->status);
    }

    public function test_other_statuses_are_not_touched_even_with_past_date(): void
    {
        $draft     = $this->makePost('draf', Post::DRAFT, now()->subDay());
        $submitted = $this->makePost('review', Post::SUBMITTED, now()->subDay());
        $rejected  = $this->makePost('ditolak', Post::REJECTED, now()->subDay());
        $suspended = $this->makePost('ditangguhkan', Post::SUSPENDED, now()->subDay());

        $this->artisan('posts:archive-expired')->expectsOutput('0 postingan diarsipkan.');

        $this->assertSame(Post::DRAFT, $draft->fresh()->status);
        $this->assertSame(Post::SUBMITTED, $submitted->fresh()->status);
        $this->assertSame(Post::REJECTED, $rejected->fresh()->status);
        $this->assertSame(Post::SUSPENDED, $suspended->fresh()->status);
    }

    public function test_running_twice_is_safe(): void
    {
        $this->makePost('lewat', Post::PUBLISHED, now()->subHour());

        $this->artisan('posts:archive-expired')->expectsOutput('1 postingan diarsipkan.');
        $this->artisan('posts:archive-expired')->expectsOutput('0 postingan diarsipkan.');
    }

    public function test_many_posts_are_all_archived(): void
    {
        foreach (range(1, 250) as $i) {
            $this->makePost("lewat-{$i}", Post::PUBLISHED, now()->subHour());
        }

        $this->artisan('posts:archive-expired')->expectsOutput('250 postingan diarsipkan.');

        $this->assertSame(0, Post::where('status', Post::PUBLISHED)->count());
    }

    public function test_republished_post_does_not_come_back_pinned(): void
    {
        $post = $this->makePost('lagi', Post::PUBLISHED, now()->subMinute(), pinned: true);
        $this->artisan('posts:archive-expired');

        $post->fresh()->markPublished(now()->addDays(3));

        $this->assertFalse($post->fresh()->is_pinned);
        $this->assertTrue($post->fresh()->isActive());
    }

    public function test_command_is_scheduled_hourly(): void
    {
        // Muat definisi jadwal dari routes/console.php
        $this->artisan('list');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'posts:archive-expired'));

        $this->assertNotNull($event, 'posts:archive-expired belum dijadwalkan.');
        $this->assertSame('0 * * * *', $event->expression);
    }
}
