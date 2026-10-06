<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class PostRejected extends Notification
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
            'type'    => 'rejected',
            'post_id' => $this->post->id,
            'title'   => 'Postingan ditolak',
            'message' => "\"{$this->post->title}\" ditolak. Alasan: {$this->post->rejection_reason}",
            'url'     => '/user/posts',
        ];
    }
}