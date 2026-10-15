# Spotly: Dokumen Serah Terima (2 Oktober 2026)

Proyek UKK: mading digital sekolah. Stack: Laravel 12, Inertia.js + React, Tailwind v4, SQLite.
Repo: github.com/ipengbertha/spotly

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
- Tampilan baru: rail ikon navy melayang, header pill putih, kartu putih membulat.
  Warna: ink, brand (merah muda), grape (ungu), peach, cream.

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

Tes otomatis: php artisan test (seluruh suite). Berkas tes ada di tests/Feature/Admin
dan tests/Feature/User (termasuk ExtraCategoryTest, PostArchiveTest, PostPinTest,
CategoryManagementTest, UserManagementTest).

## Belum diverifikasi (jangan dianggap pasti jalan)

- Di browser: alur kategori tambahan (form buat/edit siswa, kartu menampilkan
  banyak kategori), hapus kategori dengan kotak pemindahan, nonaktifkan akun
  (login ditolak, sesi aktif dikeluarkan), Setujui/Tolak setelah desain baru
- Halaman Postingan Saya dan tampilan layar sempit (drawer mobile)

## Pembaruan (15 Oktober 2026)

Tenggat UKK: 4 Januari 2027.

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
- Seeder data realistis sudah ada (PostSeeder 14 postingan, DemoActivitySeeder: like,
  simpanan, komentar, laporan). README sudah diperbarui (fitur, penjadwal, tes).
- Berkas nyasar: tests/CommentTest.php (32 tes) berbeda dari tests/Feature/CommentTest.php
  (44 tes) dan tidak ikut dijalankan karena di luar folder suite. Kemungkinan salinan lama; periksa lalu hapus.
- Catatan lingkungan: folder resource bernama js/Pages (huruf besar). Di Linux, pengecekan
  halaman Inertia pada tes mencari js/pages, jadi tes bisa gagal di server/CI Linux.

## Belum dikerjakan

- Tampilan form postingan siswa (PostForm.jsx) masih gaya slate lama, belum selaras
  dengan tema Spotly
- Modal Lihat di Review, tabel Postingan, dan Arsip masih menampilkan kategori
  utama saja
- Home.jsx (landing) belum dicek: kalau mencetak p.category langsung, hanya
  menampilkan kategori utama
- Pagination halaman Pengguna belum diseragamkan
- Menu rail redup "segera": hanya Profil di sisi siswa (UserLayout). Sisi admin tidak
  punya lagi.
- 2 tes DashboardTest user (opsional)

## Catatan teknis

- Setelah clone: composer install, npm install, salin .env, php artisan key:generate,
  php artisan migrate (ada migration is_active di users dan tabel category_post).
- Tes memakai Category::create([...]) dan User::factory()->create(['role' => ...]).
- User.php punya $attributes = ['is_active' => true] supaya user buatan factory aktif.
- Jangan pakai migrate:fresh di database yang berisi data.