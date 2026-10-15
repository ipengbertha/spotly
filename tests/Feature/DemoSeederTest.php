<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Menjaga supaya data demo selalu "hidup": tab Tayang tidak kosong dan fitur baru ada isinya
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_mading_has_active_pinned_and_archived_posts(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(8, Post::active()->count());
        $this->assertSame(3, Post::active()->where('is_pinned', true)->count());
        $this->assertGreaterThanOrEqual(2, Post::archive()->count());
        $this->assertSame(1, Post::where('status', Post::SUSPENDED)->count());
    }

    public function test_seeded_students_have_bios_and_posts_have_likes(): void
    {
        $this->seed();

        $this->assertSame(0, User::where('role', 'user')->whereNull('bio')->count());
        $this->assertGreaterThan(10, Like::count());

        // tidak ada yang menyukai postingannya sendiri
        Like::with('post')->get()->each(fn ($like) => $this->assertNotSame($like->post->user_id, $like->user_id));
    }

    public function test_seeded_bookmarks_are_on_live_posts_of_other_students(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(8, Bookmark::count());

        // hanya postingan tayang, dan tidak ada yang menyimpan postingannya sendiri
        Bookmark::with('post')->get()->each(function ($b) {
            $this->assertTrue($b->post->isActive());
            $this->assertNotSame($b->post->user_id, $b->user_id);
        });

        // akun demo utama (Ivana) punya beberapa simpanan, dan halaman Tersimpan menampilkannya
        $ivana = User::where('email', 'ivana@spotly.test')->first();
        $this->assertGreaterThanOrEqual(3, Bookmark::where('user_id', $ivana->id)->count());

        $this->actingAs($ivana)->get('/user/tersimpan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Saved')
                ->has('posts.data', Bookmark::where('user_id', $ivana->id)->count()));
    }

    public function test_bookmark_seeding_does_not_duplicate_when_run_again(): void
    {
        $this->seed();
        $before = Bookmark::count();

        // Hanya bagian simpanan yang dijalankan ulang (bagian laporan memang tidak untuk dua kali jalan)
        $seeder = new \Database\Seeders\DemoActivitySeeder();
        $method = new \ReflectionMethod($seeder, 'bookmarks');
        $method->invoke($seeder, User::where('role', 'user')->orderBy('id')->get());

        $this->assertSame($before, Bookmark::count());
    }

    public function test_seeded_reports_cover_pending_upheld_and_dismissed(): void
    {
        $this->seed();

        $this->assertSame(2, Report::where('status', Report::PENDING)->count());
        $this->assertSame(1, Report::pending()->distinct()->count('post_id'));
        $this->assertSame(2, Report::where('status', Report::UPHELD)->count());
        $this->assertSame(1, Report::where('status', Report::DISMISSED)->count());

        // pelapor bukan penulis postingan
        Report::with('post')->get()->each(fn ($r) => $this->assertNotSame($r->post->user_id, $r->user_id));

        $suspended = Post::where('status', Post::SUSPENDED)->first();
        $this->assertNotEmpty($suspended->suspension_reason);
    }

    public function test_seeded_data_shows_up_for_admin_and_student(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@spotly.test')->first();
        $ivana = User::where('email', 'ivana@spotly.test')->first();

        $this->actingAs($admin)->get('/admin/reports')
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.pending', 1)
                ->where('counts.history', 3));

        $this->assertSame(1, $admin->notifications()->count());

        $this->actingAs($ivana)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.total', Post::active()->count()));
    }
}