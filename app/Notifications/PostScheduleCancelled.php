<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Notifications\Notification;

class PostScheduleCancelled extends Notification
{
    // Tanggal jadwal dikirim terpisah karena bisa berubah saat jadwal dibatalkan
    public function __construct(private Post $post, private ?string $scheduledLabel = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $when = $this->scheduledLabel ? " yang dijadwalkan tayang {$this->scheduledLabel}" : '';

        return [
            'type'    => 'unscheduled',
            'post_id' => $this->post->id,
            'title'   => 'Jadwal tayang dibatalkan',
            'message' => "Jadwal tayang \"{$this->post->title}\"{$when} dibatalkan admin. "
                . 'Postingan kembali ke antrean review dan akan ditinjau lagi.',
            'url'     => '/user/posts',
        ];
    }
}