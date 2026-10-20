# Spotly: Dokumen Serah Terima (diperbarui 20 Oktober 2026)

Proyek UKK: mading digital sekolah. Stack: Laravel 12, Inertia.js + React, Tailwind v4, SQLite.
Repo: github.com/ipengbertha/spotly
Tenggat UKK: 4 Januari 2027.

## Aturan kerja (wajib diikuti)

1. Aturan #10: keputusan teknis disepakati dulu dengan saya sebelum lanjut.
2. Setiap kali menyuruh saya mengganti, mengubah, atau membuat sesuatu, sebutkan
   NAMA FILE LENGKAP dengan foldernya dan LETAKNYA. Lebih baik beri seluruh isi
   file untuk ditimpa daripada potongan.
3. Jangan menebak isi file yang belum dilihat. Minta saya menempelnya dulu.
4. Jelaskan dengan bahasa sederhana, jangan terlalu teknis.
5. Nama kelas Tailwind v4: bg-linear-to-br (bukan bg-gradient-to-br), scrollbar-thin.
6. Ada dua file bernama PostController.php (Admin/ dan User/). Selalu sebut foldernya.
   Pernah tertukar dan membuat error "Cannot declare class".

## Struktur penting

- Peran: admin dan user. Middleware role:admin, role:user, EnsureUserIsActive
  (mengeluarkan akun nonaktif).
- Status postingan: draft, submitted, published, rejected, archived
  (konstanta di App\Models\Post). Tayang = scopeActive, arsip = scopeArchive.
- Mading di /user/beranda bisa dibuka admin dan user (route di luar grup
  admin/user; FeedController mengirim isAdmin, Beranda.jsx memilih layout).
- Kategori postingan: satu kategori UTAMA (posts.category_id, wajib) + maksimal
  2 kategori TAMBAHAN (tabel category_post, opsional). Post::categoryNames()
  mengembalikan semuanya. Filter Mading dan landing mencocokkan utama atau tambahan.
- Kategori bawaan "Lainnya" (slug lainnya): dibuat otomatis lewat
  Category::fallback(), tidak bisa diubah atau dihapus.
- Hapus kategori: postingan yang kategori utamanya dihapus dan punya kategori
  tambahan otomatis memakai kategori tambahan pertamanya. Yang tidak punya
  dipindah ke tujuan pilihan admin, atau ke "Lainnya".
- Logo: komponen Logo.jsx (varian wordmark, full, icon), gambar di public/images/.
- Tampilan: rail ikon navy melayang, header pill putih, kartu putih membulat.
  Warna: ink, brand (merah muda), grape (ungu), peach, cream.
- Isi artikel disimpan sebagai HTML dari editor. Semua urusannya ada di
  app/Support/PostHtml.php (bersihkan sebelum simpan, tampilkan, ambil teks polos).
  Artikel lama berupa teks biasa tetap tampil lewat isLegacy(), tanpa migrasi data.
- Form postingan siswa: komponen bersama resources/js/Components/PostForm.jsx,
  dipakai halaman Buat (User/PostCreate) dan Edit (User/PostEdit).
- Rute siswa ada di grup "Area User" di routes/web.php (awalan /user, nama user.*).

## Sudah dikerjakan

Tahap 1-4: setup, register/login (throttle), role, CRUD postingan siswa, kirim
untuk review, landing page.

Tahap 5 (admin):
- 5A: dashboard admin dan antrean review
- 5B: setujui (tanggal akhir tayang wajib) dan tolak (alasan 10-500 karakter)
- 5C: halaman Postingan (/admin/posts): ubah masa tayang, pin maksimal 3
  (transaksi + kunci baris), pin tersambung ke Spotlight dan urutan Mading
- 5D: kelola Kategori (/admin/categories) dan Pengguna (/admin/users:
  nonaktifkan/aktifkan akun; akun nonaktif ditolak login dan sesinya dikeluarkan)
- 5E: Arsipkan (pin dilepas), halaman Arsip (/admin/archive, dengan pencarian),
  Tayangkan lagi, Hapus permanen

Tambahan setelah 5E:
- Hapus kategori dengan pemindahan postingan (pilih tujuan atau otomatis ke Lainnya)
- Kategori tambahan per postingan (maks 2) di form siswa, kartu Mading,
  Postingan Saya, dan filter kategori
- Dashboard user dan admin bergaya referensi; sidebar rail; header pill
- Halaman Review, Postingan, Arsip, Kategori: kotak keterangan highlight,
  tombol aksi satu baris, pagination selalu tampil berbahasa Indonesia
- Pencarian di header admin: Mading, Pengguna, Review, Arsip

Pembaruan 15 Oktober 2026:
- Fitur komentar, saklar komentar (penulis), dan moderasi komentar admin.
- Tahap 1A (kenyamanan membaca): estimasi waktu baca, ukuran font dan mode gelap
  di halaman baca, pencarian langsung dan filter periode di Mading, bagikan
  (salin tautan dan WhatsApp), Simpan artikel (hanya siswa login) + halaman
  /user/tersimpan.
- Arsip otomatis: perintah `php artisan posts:archive-expired` mengarsipkan postingan
  published yang masa tayangnya habis dan melepas pin. Dijadwalkan tiap jam di
  routes/console.php. Lokal: jalankan `php artisan schedule:work` di terminal terpisah
  (atau jalankan perintahnya sekali sebelum demo); server: cron `schedule:run`.
  Mading dan halaman Arsip tetap akurat tanpa penjadwal karena membaca lewat waktu.
- Timezone config/app.php sudah 'Asia/Jakarta'.
- Seeder data realistis (PostSeeder 14 postingan, DemoActivitySeeder: like,
  simpanan, komentar, laporan). README sudah diperbarui (fitur, penjadwal, tes).

