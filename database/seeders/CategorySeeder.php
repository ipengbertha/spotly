<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['Pengumuman', 'Prestasi', 'Info Lomba', 'Ekstrakurikuler',
          'Karya Siswa', 'Kegiatan Sekolah', 'Opini'];

    foreach ($names as $name) {
        Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }
    }
}
