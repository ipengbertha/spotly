<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CommentReceived;
use App\Notifications\PostSuspended;
use App\Notifications\ReportReviewed;
use App\Notifications\ReportSubmitted;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data demo untuk fitur interaksi: bio siswa, like, komentar, laporan, dan postingan ditangguhkan.
 * Dijalankan SETELAH PostSeeder. Semua tanggal relatif terhadap hari ini.
 */
class DemoActivitySeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::where('role', 'admin')->orderBy('id')->first();
        $students = User::where('role', 'user')->orderBy('id')->get();

        if (! $admin || $students->count() < 3 || Post::count() === 0) {
            $this->command?->warn('Admin, siswa, atau postingan belum ada. Jalankan UserSeeder dan PostSeeder dulu.');
            return;
        }

        $this->bios($students);
        $this->likes($students);
        $this->comments($students);
        $this->suspendedPost($admin, $students);
        $this->reports($admin, $students);
    }

    // Bio singkat supaya halaman profil tidak kosong
    private function bios(Collection $students): void
    {
        $bios = [
            'Ivana' => 'Suka menulis dan fotografi. Anggota tim jurnalistik.',
            'Budi'  => 'Kapten tim basket. Doyan baca komik.',
            'Citra' => 'Anggota tim robotik dan pecinta musik.',
            'Dimas' => 'Suka mendesain poster dan bermain futsal.',
            'Elsa'  => 'Aktif di PMR dan kegiatan kebersihan sekolah.',
        ];

        foreach ($students as $s) {
            if (empty($s->bio) && isset($bios[$s->name])) {
                $s->forceFill(['bio' => $bios[$s->name]])->save();
            }
        }
    }

    // Jumlah suka bervariasi per postingan tayang; tidak ada yang menyukai postingannya sendiri
    private function likes(Collection $students): void
    {
        $pattern = [4, 3, 2, 4, 1, 3, 2, 0];

        Post::active()->orderBy('id')->get()->each(function (Post $post, int $i) use ($students, $pattern) {
            $ownerIdx = $students->search(fn ($s) => $s->id === $post->user_id);
            $ownerIdx = $ownerIdx === false ? 0 : $ownerIdx;
            $want     = $pattern[$i % count($pattern)];
            $given    = 0;

            for ($step = 1; $step < $students->count() && $given < $want; $step++) {
                $liker = $students[($ownerIdx + $step) % $students->count()];

                if ($liker->id === $post->user_id) {
                    continue;
                }

                Like::firstOrCreate(['post_id' => $post->id, 'user_id' => $liker->id]);
                $given++;
            }
        });
    }

    // Beberapa komentar pada postingan tayang. Penulis komentar selalu orang lain, bukan pemilik
    // postingan, dan pemilik mendapat notifikasi seperti aslinya. Dijalankan ulang tidak menggandakan.
    private function comments(Collection $students): void
    {
        if (Comment::exists()) {
            return;
        }

        $texts = [
            'Terima kasih infonya, sangat membantu!',
            'Wah, ini menarik. Aku pasti ikut.',
            'Apakah ada yang tahu detail waktunya?',
            'Semoga acaranya berjalan lancar ya.',
            'Setuju banget, semoga segera ada tindak lanjutnya.',
            'Keren! Ditunggu informasi selanjutnya.',
            'Boleh minta info kontak panitianya?',
            'Mantap, jangan sampai terlewat.',
        ];
        $pattern = [3, 2, 1, 0, 2, 1, 0, 2];
        $n       = 0;

        Post::active()->with('user')->orderBy('id')->get()->each(function (Post $post, int $i) use ($students, $texts, $pattern, &$n) {
            $want       = $pattern[$i % count($pattern)];
            $commenters = $students->where('id', '!=', $post->user_id)->values();

            for ($k = 0; $k < $want && $commenters->isNotEmpty(); $k++) {
                $commenter = $commenters[($i + $k) % $commenters->count()];
                $at        = now()->subHours($i + ($want - $k) * 2)->subMinutes($k * 7);

                $comment = Comment::forceCreate([
                    'post_id'    => $post->id,
                    'user_id'    => $commenter->id,
                    'content'    => $texts[$n++ % count($texts)],
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);

                if ($post->user?->wantsNotification('comment_received')) {
                    $post->user->notify(new CommentReceived($post, $comment->setRelation('user', $commenter)));
                }
            }
        });
    }

    // Contoh postingan yang sudah ditangguhkan admin (muncul di Kiriman Saya penulisnya)
    private function suspendedPost(User $admin, Collection $students): void
    {
        $title = 'Promo Bimbel Murah, Hubungi Nomor Ini';

        if (Post::where('title', $title)->exists()) {
            return;
        }

        $author = $students->firstWhere('name', 'Elsa') ?? $students->last();
        $catId  = Category::where('slug', 'pengumuman')->value('id') ?? Category::value('id');

        $post = Post::forceCreate([
            'user_id'           => $author->id,
            'category_id'       => $catId,
            'title'             => $title,
            'slug'              => Str::slug($title) . '-' . Str::lower(Str::random(5)),
            'content'           => "Bimbel murah untuk semua mata pelajaran, diskon khusus siswa sekolah ini.\n\nHubungi nomor di brosur untuk pendaftaran.",
            'status'            => Post::SUSPENDED,
            'suspension_reason' => 'Spam atau promosi. Iklan bimbingan belajar tidak sesuai dengan tujuan mading sekolah.',
            'published_at'      => now()->subDays(3),
            'expired_at'        => now()->addDays(10),
            'is_pinned'         => false,
            'created_at'        => now()->subDays(4),
            'updated_at'        => now()->subDay(),
        ]);

        foreach ($students->where('id', '!=', $author->id)->take(2) as $n => $reporter) {
            $report = $this->makeReport($post, $reporter, 'spam', 'Ini iklan, bukan informasi sekolah.', now()->subDays(2)->addHours($n), [
                'status'      => Report::UPHELD,
                'admin_note'  => 'Iklan bimbel tidak boleh dipasang di mading.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDay(),
            ]);

            $reporter->notify(new ReportReviewed($report->load('post')));
        }

        $author->notify(new PostSuspended($post));
    }

    private function reports(User $admin, Collection $students): void
    {
        // 1) Dua laporan MENUNGGU pada satu postingan, supaya antrean admin tidak kosong
        $opini = Post::where('title', 'Opini: Perlukah Jam Masuk Sekolah Diundur?')->first();

        if ($opini) {
            $first = null;
            $items = [
                ['Citra', 'inappropriate', 'Bahasanya terlalu menyudutkan beberapa guru.', now()->subHours(5)],
                ['Dimas', 'other', 'Mohon dicek lagi isinya sebelum tetap tayang.', now()->subHours(2)],
            ];

            foreach ($items as [$name, $reason, $note, $at]) {
                $reporter = $students->firstWhere('name', $name);

                if ($reporter && $reporter->id !== $opini->user_id) {
                    $report = $this->makeReport($opini, $reporter, $reason, $note, $at);
                    $first ??= $report;
                }
            }

            // Seperti aslinya: admin hanya diberi tahu pada laporan pertama
            if ($first) {
                $admin->notify(new ReportSubmitted($opini, $first->load('reporter')));
            }
        }

        // 2) Satu laporan yang SUDAH DITINJAU dan tidak ada pelanggaran, supaya Riwayat tidak kosong
        $kerjaBakti = Post::where('title', 'Kerja Bakti Lingkungan Sekolah Sabtu Ini')->first();
        $reporter   = $students->firstWhere('name', 'Ivana');

        if ($kerjaBakti && $reporter && $reporter->id !== $kerjaBakti->user_id) {
            $report = $this->makeReport($kerjaBakti, $reporter, 'spam', 'Sepertinya pengumuman ganda.', now()->subDays(2), [
                'status'      => Report::DISMISSED,
                'admin_note'  => 'Sudah dicek: ini pengumuman resmi dari OSIS.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDay(),
            ]);

            $reporter->notify(new ReportReviewed($report->load('post')));
        }
    }

    private function makeReport(Post $post, User $reporter, string $reason, string $note, $at, array $extra = []): Report
    {
        return Report::forceCreate(array_merge([
            'post_id'    => $post->id,
            'user_id'    => $reporter->id,
            'reason'     => $reason,
            'note'       => $note,
            'status'     => Report::PENDING,
            'created_at' => $at,
            'updated_at' => $at,
        ], $extra));
    }
}