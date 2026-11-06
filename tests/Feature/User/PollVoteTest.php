<?php

namespace Tests\Feature\User;

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PollVoteTest extends TestCase
{
    use RefreshDatabase;

    private function siswa(): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function buatPolling(array $attr = []): Poll
    {
        $poll = Poll::create(array_merge([
            'question'     => 'Setuju jam masuk diundur?',
            'allow_change' => false,
            'ends_at'      => now()->addDays(3),
        ], $attr));

        foreach (['Ya', 'Tidak', 'Netral'] as $i => $label) {
            $poll->options()->create(['label' => $label, 'position' => $i]);
        }

        return $poll->load('options');
    }

    public function test_siswa_bisa_memilih(): void
    {
        $poll = $this->buatPolling();
        $siswa = $this->siswa();

        $this->actingAs($siswa)
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options[0]->id])
            ->assertSessionHasNoErrors();

        $vote = PollVote::firstOrFail();
        $this->assertSame($poll->id, $vote->poll_id);
        $this->assertSame($poll->options[0]->id, $vote->poll_option_id);
        $this->assertSame($siswa->id, $vote->user_id);
    }

    public function test_suara_bisa_diganti_tetap_satu_suara(): void
    {
        $poll = $this->buatPolling(['allow_change' => true]);
        [$ya, $tidak] = $poll->options->all();

        $this->actingAs($this->siswa())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id])
            ->assertSessionHasNoErrors();

        $this->post("/polls/{$poll->id}/vote", ['option_id' => $tidak->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PollVote::count());
        $this->assertSame($tidak->id, PollVote::first()->poll_option_id);
    }

    public function test_suara_final_tidak_bisa_diubah(): void
    {
        $poll = $this->buatPolling(['allow_change' => false]);
        [$ya, $tidak] = $poll->options->all();

        $this->actingAs($this->siswa())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id])
            ->assertSessionHasNoErrors();

        $this->post("/polls/{$poll->id}/vote", ['option_id' => $tidak->id])
            ->assertSessionHasErrors('poll');

        $this->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id])
            ->assertSessionHasErrors('poll');

        $this->assertSame(1, PollVote::count());
        $this->assertSame($ya->id, PollVote::first()->poll_option_id);
    }

    public function test_suara_siswa_berbeda_dihitung_terpisah(): void
    {
        $poll = $this->buatPolling();
        $ya = $poll->options[0];

        $this->actingAs($this->siswa())->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id]);
        $this->actingAs($this->siswa())->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id]);

        $this->assertSame(2, PollVote::where('poll_option_id', $ya->id)->count());
    }

    public function test_pilihan_dari_polling_lain_ditolak(): void
    {
        $poll = $this->buatPolling();
        $lain = $this->buatPolling(['closed_at' => now()]);

        $this->actingAs($this->siswa())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $lain->options[0]->id])
            ->assertSessionHasErrors('option_id');

        $this->assertSame(0, PollVote::count());
    }

    public function test_polling_yang_ditutup_menolak_suara(): void
    {
        $poll = $this->buatPolling(['closed_at' => now()]);

        $this->actingAs($this->siswa())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options[0]->id])
            ->assertSessionHasErrors('poll');

        $this->assertSame(0, PollVote::count());
    }

    public function test_polling_yang_sudah_berakhir_menolak_suara(): void
    {
        $poll = $this->buatPolling(['ends_at' => now()->subDay()]);

        $this->actingAs($this->siswa())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options[0]->id])
            ->assertSessionHasErrors('poll');

        $this->assertSame(0, PollVote::count());
    }

    public function test_admin_dan_tamu_tidak_bisa_memilih(): void
    {
        $poll = $this->buatPolling();
        $data = ['option_id' => $poll->options[0]->id];

        $this->post("/polls/{$poll->id}/vote", $data)->assertRedirect(route('login'));

        $this->actingAs($this->admin())
            ->post("/polls/{$poll->id}/vote", $data)
            ->assertForbidden();

        $this->assertSame(0, PollVote::count());
    }

    public function test_beranda_menyembunyikan_hasil_sebelum_memilih(): void
    {
        $poll = $this->buatPolling();
        PollVote::create([
            'poll_id' => $poll->id, 'poll_option_id' => $poll->options[0]->id, 'user_id' => $this->siswa()->id,
        ]);

        $this->actingAs($this->siswa())
            ->get('/user/beranda')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('poll.id', $poll->id)
                ->where('poll.can_vote', true)
                ->where('poll.my_option_id', null)
                ->where('poll.show_results', false)
                ->where('poll.total_votes', null)
                ->where('poll.options.0.votes', null)
                ->where('poll.options.0.percent', null));
    }

    public function test_beranda_menampilkan_hasil_setelah_memilih(): void
    {
        $poll = $this->buatPolling();
        [$ya, $tidak] = $poll->options->all();
        $saya = $this->siswa();

        PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $tidak->id, 'user_id' => $this->siswa()->id]);

        $this->actingAs($saya)->post("/polls/{$poll->id}/vote", ['option_id' => $ya->id]);

        $this->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('poll.my_option_id', $ya->id)
                ->where('poll.show_results', true)
                ->where('poll.total_votes', 2)
                ->where('poll.options.0.votes', 1)
                ->where('poll.options.0.percent', 50)
                ->where('poll.options.1.percent', 50)
                ->where('poll.options.2.percent', 0));
    }

    public function test_admin_melihat_hasil_tanpa_bisa_memilih(): void
    {
        $poll = $this->buatPolling();
        PollVote::create([
            'poll_id' => $poll->id, 'poll_option_id' => $poll->options[0]->id, 'user_id' => $this->siswa()->id,
        ]);

        $this->actingAs($this->admin())
            ->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page
                ->where('poll.can_vote', false)
                ->where('poll.show_results', true)
                ->where('poll.total_votes', 1)
                ->where('poll.options.0.votes', 1));
    }

    public function test_tanpa_polling_aktif_tidak_ada_kartu(): void
    {
        $this->buatPolling(['closed_at' => now()]);
        $this->buatPolling(['ends_at' => now()->subDay()]);

        $this->actingAs($this->siswa())
            ->get('/user/beranda')
            ->assertInertia(fn (Assert $page) => $page->where('poll', null));
    }
}