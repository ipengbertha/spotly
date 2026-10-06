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
        return [
            ...parent::share($request),
            'auth' => [
                // Hanya kirim data yang aman dan perlu (jangan seluruh model)
                'user' => $request->user()
                    ? $request->user()->only('id', 'name', 'email', 'role')
                    : null,
            ],
            'categories' => fn () => $request->user()
                ? Category::orderBy('name')->get(['id', 'name'])
                : [],
            // Jumlah notifikasi belum dibaca (untuk badge lonceng)
            'unreadNotifications' => fn () => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }
}