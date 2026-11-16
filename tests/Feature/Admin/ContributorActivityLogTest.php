<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ContributorAward;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->create(['role' => 'admin', 'is_active' => true, 'name' => 'Admin Uji']);
        $this->period = now()->subMonthNoOverflow()->format('Y-m');
    }

    /** Siswa dengan sejumlah karya terbit bulan lalu. */
    private function student(string $name, int $posts = 2): User
    {
        $student  = User::factory()->create(['role' => 'user', 'is_active' => true, 'name' => $name]);
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        Post::factory()->count($posts)->create([
            'user_id'      => $student->id,
            'category_id'  => $category->id,
            'status'       => 'published',
            'published_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
        ]);

        return $student;
    }

    private function umumkan(array $users, ?string $period = null)
    {
        return $this->actingAs($this->admin)->post('/admin/contributors', [
            'period'   => $period ?? $this->period,
            'user_ids' => collect($users)->pluck('id')->all(),
        ]);
    }

    public function test_umumkan_bintang_mading_tercatat(): void
    {
        $ani  = $this->student('Ani');
        $budi = $this->student('Budi');

        $this->umumkan([$ani, $budi])->assertSessionHasNoErrors();

        $this->assertSame(1, ActivityLog::count());

        $log = ActivityLog::first();
        $this->assertSame($this->admin->id, $log->admin_id);
        $this->assertSame('contributor.announced', $log->action);
        $this->assertSame(ContributorAward::labelFor($this->period), $log->subject_title);
        $this->assertNull($log->post_id);
        $this->assertStringContainsString('Ani', $log->detail);
        $this->assertStringContainsString('Budi', $log->detail);
    }

    public function test_ubah_pilihan_tercatat_sebagai_perubahan(): void
    {
        $ani  = $this->student('Ani');
        $budi = $this->student('Budi');

        $this->umumkan([$ani, $budi]);
        $this->umumkan([$ani])->assertSessionHasNoErrors(); // Budi dicabut

        $this->assertSame(
            ['contributor.announced', 'contributor.updated'],
            ActivityLog::orderBy('id')->pluck('action')->all()
        );

        $log = ActivityLog::orderByDesc('id')->first();
        $this->assertStringContainsString('Ani', $log->detail);
        $this->assertStringNotContainsString('Budi', $log->detail);
    }

    public function test_simpan_tanpa_perubahan_tidak_tercatat(): void
    {
        $ani  = $this->student('Ani');
        $budi = $this->student('Budi');

        $this->umumkan([$ani, $budi]);
        $this->umumkan([$ani, $budi]); // sama persis

        $this->assertSame(1, ActivityLog::count());
    }

    public function test_batalkan_pengumuman_tercatat(): void
    {
        $ani  = $this->student('Ani');
        $budi = $this->student('Budi');

        $this->umumkan([$ani, $budi]);

        $this->actingAs($this->admin)
            ->delete("/admin/contributors/{$this->period}")
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ContributorAward::where('period', $this->period)->count());
        $this->assertSame(
            ['contributor.announced', 'contributor.cancelled'],
            ActivityLog::orderBy('id')->pluck('action')->all()
        );

        $log = ActivityLog::orderByDesc('id')->first();
        $this->assertSame(ContributorAward::labelFor($this->period), $log->subject_title);
        $this->assertStringContainsString('Ani', $log->detail);
        $this->assertStringContainsString('Budi', $log->detail);
    }

    public function test_batalkan_saat_belum_diumumkan_tidak_tercatat(): void
    {
        $this->actingAs($this->admin)->delete("/admin/contributors/{$this->period}");

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_pengumuman_gagal_tidak_tercatat(): void
    {
        $citra = $this->student('Citra', 1); // di bawah minimum 2 karya

        $this->umumkan([$citra])->assertSessionHasErrors('user_ids');

        $this->assertSame(0, ActivityLog::count());
    }
}