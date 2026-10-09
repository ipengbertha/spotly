<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password', 'role', 'username', 'phone', 'bio'];
    protected $attributes = ['is_active' => true];
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function setActive(bool $active): void
    {
        $this->forceFill(['is_active' => $active])->save();
    }

    public function posts()    { return $this->hasMany(Post::class); }
    public function comments() { return $this->hasMany(Comment::class); }
    public function likes()    { return $this->hasMany(Like::class); }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

        // Preferensi notifikasi: kalau belum pernah diatur, semuanya dianggap aktif
    public function wantsNotification(string $key): bool
    {
        return (bool) (($this->notification_preferences ?? [])[$key] ?? true);
    }
}
