<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    public const PENDING   = 'pending';
    public const DISMISSED = 'dismissed';

    // status dan reviewed_* sengaja tidak fillable: hanya admin yang mengubahnya lewat controller
    protected $fillable = ['comment_id', 'user_id', 'reason', 'note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function comment()  { return $this->belongsTo(Comment::class); }
    public function reporter() { return $this->belongsTo(User::class, 'user_id'); }

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    // Alasan memakai daftar yang sama dengan laporan postingan
    public function reasonLabel(): string
    {
        return Report::REASONS[$this->reason] ?? $this->reason;
    }
}
