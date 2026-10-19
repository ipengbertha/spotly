<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Untuk admin: ada komentar yang baru dilaporkan (hanya laporan pertama per komentar)
class CommentReported extends Notification
{
    public function __construct(private Comment $comment, private CommentReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $reporter = $this->report->reporter?->name ?? 'Seorang siswa';
        $snippet  = Str::limit(trim(preg_replace('/\s+/', ' ', $this->comment->content)), 60);

        return [
            // Memakai jenis 'report_submitted' supaya tampilannya sama dengan laporan postingan
            'type'       => 'report_submitted',
            'post_id'    => $this->comment->post_id,
            'comment_id' => $this->comment->id,
            'title'      => 'Komentar dilaporkan',
            'message'    => "{$reporter} melaporkan komentar \"{$snippet}\" ({$this->report->reasonLabel()}).",
            'url'        => '/admin/comments?filter=reported',
        ];
    }
}
