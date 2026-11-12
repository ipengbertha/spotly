<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PollManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function siswa(): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'question'     => 'Menu kantin apa yang paling kamu mau?',
            'allow_change' => true,
            'ends_at'      => now()->addDays(7)->toDateString(),
            'options'      => ['Salad buah', 'Jus segar', 'Nasi merah'],
        ], $override);
    }

    private function buatPolling(array $attr = [], array $options = ['Ya', 'Tidak']): Poll
    {
        $poll = Poll::create(array_merge([
            'question'     => 'Setuju jam masuk diundur?',
            'allow_change' => false,
            'ends_at'      => now()->addDays(3),
        ], $attr));

        foreach ($options as $i => $label) {
            $poll->options()->create(['label' => $label, 'position' => $i]);
        }

        return $poll;
    }

    public function test_admin_bisa_membuat_polling_dan_tercatat(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/polls', $this->data())
            ->assertSessionHasNoErrors();

        $poll = Poll::firstOrFail();
        $this->assertSame('Menu kantin apa yang paling kamu mau?', $poll->question);
        $this->assertTrue($poll->allow_change);
        $this->assertSame($admin->id, $poll->created_by);
        $this->assertSame(
            ['Salad buah', 'Jus segar', 'Nasi merah'],
            $poll->options->pluck('label')->all()
        );
        $this->assertTrue($poll->isOpen());

        $log = ActivityLog::first();
        $this->assertSame('poll.created', $log->action);
        $this->assertSame($poll->question, $log->subject_title);
        $this->assertSame('Suara bisa diganti', $log->detail);
        $this->assertNull($log->post_id);
    }

    public function test_polling_aktif_kedua_ditolak(): void
    {
        $this->buatPolling();

        $this->actingAs($this->admin())
            ->post('/admin/polls', $this->data())
            ->assertSessionHasErrors('poll');

        $this->assertSame(1, Poll::count());
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_polling_baru_boleh_setelah_yang_lama_ditutup(): void
    {
        $this->buatPolling(['closed_at' => now()]);

        $this->actingAs($this->admin())
            ->post('/admin/polls', $this->data())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Poll::count());
    }

    public function test_pertanyaan_dan_pilihan_divalidasi(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/polls', $this->data(['options' => ['Satu saja']]))
            ->assertSessionHasErrors('options');

        $this->post('/admin/polls', $this->data(['options' => ['A', 'B', 'C', 'D', 'E', 'F', 'G']]))
            ->assertSessionHasErrors('options');

        $this->post('/admin/polls', $this->data(['options' => ['Ya', 'ya']]))
            ->assertSessionHasErrors('options.1');

        $this->post('/admin/polls', $this->data(['question' => 'Hah?']))
            ->assertSessionHasErrors('question');

        $data = $this->data();
        unset($data['allow_change']);
        $this->post('/admin/polls', $data)->assertSessionHasErrors('allow_change');

        $this->assertSame(0, Poll::count());
    }

    public function test_tanggal_berakhir_tidak_boleh_lewat(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/polls', $this->data(['ends_at' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, Poll::count());
    }

    public function test_admin_menutup_polling(): void
    {
        $poll = $this->buatPolling();

        $this->actingAs($this->admin())
            ->post("/admin/polls/{$poll->id}/close")
            ->assertSessionHasNoErrors();

        $poll->refresh();
        $this->assertNotNull($poll->closed_at);
        $this->assertFalse($poll->isOpen());
        $this->assertSame('closed', $poll->status());

        $log = ActivityLog::first();
        $this->assertSame('poll.closed', $log->action);
        $this->assertSame($poll->question, $log->subject_title);
    }

    public function test_polling_yang_sudah_ditutup_tidak_bisa_ditutup_lagi(): void
    {
        $poll = $this->buatPolling(['closed_at' => now()->subHour()]);

        $this->actingAs($this->admin())
            ->post("/admin/polls/{$poll->id}/close")
            ->assertSessionHasErrors('poll');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_admin_memperpanjang_polling(): void
    {
        $poll = $this->buatPolling(['ends_at' => now()->addDays(2)]);
        $baru = now()->addDays(9)->toDateString();

        $this->actingAs($this->admin())
            ->patch("/admin/polls/{$poll->id}/end", ['ends_at' => $baru])
            ->assertSessionHasNoErrors();

        $this->assertSame($baru, $poll->fresh()->ends_at->toDateString());
        $this->assertSame('poll.extended', ActivityLog::first()->action);

        $this->patch("/admin/polls/{$poll->id}/end", ['ends_at' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_polling_tertutup_tidak_bisa_diperpanjang(): void
    {
        $poll = $this->buatPolling(['closed_at' => now()]);
        $asli = $poll->ends_at->toDateString();

        $this->actingAs($this->admin())
            ->patch("/admin/polls/{$poll->id}/end", ['ends_at' => now()->addDays(20)->toDateString()])
            ->assertSessionHasErrors('poll');

        $this->assertSame($asli, $poll->fresh()->ends_at->toDateString());
    }

    public function test_hapus_polling_ikut_menghapus_pilihan_dan_suara(): void
    {
        $poll = $this->buatPolling();
        $opsi = $poll->options->first();

        DB::table('poll_votes')->insert([
            'poll_id'        => $poll->id,
            'poll_option_id' => $opsi->id,
            'user_id'        => $this->siswa()->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/polls/{$poll->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Poll::count());
        $this->assertSame(0, DB::table('poll_options')->count());
        $this->assertSame(0, DB::table('poll_votes')->count());

        $log = ActivityLog::first();
        $this->assertSame('poll.deleted', $log->action);
        $this->assertSame('Setuju jam masuk diundur?', $log->subject_title);
    }

    public function test_siswa_dan_tamu_tidak_bisa_mengelola_polling(): void
    {
        $poll = $this->buatPolling();

        $this->post('/admin/polls', $this->data())->assertRedirect(route('login'));

        $this->actingAs($this->siswa())
            ->post('/admin/polls', $this->data())
            ->assertForbidden();

        $this->post("/admin/polls/{$poll->id}/close")->assertForbidden();
        $this->delete("/admin/polls/{$poll->id}")->assertForbidden();
        $this->get('/admin/polls')->assertForbidden();

        $this->assertSame(1, Poll::count());
        $this->assertTrue($poll->fresh()->isOpen());
    }

    public function test_daftar_polling_mengirim_hasil(): void
    {
        $poll = $this->buatPolling();
        [$ya, $tidak] = $poll->options->all();

        foreach ([$ya, $ya] as $opsi) {
            DB::table('poll_votes')->insert([
                'poll_id'        => $poll->id,
                'poll_option_id' => $opsi->id,
                'user_id'        => $this->siswa()->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get('/admin/polls')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Polls/Index')
                ->has('polls', 1)
                ->where('hasOpen', true)
                ->where('polls.0.status', 'open')
                ->where('polls.0.total_votes', 2)
                ->has('polls.0.options', 2)
                ->where('polls.0.options.0.votes', 2)
                ->where('polls.0.options.1.votes', 0));
    }
}