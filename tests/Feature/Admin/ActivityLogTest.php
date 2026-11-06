<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $name = 'Admin Uji'): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true, 'name' => $name]);
    }

    private function buatPost(string $status, array $extra = []): Post
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $data = ['name' => 'Berita ' . Str::random(4)];
        if (Schema::hasColumn('categories', 'slug')) {
            $data['slug'] = 'berita-' . Str::random(6);
        }
        $kategori = Category::forceCreate($data);

        return Post::forceCreate(array_merge([
            'user_id'     => $siswa->id,
            'category_id' => $kategori->id,
            'title'       => 'Judul ' . Str::random(6),
            'slug'        => Str::slug('judul-' . Str::random(8)),
            'content'     => '<p>isi artikel</p>',
            'status'      => $status,
        ], $extra));
    }

    private function tayang(): Post
    {
        return $this->buatPost(Post::PUBLISHED, [
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(10),
        ]);
    }

        private function laporan(Post $post, string $alasan = 'hoax'): Report
    {
        $pelapor = User::factory()->create(['role' => 'user', 'is_active' => true]);

        return Report::forceCreate([
            'post_id' => $post->id,
            'user_id' => $pelapor->id,
            'reason'  => $alasan,
        ]);
    }

    public function test_menyetujui_tercatat(): void
    {
        $admin = $this->admin();
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($admin)->post("/admin/reviews/{$post->id}/approve", [
            'expired_at' => now()->addDays(7)->toDateString(),
        ]);

        $log = ActivityLog::first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('post.approved', $log->action);
        $this->assertSame($post->id, $log->post_id);
        $this->assertSame($post->title, $log->subject_title);
        $this->assertStringContainsString('Tayang sampai', $log->detail);
    }

    public function test_menjadwalkan_tercatat(): void
    {
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($this->admin())->post("/admin/reviews/{$post->id}/approve", [
            'publish_at' => now()->addDays(2)->toDateString(),
            'expired_at' => now()->addDays(9)->toDateString(),
        ]);

        $log = ActivityLog::first();
        $this->assertSame('post.scheduled', $log->action);
        $this->assertStringContainsString('sampai', $log->detail);
    }

    public function test_menolak_menyimpan_alasan(): void
    {
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($this->admin())->post("/admin/reviews/{$post->id}/reject", [
            'rejection_reason' => 'Isinya belum sesuai pedoman mading.',
        ]);

        $log = ActivityLog::first();
        $this->assertSame('post.rejected', $log->action);
        $this->assertSame('Isinya belum sesuai pedoman mading.', $log->detail);
    }

    public function test_minta_revisi_menyimpan_catatan(): void
    {
        $post = $this->buatPost(Post::SUBMITTED);

        $this->actingAs($this->admin())->post("/admin/reviews/{$post->id}/revision", [
            'revision_note' => 'Tolong tambahkan sumber foto.',
        ]);

        $log = ActivityLog::first();
        $this->assertSame('post.revision', $log->action);
        $this->assertSame('Tolong tambahkan sumber foto.', $log->detail);
    }

    public function test_aksi_kelola_postingan_tercatat(): void
    {
        $admin = $this->admin();
        $post = $this->tayang();

        $this->actingAs($admin);

        $this->post("/admin/posts/{$post->id}/pin");
        $this->post("/admin/posts/{$post->id}/pin"); // sudah di-pin: tidak dicatat lagi
        $this->delete("/admin/posts/{$post->id}/pin");
        $this->patch("/admin/posts/{$post->id}/expiry", ['expired_at' => now()->addDays(20)->toDateString()]);
        $this->post("/admin/posts/{$post->id}/archive");
        $this->delete("/admin/posts/{$post->id}");

        $this->assertSame(
            ['post.pinned', 'post.unpinned', 'post.expiry', 'post.archived', 'post.deleted'],
            ActivityLog::orderBy('id')->pluck('action')->all()
        );

        // Postingannya sudah hilang, tapi judulnya tetap terbaca di log
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertSame($post->title, ActivityLog::orderByDesc('id')->first()->subject_title);
    }

    public function test_batalkan_jadwal_tercatat(): void
    {
        $post = $this->buatPost(Post::PUBLISHED, [
            'published_at' => now()->addDays(3),
            'expired_at'   => now()->addDays(10),
        ]);

        $this->actingAs($this->admin())->post("/admin/posts/{$post->id}/unschedule");

        $this->assertSame('post.unscheduled', ActivityLog::first()->action);
    }

    public function test_aksi_gagal_tidak_tercatat(): void
    {
        $post = $this->tayang(); // bukan berstatus submitted

        $this->actingAs($this->admin())->post("/admin/reviews/{$post->id}/approve", [
            'expired_at' => now()->addDays(7)->toDateString(),
        ])->assertSessionHasErrors('post');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_admin_melihat_aktivitas_terbaru_dulu(): void
    {
        $admin = $this->admin('Budi Admin');

        ActivityLog::forceCreate([
            'admin_id' => $admin->id, 'action' => 'post.archived', 'subject_title' => 'Judul Lama',
            'created_at' => now()->subHour(), 'updated_at' => now()->subHour(),
        ]);
        ActivityLog::forceCreate([
            'admin_id' => $admin->id, 'action' => 'post.approved', 'subject_title' => 'Judul Baru',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/activity')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Activity/Index')
                ->has('logs.data', 2)
                ->where('logs.data.0.title', 'Judul Baru')
                ->where('logs.data.0.label', 'menyetujui')
                ->where('logs.data.0.admin', 'Budi Admin')
                ->where('logs.data.1.title', 'Judul Lama'));
    }

    public function test_pencarian_aktivitas(): void
    {
        $admin = $this->admin();

        ActivityLog::forceCreate(['admin_id' => $admin->id, 'action' => 'post.approved', 'subject_title' => 'Kantin Sehat']);
        ActivityLog::forceCreate(['admin_id' => $admin->id, 'action' => 'post.approved', 'subject_title' => 'Lomba Poster']);

        $this->actingAs($admin)
            ->get('/admin/activity?q=Kantin')
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.title', 'Kantin Sehat'));
    }

    public function test_siswa_dan_tamu_tidak_bisa_membuka_aktivitas(): void
    {
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->get('/admin/activity')->assertRedirect('/login');

        $this->actingAs($siswa)->get('/admin/activity')->assertForbidden();
    }

        public function test_publikasi_ulang_tercatat(): void
    {
        $admin = $this->admin();
        $post = $this->buatPost(Post::ARCHIVED, [
            'published_at' => now()->subDays(10),
            'expired_at'   => now()->subDay(),
        ]);
        $tanggal = now()->addDays(14)->toDateString();

        // Ganti URL/method ini kalau route:list menunjukkan yang lain
        $this->actingAs($admin)->post("/admin/archive/{$post->id}/republish", [
            'expired_at' => $tanggal,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ActivityLog::count());

        $log = ActivityLog::first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('post.republished', $log->action);
        $this->assertSame($post->id, $log->post_id);
        $this->assertSame($post->title, $log->subject_title);
        $this->assertSame(
            'Sampai ' . Carbon::parse($tanggal)->endOfDay()->translatedFormat('d M Y'),
            $log->detail
        );
    }

    public function test_tangguhkan_laporan_tercatat(): void
    {
        $admin = $this->admin();
        $post = $this->tayang();
        $this->laporan($post, 'hoax');
        $this->laporan($post, 'spam');

        $this->actingAs($admin)->post("/admin/reports/posts/{$post->id}/suspend", [
            'reason'     => 'hoax',
            'admin_note' => 'Mohon cek ulang sumbernya.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ActivityLog::count());

        $log = ActivityLog::first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('report.upheld', $log->action);
        $this->assertSame($post->id, $log->post_id);
        $this->assertSame($post->title, $log->subject_title);
        $this->assertSame('2 laporan. Informasi palsu atau hoaks. Mohon cek ulang sumbernya.', $log->detail);
    }

    public function test_tolak_laporan_tercatat(): void
    {
        $admin = $this->admin();
        $post = $this->tayang();
        $this->laporan($post, 'hoax');
        $this->laporan($post, 'spam');

        $this->actingAs($admin)->post("/admin/reports/posts/{$post->id}/dismiss", [
            'admin_note' => 'Sudah dicek, informasinya benar.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ActivityLog::count());

        $log = ActivityLog::first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('report.dismissed', $log->action);
        $this->assertSame($post->id, $log->post_id);
        $this->assertSame('2 laporan. Sudah dicek, informasinya benar.', $log->detail);
    }

        public function test_akun_dinonaktifkan_dan_diaktifkan_tercatat(): void
    {
        $admin = $this->admin();
        $siswa = User::factory()->create(['role' => 'user', 'is_active' => true, 'name' => 'Budi Siswa']);

        $this->actingAs($admin);

        $this->patch("/admin/users/{$siswa->id}/deactivate")->assertSessionHasNoErrors();
        $this->patch("/admin/users/{$siswa->id}/deactivate"); // sudah nonaktif: tidak dicatat lagi
        $this->patch("/admin/users/{$siswa->id}/activate")->assertSessionHasNoErrors();

        $this->assertSame(
            ['user.deactivated', 'user.activated'],
            ActivityLog::orderBy('id')->pluck('action')->all()
        );

        $log = ActivityLog::orderBy('id')->first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('Budi Siswa', $log->subject_title);
        $this->assertNull($log->post_id);
    }

    public function test_akun_admin_tidak_bisa_dinonaktifkan_dan_tidak_tercatat(): void
    {
        $admin = $this->admin();
        $adminLain = $this->admin('Admin Lain');

        $this->actingAs($admin)
            ->patch("/admin/users/{$adminLain->id}/deactivate")
            ->assertSessionHasErrors('user');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_tambah_kategori_tercatat(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/categories', ['name' => 'Olahraga'])
            ->assertSessionHasNoErrors();

        $log = ActivityLog::first();
        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('category.created', $log->action);
        $this->assertSame('Olahraga', $log->subject_title);
        $this->assertNull($log->post_id);
    }

    public function test_ubah_nama_kategori_tercatat_dan_nama_sama_tidak(): void
    {
        $kategori = Category::forceCreate(['name' => 'Sport', 'slug' => 'sport']);

        $this->actingAs($this->admin());

        $this->put("/admin/categories/{$kategori->id}", ['name' => 'Sport']); // tidak berubah
        $this->assertSame(0, ActivityLog::count());

        $this->put("/admin/categories/{$kategori->id}", ['name' => 'Olahraga'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ActivityLog::count());

        $log = ActivityLog::first();
        $this->assertSame('category.renamed', $log->action);
        $this->assertSame('Olahraga', $log->subject_title);
        $this->assertSame('Sebelumnya "Sport"', $log->detail);
    }

    public function test_hapus_kategori_mencatat_postingan_yang_dipindahkan(): void
    {
        $post = $this->tayang();
        $asal = Category::findOrFail($post->category_id);
        $nama = $asal->name;
        $tujuan = Category::forceCreate(['name' => 'Tujuan Pindah', 'slug' => 'tujuan-pindah']);

        $this->actingAs($this->admin())
            ->delete("/admin/categories/{$asal->id}", ['move_to' => $tujuan->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($tujuan->id, $post->fresh()->category_id);

        $log = ActivityLog::first();
        $this->assertSame('category.deleted', $log->action);
        $this->assertSame($nama, $log->subject_title);
        $this->assertSame('1 postingan dipindahkan ke "Tujuan Pindah"', $log->detail);
    }

    public function test_hapus_kategori_kosong_tercatat_tanpa_catatan(): void
    {
        $kategori = Category::forceCreate(['name' => 'Kosong', 'slug' => 'kosong']);

        $this->actingAs($this->admin())
            ->delete("/admin/categories/{$kategori->id}")
            ->assertSessionHasNoErrors();

        $log = ActivityLog::first();
        $this->assertSame('category.deleted', $log->action);
        $this->assertSame('Kosong', $log->subject_title);
        $this->assertNull($log->detail);
    }
}