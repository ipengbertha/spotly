<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class ArchiveExpiredPosts extends Command
{
    protected $signature = 'posts:archive-expired';
    protected $description = 'Arsipkan postingan published yang masa tayangnya sudah habis (pin ikut dilepas)';

    public function handle(): int
    {
        $count = 0;

        // Satu per satu lewat markArchived() supaya aturannya sama dengan tombol Arsipkan admin:
        // status jadi archived DAN pin dilepas (slot pin kosong, tidak muncul lagi saat ditayangkan ulang).
        Post::where('status', Post::PUBLISHED)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->chunkById(100, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $post->markArchived();
                    $count++;
                }
            });

        $this->info("{$count} postingan diarsipkan.");

        return self::SUCCESS;
    }
}
