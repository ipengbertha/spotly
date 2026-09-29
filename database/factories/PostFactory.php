<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'user_id'     => User::where('role', 'user')->inRandomOrder()->value('id'),
            'category_id' => Category::inRandomOrder()->value('id'),
            'title'       => $title,
            'slug'        => Str::slug($title) . '-' . Str::random(5),
            'content'     => fake()->paragraphs(3, true),
            'image'       => null,
            'status'      => Post::DRAFT,
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => Post::SUBMITTED]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => Post::REJECTED,
            'rejection_reason' => 'Gambar kurang jelas, mohon unggah poster dengan resolusi lebih baik.',
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'expired_at'   => now()->addDays(7),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status'       => Post::PUBLISHED,
            'published_at' => now()->subDays(10),
            'expired_at'   => now()->subDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            'status'       => Post::ARCHIVED,
            'published_at' => now()->subDays(20),
            'expired_at'   => now()->subDays(10),
        ]);
    }
}