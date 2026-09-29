<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    // Semua aturan: harus pemilik DAN status mengizinkan
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $post->isEditableByOwner();
    }

    public function submit(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $post->isEditableByOwner();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $post->isDeletableByOwner();
    }
}