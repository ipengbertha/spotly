# Spotly

Spotly adalah aplikasi **mading digital sekolah**. Siswa menulis postingan (pengumuman, prestasi, info lomba, karya, opini), admin meninjau dan menyetujuinya, lalu postingan tayang di Mading selama masa tayang yang ditentukan.

Dibuat sebagai proyek UKK dengan Laravel dan React.

## Fitur
### Siswa
- Beranda **Mading** dengan **pencarian langsung** (hasil berubah saat mengetik), filter kategori, **filter periode tayang** (7, 30, atau 90 hari terakhir), dan pagination.
- Halaman **detail postingan** (`/posts/{slug}`) untuk membaca isi lengkap. Postingan yang sedang tayang juga bisa dibaca tamu yang belum login.
- Buat postingan dengan **1 kategori utama + maksimal 2 kategori tambahan** dan gambar (JPG/PNG/WEBP, maks. 2 MB).
- Simpan sebagai **draft**, lalu **kirim untuk review**.
- Halaman baca yang nyaman: **estimasi waktu baca** (hanya label, tidak membatasi waktu baca), pengatur **ukuran font**, **mode gelap** (khusus halaman baca), serta tombol **Salin tautan** dan **WhatsApp**.
- **Komentar** pada postingan yang sedang tayang (siswa saja; batas 5 komentar per menit). Penulis bisa **mematikan komentar** di postingannya, komentar lama tetap tampil. Siswa hanya bisa menghapus komentarnya sendiri, dan penulis postingan mendapat notifikasi komentar baru.
- **Simpan artikel** (ikon pita) untuk dibaca nanti, khusus siswa yang login. Daftarnya ada di halaman **Tersimpan** (`/user/tersimpan`); artikel yang sudah tidak tayang tidak ikut ditampilkan.
- Halaman **Kiriman Saya** dengan status: draft, menunggu review, tayang, ditolak, dan arsip.
- Postingan yang ditolak menampilkan **alasan penolakan** dan bisa diperbaiki lalu **dikirim ulang**.
- **Notifikasi** saat postingan disetujui atau ditolak, dengan preferensi yang bisa diatur.
- Pengaturan profil: data diri, foto profil, dan ganti password.

### Admin
- **Antrean Review**: lihat, setujui (dengan tanggal kedaluwarsa), atau tolak (dengan alasan wajib).
- **Postingan Tayang**: atur masa tayang, **pin** postingan (maksimal 3 pin aktif), arsipkan, atau hapus.
- **Arsip**: postingan yang diarsipkan admin atau yang masa tayangnya sudah habis; bisa ditayangkan lagi atau dihapus.
- **Kategori**: tambah, ubah nama, dan hapus. Postingan dari kategori yang dihapus dipindahkan ke kategori pengganti.
- **Pengguna**: nonaktifkan dan aktifkan kembali akun siswa.
- **Komentar** (`/admin/comments`): lihat, cari, dan hapus komentar. Admin bisa menghapus komentar di postingan apa pun, tetapi tidak menulis komentar.
- **Pengaturan Mading**, misalnya jumlah postingan per halaman.
- Pencarian dan pagination di setiap halaman daftar.
- **Like**: siswa bisa menyukai postingan yang sedang tayang (satu suka per siswa per postingan) dan melihat daftarnya di halaman **Disukai**. Tamu dan admin hanya melihat jumlah suka.
- **Profil penulis** (`/profil/{id}`): foto, bio, jumlah postingan tayang dan suka yang diterima, serta daftar postingan penulis. Nama penulis di kartu bisa diklik.

### Aturan penting
- Postingan baru selalu berstatus draft; status tidak bisa ditentukan dari form.
- Postingan yang melewati `expired_at` otomatis hilang dari Mading dan tampil di Arsip sebagai "Masa tayang habis". Pin yang kedaluwarsa tidak memakai slot.
- Postingan lebih dari tiga bulan hanya muncul di Mading lewat pencarian.
- **Arsip otomatis**: perintah `php artisan posts:archive-expired` mengubah postingan tayang yang masa tayangnya habis menjadi arsip dan melepas pin-nya. Perintah ini dijadwalkan setiap jam (lihat bagian Menjalankan). Mading dan halaman Arsip tetap akurat walau penjadwal tidak jalan, karena membaca lewat waktu.
- Akun yang dinonaktifkan tidak bisa login dan sesi aktifnya diakhiri.
- Draft, menunggu review, ditolak, arsip, dan kedaluwarsa hanya bisa dibuka oleh penulisnya dan admin. Orang lain mendapat halaman 404.

