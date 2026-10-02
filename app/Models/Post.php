<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Post extends Model
{
    use HasFactory;

    // Konstanta status supaya tidak menulis string di banyak tempat
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const PUBLISHED = 'published';
    public const REJECTED = 'rejected';
    public const ARCHIVED = 'archived';
    public const MAX_PINNED = 3;

    // status, is_pinned, dll. sengaja TIDAK di-fillable:
    // user tidak boleh mengubahnya lewat form. Diatur di controller/service.
    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'content', 'image',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expired_at'   => 'datetime',
            'is_pinned'    => 'boolean',
        ];
    }

    public function user()     { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }

        // Kategori tambahan (maksimal 2), urut sesuai waktu dipilih
    public function extraCategories()
    {
        return $this->belongsToMany(Category::class, 'category_post')->orderBy('category_post.id');
    }

    // Nama semua kategori: utama dulu, lalu tambahan.
    // Relasi category dan extraCategories harus sudah dimuat (with).
    public function categoryNames(): array
    {
        return collect([$this->category?->name])
            ->merge($this->extraCategories->pluck('name'))
            ->filter()
            ->values()
            ->all();
    }

    public function comments() { return $this->hasMany(Comment::class); }
    public function likes()    { return $this->hasMany(Like::class); }

    /**
     * Postingan AKTIF di mading: status published DAN sedang dalam masa tayang.
     * Dicek lewat waktu, jadi tetap akurat walau command arsip belum jalan.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::PUBLISHED)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            });
    }

    /**
     * Arsip: status archived ATAU published yang masa tayangnya sudah lewat.
     */
    public function scopeArchive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::ARCHIVED)
              ->orWhere(function ($q2) {
                  $q2->where('status', self::PUBLISHED)
                     ->whereNotNull('expired_at')
                     ->where('expired_at', '<=', now());
              });
        });
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->status, [self::DRAFT, self::REJECTED]);
    }

    public function isDeletableByOwner(): bool
    {
        // Hanya submitted yang dikunci (sedang direview admin).
        // Ingin mengizinkan hapus saat submitted juga? Ganti dengan: return true;
        return $this->status !== self::SUBMITTED;
    }

    // Satu-satunya jalan user mengubah status. Memakai forceFill karena
    // 'status' sengaja tidak ada di $fillable.
    public function markSubmitted(): void
    {
        $this->forceFill([
            'status' => self::SUBMITTED,
            'rejection_reason' => null, // alasan lama dihapus saat dikirim ulang
        ])->save();
    }

        // Dipanggil admin saat approve. published_at otomatis sekarang.
    public function markPublished(Carbon $expiredAt): void
    {
        $this->forceFill([
            'status'           => self::PUBLISHED,
            'published_at'     => now(),
            'expired_at'       => $expiredAt,
            'rejection_reason' => null,
        ])->save();
    }

    // Dipanggil admin saat reject. Alasan wajib supaya penulis tahu apa yang harus diperbaiki.
    public function markRejected(string $reason): void
    {
        $this->forceFill([
            'status'           => self::REJECTED,
            'rejection_reason' => $reason,
        ])->save();
    }

    // Sedang tayang sekarang (sama dengan scopeActive, tapi untuk satu objek)
    public function isActive(): bool
    {
        return $this->status === self::PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now())
            && ($this->expired_at === null || $this->expired_at->gt(now()));
    }

    public function setExpiry(Carbon $expiredAt): void
    {
        $this->forceFill(['expired_at' => $expiredAt])->save();
    }

    public function setPinned(bool $pinned): void
    {
        $this->forceFill(['is_pinned' => $pinned])->save();
    }

        // Arsip: diarsipkan admin, atau tayang tapi masa tayangnya sudah lewat (cermin scopeArchive)
    public function isArchived(): bool
    {
        return $this->status === self::ARCHIVED
            || ($this->status === self::PUBLISHED
                && $this->expired_at !== null
                && $this->expired_at->lte(now()));
    }

    // Diarsipkan admin: pin dilepas supaya slot pin kosong lagi
    public function markArchived(): void
    {
        $this->forceFill(['status' => self::ARCHIVED, 'is_pinned' => false])->save();
    }

    public function displayStatus(): string
    {
        if ($this->status === self::PUBLISHED && $this->expired_at && $this->expired_at->isPast()) {
            return self::ARCHIVED;
        }

        return $this->status;
    }
}