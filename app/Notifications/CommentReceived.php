<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Untuk penulis postingan: ada komentar baru dari siswa lain
class CommentReceived extends Notification
{
    public function __construct(private Post $post, private Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commenter = $this->comment->user?->name ?? 'Seorang siswa';
        $snippet   = Str::limit(trim(preg_replace('/\s+/', ' ', $this->comment->content)), 80);

        return [
            'type'       => 'comment_received',
            'post_id'    => $this->post->id,
            'comment_id' => $this->comment->id,
            'title'      => 'Komentar baru',
            'message'    => "{$commenter} mengomentari \"{$this->post->title}\": {$snippet}",
            'url'        => "/posts/{$this->post->slug}",
        ];
    }
}
