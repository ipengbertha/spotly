<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public const PENDING   = 'pending';
    public const UPHELD    = 'upheld';
    public const DISMISSED = 'dismissed';

    // Pilihan alasan (kunci => teks yang tampil)
    public const REASONS = [
        'inappropriate' => 'Konten tidak pantas',
        'hoax'          => 'Informasi palsu atau hoaks',
        'spam'          => 'Spam atau promosi',
        'privacy'       => 'Melanggar privasi',
        'other'         => 'Lainnya',
    ];

    // status, admin_note, dll. sengaja tidak fillable: hanya admin yang mengubahnya lewat controller
    protected $fillable = ['post_id', 'user_id', 'reason', 'note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function post()     { return $this->belongsTo(Post::class); }
    public function reporter() { return $this->belongsTo(User::class, 'user_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public static function reasonOptions(): array
    {
        return collect(self::REASONS)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }
}
