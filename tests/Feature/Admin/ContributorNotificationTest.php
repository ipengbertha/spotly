<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ContributorAnnounced;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorNotificationTest extends TestCase
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

    /** Siswa dengan 2 karya terbit bulan lalu (memenuhi syarat minimum). */
    private function student(string $name): User
    {
        $student  = User::factory()->create(['role' => 'user', 'name' => $name]);
        $category = Category::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        Post::factory()->count(2)->create([
            'user_id'      => $student->id,
            'category_id'  => $category->id,
            'status'       => 'published',
            'published_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
        ]);

        return $student;
    }

    private function announce(array $users): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/contributors', [
            'period'   => $this->period,
            'user_ids' => collect($users)->pluck('id')->all(),
        ]);

        // Pemeriksaan diagnosis: kalau gagal di sini, masalahnya ada di rute/validasi, bukan di notifikasi.
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertSame(
            count($users),
            \App\Models\ContributorAward::where('period', $this->period)->count(),
            'Pengumuman tidak tersimpan ke tabel contributor_awards.'
        );
    }

    private function announcedCount(User $user): int
    {
        return $user->notifications()->where('type', ContributorAnnounced::class)->count();
    }

    public function test_selected_students_get_notified(): void
    {
        $a = $this->student('Ani');
        $b = $this->student('Budi');
        $c = $this->student('Citra');

        $this->announce([$a, $b]);

        $this->assertSame(1, $this->announcedCount($a));
        $this->assertSame(1, $this->announcedCount($b));
        $this->assertSame(0, $this->announcedCount($c));

        $data = $a->notifications()->first()->data;
        $this->assertSame('contributor_announced', $data['type']);
        $this->assertSame($this->period, $data['period']);
    }

    public function test_saving_again_does_not_send_duplicates(): void
    {
        $a = $this->student('Ani');
        $b = $this->student('Budi');

        $this->announce([$a, $b]);
        $this->announce([$a, $b]);

        $this->assertSame(1, $this->announcedCount($a));
        $this->assertSame(1, $this->announcedCount($b));
    }

    public function test_removed_student_loses_notification_and_new_one_is_notified(): void
    {
        $a = $this->student('Ani');
        $b = $this->student('Budi');
        $c = $this->student('Citra');

        $this->announce([$a, $b]);
        $this->announce([$b, $c]);

        $this->assertSame(0, $this->announcedCount($a));
        $this->assertSame(1, $this->announcedCount($b));
        $this->assertSame(1, $this->announcedCount($c));
    }

    public function test_cancelling_removes_all_notifications_and_reannouncing_sends_again(): void
    {
        $a = $this->student('Ani');
        $b = $this->student('Budi');

        $this->announce([$a, $b]);
        $this->actingAs($this->admin)->delete("/admin/contributors/{$this->period}");

        $this->assertSame(0, $this->announcedCount($a));
        $this->assertSame(0, $this->announcedCount($b));

        $this->announce([$a, $b]);

        $this->assertSame(1, $this->announcedCount($a));
        $this->assertSame(1, $this->announcedCount($b));
    }
}