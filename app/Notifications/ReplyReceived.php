<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Untuk penulis komentar: ada siswa lain yang membalas komentarnya
class ReplyReceived extends Notification
{
    public function __construct(private Post $post, private Comment $reply)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $replier = $this->reply->user?->name ?? 'Seorang siswa';
        $snippet = Str::limit(trim(preg_replace('/\s+/', ' ', $this->reply->content)), 80);

        return [
            // Memakai jenis 'comment_received' supaya tetap ikut saklar notifikasi komentar di Pengaturan
            'type'       => 'comment_received',
            'post_id'    => $this->post->id,
            'comment_id' => $this->reply->id,
            'title'      => 'Balasan baru',
            'message'    => "{$replier} membalas komentarmu di \"{$this->post->title}\": {$snippet}",
            'url'        => "/posts/{$this->post->slug}",
        ];
    }
}