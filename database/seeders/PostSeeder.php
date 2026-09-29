<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory()->count(3)->create();               // draft
        Post::factory()->count(3)->submitted()->create();  // menunggu review
        Post::factory()->count(2)->rejected()->create();   // ditolak
        Post::factory()->count(8)->published()->create();  // tayang
        Post::factory()->count(2)->expired()->create();    // lewat masa tayang
        Post::factory()->count(3)->archived()->create();   // arsip

        // Sematkan 2 postingan yang sedang tayang
        Post::active()->inRandomOrder()->limit(2)->update(['is_pinned' => true]);

        // Like & komentar dummy pada postingan yang sedang tayang
        $users = User::where('role', 'user')->get();

        Post::active()->get()->each(function ($post) use ($users) {
            foreach ($users->random(rand(1, 4)) as $user) {
                Like::create(['post_id' => $post->id, 'user_id' => $user->id]);
            }
            foreach ($users->random(rand(0, 2)) as $user) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'content' => fake()->sentence(8),
                ]);
            }
        });
    }
}
