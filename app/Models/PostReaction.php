<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    // kata kunci => emoji. Urutan di sini = urutan tampil di halaman baca.
    public const OPTIONS = [
        'thumbs' => '👍',
        'clap'   => '👏',
        'love'   => '😍',
        'wow'    => '😮',
        'fire'   => '🔥',
    ];

    protected $fillable = ['post_id', 'user_id', 'type'];

    public function post() { return $this->belongsTo(Post::class); }
    public function user() { return $this->belongsTo(User::class); }
}