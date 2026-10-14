<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

// Untuk penulis: postingannya ditangguhkan admin setelah ada laporan
class PostSuspended extends Notification
{
    public function __construct(private Post $post)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'suspended',
            'post_id' => $this->post->id,
            'title'   => 'Postingan ditangguhkan',
            'message' => "\"{$this->post->title}\" ditangguhkan. Alasan: {$this->post->suspension_reason}",
            'url'     => "/posts/{$this->post->slug}",
        ];
    }
}
