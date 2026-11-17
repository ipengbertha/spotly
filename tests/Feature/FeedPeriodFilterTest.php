<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeedPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Selasa, 17 Nov 2026 -> tahun ajaran berjalan mulai 1 Juli 2026
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00'));
        $this->student = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $category = Category::forceCreate(['name' => 'Umum', 'slug' => 'umum']);

        $this->makePost($category, 'Baru',        '2026-11-15 08:00:00'); // 2 hari lalu
        $this->makePost($category, 'Awal bulan',  '2026-11-03 08:00:00'); // bulan ini, 14 hari lalu
        $this->makePost($category, 'Oktober',     '2026-10-25 08:00:00'); // 23 hari lalu
        $this->makePost($category, 'Agustus',     '2026-08-10 08:00:00'); // tahun ajaran ini
        $this->makePost($category, 'Tahun lalu',  '2026-06-20 08:00:00'); // tahun ajaran lalu
    }

    private function makePost(Category $category, string $title, string $publishedAt): void
    {
        Post::forceCreate([
            'user_id'      => $this->student->id,
            'category_id'  => $category->id,
            'title'        => $title,
            'slug'         => str($title)->slug() . '-' . uniqid(),
            'content'      => 'Isi ' . $title,
            'status'       => Post::PUBLISHED,
            'published_at' => $publishedAt,
            'expired_at'   => null,
        ]);
    }

    private function feed(array $query = [])
    {
        return $this->actingAs($this->student)->get('/user/beranda?' . http_build_query($query));
    }

    private function assertFeedCount(int $expected, array $query): void
    {
        $this->feed($query)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('User/Beranda')
            ->has('posts.data', $expected));
    }

    public function test_filter_7_hari(): void
    {
        $this->assertFeedCount(1, ['period' => '7']);
    }

    public function test_filter_30_hari(): void
    {
        $this->assertFeedCount(3, ['period' => '30']);
    }

    public function test_filter_bulan_ini(): void
    {
        $this->assertFeedCount(2, ['period' => 'month']);
    }

    public function test_filter_tahun_ajaran_ini(): void
    {
        $this->assertFeedCount(4, ['period' => 'school_year']);
    }

    public function test_tanpa_filter_memakai_jendela_tiga_bulan(): void
    {
        // Agustus (3 bulan lebih) dan Juni tidak ikut
        $this->assertFeedCount(3, []);
    }

    public function test_rentang_kosong_mengembalikan_daftar_kosong(): void
    {
        Post::query()->update(['published_at' => '2026-01-01 08:00:00']);

        $this->assertFeedCount(0, ['period' => '30']);
    }

    public function test_filter_bisa_digabung_dengan_pencarian(): void
    {
        $this->assertFeedCount(1, ['period' => 'month', 'q' => 'Awal']);
    }

    public function test_nilai_filter_tidak_valid_ditolak(): void
    {
        $this->feed(['period' => 'asal'])->assertSessionHasErrors('period');
    }
}