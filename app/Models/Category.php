<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // Kategori bawaan sistem: tujuan otomatis postingan saat kategori dihapus
    public const FALLBACK_SLUG = 'lainnya';
    public const FALLBACK_NAME = 'Lainnya';

    protected $fillable = ['name', 'slug'];

    // Postingan yang memakai kategori ini sebagai kategori UTAMA
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Postingan yang memakai kategori ini sebagai kategori TAMBAHAN
    public function extraPosts()
    {
        return $this->belongsToMany(Post::class, 'category_post');
    }

    // Ambil kategori "Lainnya"; dibuat otomatis kalau belum ada
    public static function fallback(): self
    {
        return static::firstOrCreate(
            ['slug' => self::FALLBACK_SLUG],
            ['name' => self::FALLBACK_NAME],
        );
    }

    public function isFallback(): bool
    {
        return $this->slug === self::FALLBACK_SLUG;
    }
}