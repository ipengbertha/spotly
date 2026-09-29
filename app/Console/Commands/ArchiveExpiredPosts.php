<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class ArchiveExpiredPosts extends Command
{
    protected $signature = 'posts:archive-expired';
    protected $description = 'Ubah status postingan published yang masa tayangnya habis menjadi archived';

    public function handle(): int
    {
        $count = Post::where('status', Post::PUBLISHED)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['status' => Post::ARCHIVED]);

        $this->info("{$count} postingan diarsipkan.");

        return self::SUCCESS;
    }
}