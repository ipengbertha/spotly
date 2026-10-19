<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    // Komentar hanya bisa diedit penulisnya selama belum lewat batas ini sejak dikirim
    public const EDIT_WINDOW_HOURS = 24;

    protected $fillable = ['post_id', 'user_id', 'parent_id', 'content'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Menghapus komentar ikut menghapus semua balasannya (dan balasan dari balasan).
        // Yang utama adalah cascade di database (lihat migration parent_id); hook ini jaring
        // pengaman kalau foreign key database sedang tidak aktif.
        static::deleting(function (Comment $comment) {
            $comment->replies()->get()->each->delete();
        });
    }

    public function post() { return $this->belongsTo(Post::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies() { return $this->hasMany(Comment::class, 'parent_id'); }
    public function likes() { return $this->hasMany(CommentLike::class); }
    public function reports() { return $this->hasMany(CommentReport::class); }

    // Masih dalam batas waktu edit (24 jam sejak dikirim)?
    public function isEditable(): bool
    {
        return $this->created_at->gt(now()->subHours(self::EDIT_WINDOW_HOURS));
    }
}
