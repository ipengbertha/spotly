<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ContributorAward;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardContributorsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->create(['role' => 'admin']);
        $this->period = now()->subMonthNoOverflow()->format('Y-m');
    }

    /** Siswa dengan sejumlah karya terbit bulan lalu. */
    private function student(string $name, int $posts = 2): User
    {
        $student  = User::factory()->create(['role' => 'user', 'name' => $name]);
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        Post::factory()->count($posts)->create([
            'user_id'      => $student->id,
            'category_id'  => $category->id,
            'status'       => 'published',
            'published_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
        ]);

        return $student;
    }

    public function test_dashboard_lists_last_months_eligible_students(): void
    {
        $this->student('Ani', 2);
        $this->student('Budi', 2);
        $this->student('Citra', 1); // belum memenuhi minimum 2 karya

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('contributors.period', $this->period)
                ->where('contributors.announced', false)
                ->has('contributors.items', 2)
                ->where('contributors.items.0.rank', 1)
            );
    }

    public function test_dashboard_marks_announced_students(): void
    {
        $ani = $this->student('Ani', 2);
        $this->student('Budi', 2);

        ContributorAward::create([
            'period'         => $this->period,
            'user_id'        => $ani->id,
            'rank'           => 1,
            'posts_count'    => 2,
            'likes_count'    => 0,
            'comments_count' => 0,
            'announced_by'   => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('contributors.announced', true)
                ->where('contributors.items', fn ($items) => collect($items)->where('announced', true)->count() === 1)
            );
    }

    public function test_dashboard_card_is_empty_when_nobody_qualifies(): void
    {
        $this->student('Citra', 1);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('contributors.announced', false)
                ->has('contributors.items', 0)
            );
    }
}