Pembaruan 16-20 Oktober 2026 (dari riwayat commit):
- Reaksi emoji dan komentar bertingkat (balasan tanpa batas), username di komentar.
- Menu komentar: edit (24 jam), jempol, laporan komentar.
- Riwayat bacaan (/user/riwayat), artikel terkait, tombol lihat/sembunyikan komentar.
- Editor artikel rich text (TipTap): tebal, miring, garis bawah, judul H2/H3, daftar,
  kutipan, tautan (dibuka di tab baru), tabel. HTML dibersihkan di server dengan
  mews/purifier lewat PostHtml.php.
- Gambar di dalam artikel: tombol "Gambar" di toolbar. Unggah JPG/PNG/WEBP maks 2 MB
  ke POST /user/post-images (PostImageController, hanya siswa, dibatasi 20 unggahan
  per menit). Pembersih hanya meloloskan gambar dari /storage/post-images/, gambar
  dari situs lain dibuang.
- Video di dalam artikel: tombol "Video", hanya tautan YouTube (youtube.com, youtu.be,
  shorts, embed), diubah jadi pemutar youtube-nocookie.com. Iframe lain dibuang di server.
- Autosave draf: PostForm menyimpan otomatis 3 detik setelah siswa berhenti mengetik,
  lewat POST /user/posts/autosave (PostController@autosave, JSON). Syarat: judul,
  kategori utama, dan isi terisi. Gambar sampul tidak ikut autosave. Hanya draf dan
  ditolak milik sendiri yang bisa disimpan (PostPolicy::update). Tanda "Tersimpan
  otomatis pukul ..." tampil di samping tombol.

Tes otomatis: `php artisan test` (seluruh suite), terakhir 325 lulus. Berkas tes ada di
tests/Feature, tests/Feature/Admin, dan tests/Feature/User (termasuk PostMediaTest untuk
gambar dan video, PostAutosaveTest untuk autosave).

## Rencana sampai tenggat (8 minggu, mulai Minggu 1 = pertengahan Oktober)

| Minggu | Fokus | Status |
|---|---|---|
| 1 | Tutup bagian A, cek B dan F | sebagian (editor jalan, rekomendasi artikel belum dipastikan) |
| 2-3 | Editor rich text, gambar, video, autosave, pratinjau | editor, gambar, video, autosave selesai; pratinjau belum |
| 4 | Status "perlu revisi" terpisah dari "ditolak", portofolio di profil | belum |
| 5 | Jadwal terbit dan penarikan artikel, analitik dasar, log aktivitas | belum |
| 6 | Polling dengan grafik, pertanyaan mingguan, kotak aspirasi | belum |
| 7 | Poin, badge, leaderboard, streak | belum |
| 8 | Voting karya terbaik, bug, dokumentasi | belum |

Sengaja ditunda: riwayat versi, penyuntingan bersama, Kanban, kalender editorial,
komentar internal, kuis, forum, level, tantangan antarkelas, penukaran poin.

## Belum diverifikasi (jangan dianggap pasti jalan)

- Di browser: autosave (indikator tersimpan, tidak membuat draf kembar, pesan offline).
- Di browser: klik tautan di halaman baca terbuka di tab baru, modal Lihat di Review
  admin untuk artikel seeder, tabel dan pemutar video di layar sempit.
- Di browser: alur kategori tambahan, hapus kategori dengan kotak pemindahan,
  nonaktifkan akun, Setujui/Tolak setelah desain baru.
- Halaman Postingan Saya dan tampilan layar sempit (drawer mobile).

## Belum dikerjakan

- Pratinjau artikel (tombol biasa yang menampilkan isi seperti di halaman baca)
- Rekomendasi artikel (belum ada di kode; artikel terkait sudah ada)
- Status "perlu revisi" terpisah dari "ditolak"; portofolio di profil
- Pembersihan gambar dalam artikel yang sudah dihapus dari editor (file tetap di disk,
  sengaja ditunda karena tidak mendesak)
- Modal Lihat di Review, tabel Postingan, dan Arsip masih menampilkan kategori
  utama saja
- Home.jsx (landing) belum dicek: kalau mencetak p.category langsung, hanya
  menampilkan kategori utama
- Pagination halaman Pengguna belum diseragamkan
- Menu rail redup "segera": hanya Profil di sisi siswa (UserLayout)
- 2 tes DashboardTest user (opsional)
- Nama tes lama "links open in new tab and images are not allowed" (PostContentTest)
  perlu diganti agar sesuai: sekarang gambar dari penyimpanan sendiri boleh
- Catatan fitur lama belum seluruhnya tercatat di sini; rapikan lewat `git log --oneline`

## Catatan teknis

- Setelah clone: composer install, npm install, salin .env, php artisan key:generate,
  php artisan migrate (ada migration is_active di users dan tabel category_post),
  php artisan storage:link (supaya gambar unggahan bisa dibuka lewat /storage/...).
- Tes memakai Category::create([...]) (wajib menyertakan slug, Category tidak punya
  factory) dan User::factory()->create(['role' => ...]).
- User.php punya $attributes = ['is_active' => true] supaya user buatan factory aktif.
- Jangan pakai migrate:fresh di database yang berisi data.
- Berkas nyasar: tests/CommentTest.php (32 tes) berbeda dari tests/Feature/CommentTest.php
  (44 tes) dan tidak ikut dijalankan karena di luar folder suite. Kemungkinan salinan
  lama; periksa lalu hapus.
- Folder resource bernama js/Pages (huruf besar). Di Linux, pengecekan halaman Inertia
  pada tes mencari js/pages, jadi tes bisa gagal di server/CI Linux.
- Paket baru: @tiptap/extension-image, @tiptap/extension-youtube (npm), mews/purifier (composer).