<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', '!=', 'admin')->orderBy('id')->get();
        $cat      = Category::pluck('id', 'slug'); // slug => id

        if ($students->isEmpty() || $cat->isEmpty()) {
            $this->command?->warn('Siswa atau kategori belum ada. Jalankan UserSeeder dan CategorySeeder dulu.');
            return;
        }

        // status: published | submitted | rejected | draft | archived
        // pub  = berapa hari lalu tayang, exp = berapa hari lagi kedaluwarsa (negatif = sudah lewat)
        $rows = [
            // ---- Tayang, ber-pin (maks. 3) ----
            ['title' => 'Pendaftaran Ekstrakurikuler Semester Genap Dibuka',
             'cat' => 'pengumuman', 'extra' => ['ekstrakurikuler', 'kegiatan-sekolah'],
             'status' => 'published', 'pub' => 2, 'exp' => 28, 'pin' => true,
             'content' => "Pendaftaran ekstrakurikuler semester genap resmi dibuka mulai hari ini hingga akhir bulan.\n\nSiswa dapat memilih maksimal dua ekstrakurikuler dan mendaftar langsung ke pembina masing-masing. Pilihan yang tersedia meliputi Pramuka, PMR, Paskibra, Basket, Futsal, Jurnalistik, dan Robotik.\n\nPastikan jadwal latihan tidak bentrok dengan jam pelajaran tambahan. Informasi lengkap bisa ditanyakan ke ruang OSIS."],

            ['title' => 'Tim Basket Putra Juara 1 Turnamen Antar-SMA',
             'cat' => 'prestasi', 'extra' => ['ekstrakurikuler'],
             'status' => 'published', 'pub' => 5, 'exp' => 40, 'pin' => true,
             'content' => "Tim basket putra sekolah kita berhasil meraih juara 1 dalam turnamen antar-SMA tingkat kota.\n\nDi partai final, tim menang dengan skor 58-51 setelah tertinggal di kuarter pertama. Pelatih menyebut kekompakan dan latihan rutin sebagai kunci kemenangan.\n\nSelamat kepada seluruh pemain dan pelatih. Piala akan dipajang di lobi sekolah."],

            ['title' => 'Jadwal Penilaian Tengah Semester',
             'cat' => 'pengumuman', 'extra' => [],
             'status' => 'published', 'pub' => 1, 'exp' => 14, 'pin' => true,
             'content' => "Penilaian tengah semester akan dilaksanakan pada minggu depan, Senin sampai Kamis.\n\nSiswa diminta hadir 15 menit sebelum jam pertama dan membawa kartu ujian. Kartu ujian dapat diambil di wali kelas masing-masing.\n\nSiswa yang berhalangan hadir karena sakit wajib menyerahkan surat keterangan."],

            // ---- Tayang, biasa ----
            ['title' => 'Lomba Poster Digital Bulan Bahasa',
             'cat' => 'info-lomba', 'extra' => ['karya-siswa'],
             'status' => 'published', 'pub' => 3, 'exp' => 21, 'pin' => false,
             'content' => "Panitia Bulan Bahasa membuka lomba poster digital dengan tema \"Bahasa Kita, Bangga Kita\".\n\nKarya dikirim dalam format JPG atau PNG, ukuran maksimal 5 MB, dan dikumpulkan ke panitia paling lambat dua minggu dari sekarang. Setiap siswa boleh mengirim satu karya.\n\nPemenang mendapat sertifikat dan hadiah dari sekolah."],

            ['title' => 'Kerja Bakti Lingkungan Sekolah Sabtu Ini',
             'cat' => 'kegiatan-sekolah', 'extra' => [],
             'status' => 'published', 'pub' => 4, 'exp' => 5, 'pin' => false,
             'content' => "Seluruh kelas X dan XI diundang mengikuti kerja bakti membersihkan lingkungan sekolah hari Sabtu pukul 07.00.\n\nMasing-masing kelas mendapat area yang sudah dibagi oleh OSIS. Peralatan kebersihan disediakan sekolah, namun siswa dianjurkan membawa sarung tangan sendiri.\n\nKegiatan selesai sekitar pukul 10.00 dan dilanjutkan sarapan bersama."],

            ['title' => 'Cerpen: Hujan di Gerbang Sekolah',
             'cat' => 'karya-siswa', 'extra' => [],
             'status' => 'published', 'pub' => 6, 'exp' => 60, 'pin' => false,
             'content' => "Hujan turun sejak pagi, dan Rani masih berdiri di gerbang sekolah tanpa payung.\n\nDi seberang jalan, seorang penjual jajanan menawarkan tempat berteduh. Dari percakapan singkat itu, Rani belajar bahwa kebaikan kecil sering datang dari tempat yang tidak disangka.\n\nCerpen ini ditulis untuk tugas Bahasa Indonesia dan dibagikan di Spotly agar bisa dibaca teman-teman."],

            ['title' => 'Opini: Perlukah Jam Masuk Sekolah Diundur?',
             'cat' => 'opini', 'extra' => [],
             'status' => 'published', 'pub' => 7, 'exp' => 30, 'pin' => false,
             'content' => "Banyak siswa mengeluh mengantuk di jam pertama. Sebagian pakar menyebut remaja memang butuh tidur lebih lama.\n\nNamun jam masuk yang diundur berarti jam pulang ikut mundur, dan itu berdampak pada jadwal ekstrakurikuler serta transportasi siswa.\n\nMenurut saya, sekolah bisa mencoba uji coba satu semester lalu mengevaluasinya bersama siswa dan orang tua."],

            ['title' => 'Tim Robotik Lolos Babak Final Regional',
             'cat' => 'prestasi', 'extra' => ['info-lomba', 'ekstrakurikuler'],
             'status' => 'published', 'pub' => 9, 'exp' => 45, 'pin' => false,
             'content' => "Tim robotik sekolah berhasil lolos ke babak final kompetisi tingkat regional setelah mengalahkan 24 tim lain.\n\nRobot rancangan tim dinilai unggul pada kecepatan dan ketepatan menyelesaikan misi. Final akan digelar bulan depan.\n\nTim mengundang siswa yang ingin ikut menyemangati untuk hadir sebagai suporter."],

            // ---- Menunggu review (submitted) ----
            ['title' => 'Usulan Kantin Sehat Setiap Hari Jumat',
             'cat' => 'opini', 'extra' => ['kegiatan-sekolah'],
             'status' => 'submitted', 'pub' => null, 'exp' => null, 'pin' => false,
             'content' => "Saya mengusulkan agar kantin menyediakan menu sehat khusus hari Jumat, seperti buah potong, salad, dan jus tanpa gula tambahan.\n\nUsulan ini bisa dimulai sebagai uji coba satu bulan dan dievaluasi lewat survei singkat ke siswa."],

            ['title' => 'Open Recruitment Tim Jurnalistik Sekolah',
             'cat' => 'ekstrakurikuler', 'extra' => ['pengumuman'],
             'status' => 'submitted', 'pub' => null, 'exp' => null, 'pin' => false,
             'content' => "Tim jurnalistik membuka rekrutmen anggota baru untuk bidang tulisan, fotografi, dan desain.\n\nPendaftaran dibuka sampai Jumat. Calon anggota cukup mengirim satu contoh karya ke ruang jurnalistik."],

            // ---- Ditolak (rejected) ----
            ['title' => 'Jual Beli Seragam Bekas',
             'cat' => 'pengumuman', 'extra' => [],
             'status' => 'rejected', 'pub' => null, 'exp' => null, 'pin' => false,
             'reason' => 'Konten bersifat jual beli dan belum sesuai dengan kategori Pengumuman. Silakan ubah menjadi informasi kegiatan sekolah atau hubungi pembina.',
             'content' => "Menjual seragam bekas kelas X ukuran M dan L dengan kondisi masih bagus. Hubungi saya lewat kontak kelas."],

            // ---- Draft ----
            ['title' => 'Catatan Kunjungan Industri ke Pabrik Roti',
             'cat' => 'kegiatan-sekolah', 'extra' => [],
             'status' => 'draft', 'pub' => null, 'exp' => null, 'pin' => false,
             'content' => "Kunjungan industri minggu lalu mengajarkan banyak hal tentang proses produksi massal. (Draft, belum selesai.)"],

            // ---- Kedaluwarsa (published tapi sudah lewat -> tampil di Arsip) ----
            ['title' => 'Pengumuman Libur Semester Ganjil',
             'cat' => 'pengumuman', 'extra' => [],
             'status' => 'published', 'pub' => 40, 'exp' => -10, 'pin' => false,
             'content' => "Libur semester ganjil dimulai Sabtu depan dan berlangsung dua minggu. Kegiatan belajar dimulai kembali sesuai kalender akademik."],

            // ---- Diarsipkan manual oleh admin ----
            ['title' => 'Hasil Lomba Kebersihan Kelas Bulan Lalu',
             'cat' => 'kegiatan-sekolah', 'extra' => ['prestasi'],
             'status' => 'archived', 'pub' => 35, 'exp' => 5, 'pin' => false,
             'content' => "Kelas XI IPA 2 meraih peringkat pertama lomba kebersihan kelas bulan lalu. Penilaian mencakup kerapian, kebersihan, dan kreativitas dekorasi."],
        ];

        Post::unguard();

        foreach ($rows as $i => $r) {
            $owner = $students[$i % $students->count()];
            $pub   = isset($r['pub']) ? now()->subDays($r['pub']) : null;
            $exp   = isset($r['exp']) ? now()->addDays($r['exp']) : null;

            $post = Post::create([
                'user_id'          => $owner->id,
                'category_id'      => $cat[$r['cat']],
                'title'            => $r['title'],
                'slug'             => Str::slug($r['title']) . '-' . Str::lower(Str::random(5)),
                'content'          => $r['content'],
                'image'            => null,
                'status'           => $r['status'],
                'rejection_reason' => $r['reason'] ?? null,
                'published_at'     => $pub,
                'expired_at'       => $exp,
                'is_pinned'        => $r['pin'],
                'created_at'       => ($pub ?? now()->subDays(1))->copy()->subDay(),
                'updated_at'       => $pub ?? now()->subHours(3),
            ]);

            // Kategori tambahan (ASUMSI: relasi bernama extraCategories)
            if (! empty($r['extra'])) {
                $post->extraCategories()->sync(
                    collect($r['extra'])->map(fn ($s) => $cat[$s])->all()
                );
            }
        }

        Post::reguard();
    }
}