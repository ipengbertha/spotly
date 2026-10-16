<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['post_id', 'user_id', 'parent_id', 'content'];

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
}