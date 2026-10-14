# Spotly

Spotly adalah aplikasi **mading digital sekolah**. Siswa menulis postingan (pengumuman, prestasi, info lomba, karya, opini), admin meninjau dan menyetujuinya, lalu postingan tayang di Mading selama masa tayang yang ditentukan.

Dibuat sebagai proyek UKK dengan Laravel dan React.

## Fitur
### Siswa
- Beranda **Mading** dengan pencarian, filter kategori, dan pagination.
- Halaman **detail postingan** (`/posts/{slug}`) untuk membaca isi lengkap. Postingan yang sedang tayang juga bisa dibaca tamu yang belum login.
- Buat postingan dengan **1 kategori utama + maksimal 2 kategori tambahan** dan gambar (JPG/PNG/WEBP, maks. 2 MB).
- Simpan sebagai **draft**, lalu **kirim untuk review**.
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
- **Pengaturan Mading**, misalnya jumlah postingan per halaman.
- Pencarian dan pagination di setiap halaman daftar.
- **Like**: siswa bisa menyukai postingan yang sedang tayang (satu suka per siswa per postingan) dan melihat daftarnya di halaman **Disukai**. Tamu dan admin hanya melihat jumlah suka.
- **Profil penulis** (`/profil/{id}`): foto, bio, jumlah postingan tayang dan suka yang diterima, serta daftar postingan penulis. Nama penulis di kartu bisa diklik.

### Aturan penting
- Postingan baru selalu berstatus draft; status tidak bisa ditentukan dari form.
- Postingan yang melewati `expired_at` otomatis hilang dari Mading dan tampil di Arsip sebagai "Masa tayang habis". Pin yang kedaluwarsa tidak memakai slot.
- Postingan lebih dari tiga bulan hanya muncul di Mading lewat pencarian.
- Akun yang dinonaktifkan tidak bisa login dan sesi aktifnya diakhiri.
- Draft, menunggu review, ditolak, arsip, dan kedaluwarsa hanya bisa dibuka oleh penulisnya dan admin. Orang lain mendapat halaman 404.

## Teknologi
- PHP 8.2+, Laravel
- React dengan Inertia.js, dibangun dengan Vite
- MySQL/MariaDB (XAMPP)
- Pengujian dengan PHPUnit (`php artisan test`)
Saat ini terdapat 115 tes yang mencakup hak akses admin, kategori, review, pin dan arsip, pencarian, kategori tambahan, halaman detail postingan, notifikasi, pengaturan akun, dan manajemen pengguna.

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

Untuk build produksi: `npm run build`.

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

`PostSeeder` membuat postingan realistis dengan **tanggal relatif terhadap hari ini**: tayang, ber-pin, menunggu review, ditolak, draft, kedaluwarsa, dan diarsipkan. Sebelum presentasi, atur ulang data supaya masa tayangnya segar:

```bash
php artisan migrate:fresh --seed
```

Perintah ini **menghapus seluruh data** di database.

## Pengujian

```bash
php artisan test
```

Saat ini terdapat 107 tes yang mencakup hak akses admin, kategori, review, pin dan arsip, pencarian, kategori tambahan, notifikasi, pengaturan akun, dan manajemen pengguna.

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