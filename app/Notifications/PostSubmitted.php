<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class PostSubmitted extends Notification
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
        $author = $this->post->user?->name ?? 'Seorang siswa';

        return [
            'type'    => 'submitted',
            'post_id' => $this->post->id,
            'title'   => 'Menunggu review',
            'message' => "{$author} mengirim \"{$this->post->title}\" untuk direview.",
            'url'     => '/admin/reviews',
        ];
    }
}