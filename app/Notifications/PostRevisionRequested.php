<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class PostRevisionRequested extends Notification
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
            'type'    => 'revision',
            'post_id' => $this->post->id,
            'title'   => 'Postingan perlu revisi',
            'message' => "\"{$this->post->title}\" perlu diperbaiki. Catatan: {$this->post->rejection_reason}",
            'url'     => '/user/posts',
        ];
    }
}