## Teknologi
- PHP 8.2+, Laravel
- React dengan Inertia.js, dibangun dengan Vite
- MySQL/MariaDB (XAMPP)
- Pengujian dengan PHPUnit (`php artisan test`)
Lebih dari 240 tes otomatis (`php artisan test`) menjaga fitur-fitur di atas.

## Instalasi

Prasyarat: PHP 8.2+, Composer, Node.js, dan MySQL/MariaDB (XAMPP).

```bash
git clone <url-repositori>
cd spotly

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Buat database kosong bernama `spotly` (lewat phpMyAdmin), lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spotly
DB_USERNAME=root
DB_PASSWORD=
```

Lanjutkan:

```bash
php artisan migrate --seed
php artisan storage:link
```

`storage:link` diperlukan agar gambar postingan dan foto profil bisa tampil.

## Menjalankan

Buka dua terminal:

```bash
# Terminal 1: server Laravel
php artisan serve

# Terminal 2: aset front-end
npm run dev
```

Buka http://127.0.0.1:8000.

Terminal ketiga (opsional) untuk arsip otomatis setiap jam:

```bash
php artisan schedule:work
```

Tanpa itu, jalankan `php artisan posts:archive-expired` sekali sebelum presentasi. Di server, pakai cron `* * * * * php artisan schedule:run`.

Untuk build produksi: `npm run build`. Hentikan `npm run dev` dulu dan pastikan file `public/hot` sudah hilang (file itu dibuat oleh `npm run dev`); kalau masih ada, Laravel memuat aset dari server dev dan bukan dari hasil build.

## Akun demo

Dibuat oleh `UserSeeder`. Password semua akun demo: `password`.

| Peran | Email | Nama |
|-------|-------|------|
| Admin | `admin@spotly.test` | Admin Spotly |
| Siswa | `ivana@spotly.test` | Ivana |
| Siswa | `budi@spotly.test` | Budi |
| Siswa | `citra@spotly.test` | Citra |
| Siswa | `dimas@spotly.test` | Dimas |
| Siswa | `elsa@spotly.test` | Elsa |

Akun ini hanya untuk demo dan pengembangan. Jangan dipakai di server sungguhan.

## Data contoh

`PostSeeder` membuat postingan realistis dengan **tanggal relatif terhadap hari ini**: tayang, ber-pin, menunggu review, ditolak, draft, kedaluwarsa, dan diarsipkan. `DemoActivitySeeder` menambah bio siswa, like, simpanan, komentar, laporan, dan satu postingan yang ditangguhkan, sehingga halaman Disukai, Tersimpan, dan moderasi tidak kosong saat demo. Sebelum presentasi, atur ulang data supaya masa tayangnya segar:

```bash
php artisan migrate:fresh --seed
```

Perintah ini **menghapus seluruh data** di database.

## Pengujian

```bash
php artisan test
```

Tes mencakup hak akses admin dan siswa, kategori, review, pin dan arsip (termasuk arsip otomatis), pencarian dan filter, komentar, simpan artikel, like, laporan, notifikasi, pengaturan akun, manajemen pengguna, dan data contoh.

Catatan: tes `ExampleTest` bawaan Laravel membuka halaman depan tanpa menyiapkan database, jadi hasilnya bergantung pada database lokal yang sudah berisi tabel. Folder halaman bernama `resources/js/Pages` (huruf besar); di Linux, pengecekan komponen Inertia pada tes mencari `js/pages`, jadi tes bisa gagal di server atau CI Linux kecuali folder itu disesuaikan.

## Struktur singkat

```
app/Http/Controllers/Admin/   controller sisi admin
app/Http/Controllers/User/    controller sisi siswa
app/Http/Requests/            validasi form (PostRequest, dll.)
database/seeders/             data awal dan data contoh
resources/js/Pages/           halaman React (Admin, User, Auth)
resources/js/Layouts/         AdminLayout dan layout lainnya
tests/Feature/                tes fitur
```

## Lisensi

Proyek ini dibuat untuk keperluan pembelajaran. Laravel dilisensikan di bawah [MIT](https://opensource.org/licenses/MIT).