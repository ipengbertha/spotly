<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

// Untuk pelapor: laporannya sudah ditinjau admin. Detail dibuka di /laporan/{id}
class ReportReviewed extends Notification
{
    public function __construct(private Report $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title     = $this->report->post?->title ?? 'postingan';
        $dismissed = $this->report->status === Report::DISMISSED;

        return [
            'type'    => $dismissed ? 'report_dismissed' : 'report_upheld',
            'post_id' => $this->report->post_id,
            'title'   => 'Laporanmu sudah ditinjau',
            'message' => $dismissed
                ? "Admin sudah meninjau laporanmu tentang \"{$title}\" dan tidak menemukan pelanggaran."
                : "Admin sudah meninjau laporanmu tentang \"{$title}\" dan menindaklanjutinya.",
            'url'     => "/laporan/{$this->report->id}",
        ];
    }
}
