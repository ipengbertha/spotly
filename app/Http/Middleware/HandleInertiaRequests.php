<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Category;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                // Hanya kirim data yang aman dan perlu (jangan seluruh model)
                'user' => $user
                    ? [
                        ...$user->only('id', 'name', 'email', 'role'),
                        'avatar_url' => $user->avatar ? '/storage/' . $user->avatar : null,
                    ]
                    : null,
            ],
            'categories' => fn () => $user
                ? Category::orderBy('name')->get(['id', 'name'])
                : [],
            // Jumlah notifikasi belum dibaca (untuk badge lonceng)
            'unreadNotifications' => fn () => $user
                ? $user->unreadNotifications()->count()
                : 0,
        ];
    }
}