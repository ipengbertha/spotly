<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class PostApproved extends Notification
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
        $until = $this->post->expired_at?->translatedFormat('d M Y');

        return [
            'type'    => 'approved',
            'post_id' => $this->post->id,
            'title'   => 'Postingan disetujui',
            'message' => "\"{$this->post->title}\" sudah tayang di mading" . ($until ? " sampai {$until}." : '.'),
            'url'     => '/user/posts',
        ];
    }
}