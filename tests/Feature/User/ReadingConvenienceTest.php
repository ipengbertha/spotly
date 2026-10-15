<?php

namespace Tests\Feature\User;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Tahap 1A paket 1: estimasi waktu baca dan filter tanggal di Mading.
class ReadingConvenienceTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student  = User::factory()->create(['role' => 'user']);
        $this->category = Category::forceCreate(['name' => 'Pengumuman', 'slug' => 'pengumuman']);
    }

    private function makePost(string $slug, array $attrs = []): Post
    {
        return Post::forceCreate(array_merge([
            'user_id'      => $this->student->id,
            'category_id'  => $this->category->id,
            'title'        => "Judul {$slug}",
            'slug'         => $slug,
            'content'      => 'Isi postingan tes.',
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addMonths(6),
        ], $attrs));
    }

    private function words(int $n): string
    {
        return trim(str_repeat('kata ', $n));
    }

    // ---- Estimasi waktu baca ----

    public function test_reading_minutes_rounds_up_with_minimum_of_one(): void
    {
        $this->assertSame(1, $this->makePost('a', ['content' => ''])->readingMinutes());
        $this->assertSame(1, $this->makePost('b', ['content' => $this->words(5)])->readingMinutes());
        $this->assertSame(1, $this->makePost('c', ['content' => $this->words(200)])->readingMinutes());
        $this->assertSame(2, $this->makePost('d', ['content' => $this->words(201)])->readingMinutes());
        $this->assertSame(5, $this->makePost('e', ['content' => $this->words(1000)])->readingMinutes());
    }

    public function test_reading_minutes_ignores_html_tags_and_extra_whitespace(): void
    {
        $content = '<p>' . $this->words(150) . "</p>\n\n   <p>" . $this->words(100) . '</p>';

        $this->assertSame(2, $this->makePost('tag', ['content' => $content])->readingMinutes());
    }

    public function test_feed_cards_include_reading_minutes(): void
    {
        $this->makePost('panjang', ['content' => $this->words(450)]);

        $this->actingAs($this->student)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.reading_minutes', 3));
    }

    public function test_post_page_includes_reading_minutes_for_guest(): void
    {
        $post = $this->makePost('halaman', ['content' => $this->words(400)]);

        $this->get("/posts/{$post->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Posts/Show')
                ->where('post.reading_minutes', 2));
    }

    // ---- Filter tanggal ----

    private function seedPostsByAge(): void
    {
        $this->makePost('baru',   ['published_at' => now()->subDays(3)]);
        $this->makePost('sedang', ['published_at' => now()->subDays(20)]);
        $this->makePost('lama',   ['published_at' => now()->subDays(60)]);
    }

    private function slugs($response): array
    {
        return collect($response->viewData('page')['props']['posts']['data'])
            ->pluck('slug')->sort()->values()->all();
    }

    public function test_period_filter_limits_posts_by_publish_date(): void
    {
        $this->seedPostsByAge();

        $this->assertSame(['baru'],
            $this->slugs($this->actingAs($this->student)->get('/user/beranda?period=7')));
        $this->assertSame(['baru', 'sedang'],
            $this->slugs($this->actingAs($this->student)->get('/user/beranda?period=30')));
        $this->assertSame(['baru', 'lama', 'sedang'],
            $this->slugs($this->actingAs($this->student)->get('/user/beranda?period=90')));
    }

    public function test_period_is_returned_in_filters(): void
    {
        $this->actingAs($this->student)->get('/user/beranda?period=30')
            ->assertInertia(fn (Assert $page) => $page->where('filters.period', 30));

        $this->actingAs($this->student)->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->where('filters.period', null));
    }

    public function test_invalid_period_is_rejected(): void
    {
        $this->actingAs($this->student)->get('/user/beranda?period=5')
            ->assertSessionHasErrors('period');

        $this->actingAs($this->student)->get('/user/beranda?period=abc')
            ->assertSessionHasErrors('period');
    }

    public function test_period_filter_also_hides_old_pinned_posts(): void
    {
        $old = $this->makePost('disematkan-lama', ['published_at' => now()->subDays(40)]);
        $old->forceFill(['is_pinned' => true])->save();
        $this->makePost('baru', ['published_at' => now()->subDays(2)]);

        // Tanpa filter tanggal, yang disematkan tetap tampil
        $this->assertContains('disematkan-lama',
            $this->slugs($this->actingAs($this->student)->get('/user/beranda')));

        // Dengan filter 7 hari, yang tayang lebih lama tidak ikut
        $this->assertSame(['baru'],
            $this->slugs($this->actingAs($this->student)->get('/user/beranda?period=7')));
    }

    public function test_period_combines_with_category_filter(): void
    {
        $other = Category::forceCreate(['name' => 'Prestasi', 'slug' => 'prestasi']);

        $this->makePost('pengumuman-baru', ['published_at' => now()->subDays(2)]);
        $this->makePost('prestasi-baru', ['category_id' => $other->id, 'published_at' => now()->subDays(2)]);
        $this->makePost('pengumuman-lama', ['published_at' => now()->subDays(25)]);

        $this->assertSame(['pengumuman-baru'],
            $this->slugs($this->actingAs($this->student)
                ->get("/user/beranda?period=7&category={$this->category->id}")));
    }

    public function test_period_combines_with_search(): void
    {
        $this->makePost('lomba-baru', ['title' => 'Lomba baru', 'published_at' => now()->subDays(2)]);
        $this->makePost('lomba-lama', ['title' => 'Lomba lama', 'published_at' => now()->subDays(25)]);
        $this->makePost('lain-baru',  ['title' => 'Info lain', 'published_at' => now()->subDays(2)]);

        $this->assertSame(['lomba-baru'],
            $this->slugs($this->actingAs($this->student)->get('/user/beranda?period=7&q=lomba')));
    }

    public function test_admin_can_use_period_filter_too(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedPostsByAge();

        $this->assertSame(['baru'],
            $this->slugs($this->actingAs($admin)->get('/user/beranda?period=7')));
    }
}
