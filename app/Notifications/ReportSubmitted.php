<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Notifications\Notification;

// Untuk admin: ada postingan yang baru dilaporkan (hanya laporan pertama per postingan)
class ReportSubmitted extends Notification
{
    public function __construct(private Post $post, private Report $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $reporter = $this->report->reporter?->name ?? 'Seorang siswa';

        return [
            'type'    => 'report_submitted',
            'post_id' => $this->post->id,
            'title'   => 'Laporan baru',
            'message' => "{$reporter} melaporkan \"{$this->post->title}\" ({$this->report->reasonLabel()}).",
            'url'     => '/admin/reports',
        ];
    }
}
