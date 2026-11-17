<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // nama => mode batas tayang (kosong = ada batas tanggal)
        $categories = [
            'Pengumuman'         => Category::EXPIRY_DATED,
            'Prestasi'           => Category::EXPIRY_SCHOOL_YEAR,
            'Info Lomba'         => Category::EXPIRY_DATED,
            'Ekstrakurikuler'    => Category::EXPIRY_DATED,
            'Karya Siswa'        => Category::EXPIRY_DATED,
            'Kegiatan Sekolah'   => Category::EXPIRY_DATED,
            'Opini'              => Category::EXPIRY_DATED,
            'Berita'             => Category::EXPIRY_DATED,
            'Agenda'             => Category::EXPIRY_DATED,
            'Beasiswa & Peluang' => Category::EXPIRY_DATED,
            'Tips & Edukasi'     => Category::EXPIRY_DATED,
            'Sastra'             => Category::EXPIRY_DATED,
        ];

        foreach ($categories as $name => $mode) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'expiry_mode' => $mode],
            );
        }
    }
}