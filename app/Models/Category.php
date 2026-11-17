<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Category extends Model
{
    // Kategori bawaan sistem: tujuan otomatis postingan saat kategori dihapus
    public const FALLBACK_SLUG = 'lainnya';
    public const FALLBACK_NAME = 'Lainnya';

    // Aturan batas tayang per kategori
    public const EXPIRY_DATED       = 'dated';        // admin mengisi tanggal, atau memilih "tanpa batas"
    public const EXPIRY_NONE        = 'none';         // selalu tanpa batas
    public const EXPIRY_SCHOOL_YEAR = 'school_year';  // otomatis sampai pergantian tahun ajaran

    public const EXPIRY_MODES = [
        self::EXPIRY_DATED       => 'Ada batas tanggal',
        self::EXPIRY_NONE        => 'Tanpa batas tayang',
        self::EXPIRY_SCHOOL_YEAR => 'Sampai ganti tahun ajaran',
    ];

    // Tahun ajaran berganti pada 1 Juli, jadi tayangnya berakhir 30 Juni
    public const SCHOOL_YEAR_START_MONTH = 7;

    protected $fillable = ['name', 'slug', 'expiry_mode'];

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

    // Mode batas tayang (aman kalau atributnya belum terisi)
    public function mode(): string
    {
        return $this->expiry_mode ?: self::EXPIRY_DATED;
    }

    // Akhir tahun ajaran terdekat sejak tanggal $from: 30 Juni, pukul 23.59
    public static function schoolYearEnd(?Carbon $from = null): Carbon
    {
        $from ??= now();
        $year = $from->month >= self::SCHOOL_YEAR_START_MONTH ? $from->year + 1 : $from->year;

        return Carbon::create($year, 6, 30, 0, 0, 0)->endOfDay();
    }

    /**
     * Tanggal akhir tayang sesuai mode kategori.
     * $requested hanya dipakai di mode "dated" (null = tanpa batas).
     * $from adalah tanggal mulai tayang, dipakai untuk mode tahun ajaran.
     */
    public function resolveExpiry(?Carbon $requested, ?Carbon $from = null): ?Carbon
    {
        return match ($this->mode()) {
            self::EXPIRY_NONE        => null,
            self::EXPIRY_SCHOOL_YEAR => self::schoolYearEnd($from),
            default                  => $requested,
        };
    }
}