<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->student  = User::factory()->create(['role' => 'user']);
        $this->category = Category::unguarded(fn () => Category::create(['name' => 'Pengumuman', 'slug' => 'pengumuman']));
    }

    private function published(string $slug, string $title): Post
    {
        $post = Post::create([
            'user_id'     => $this->student->id,
            'category_id' => $this->category->id,
            'title'       => $title,
            'slug'        => $slug,
            'content'     => 'Isi tes.',
        ]);
        $post->markSubmitted();
        $post->markPublished(now()->addDays(5));

        return $post;
    }

    public function test_post_search_filters_by_title(): void
    {
        $this->published('lomba', 'Lomba Cerdas Cermat');
        $this->published('libur', 'Jadwal Libur Semester');

        $this->actingAs($this->admin)
            ->get('/admin/posts?q=Lomba')
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Lomba Cerdas Cermat'));
    }

    public function test_search_without_match_returns_empty(): void
    {
        $this->published('lomba', 'Lomba Cerdas Cermat');

        $this->actingAs($this->admin)
            ->get('/admin/posts?q=zzzz')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    }

    public function test_pinned_count_ignores_search(): void
    {
        $pinned = $this->published('lomba', 'Lomba Cerdas Cermat');
        $this->actingAs($this->admin)->post("/admin/posts/{$pinned->id}/pin");
        $this->published('libur', 'Jadwal Libur Semester');

        // Hasil pencarian tidak memuat postingan ber-pin, tapi hitungan pin tetap 1
        $this->actingAs($this->admin)
            ->get('/admin/posts?q=Libur')
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('pinnedCount', 1));
    }
}