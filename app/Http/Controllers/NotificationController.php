<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Notifications/Index', [
            'notifications' => $user->notifications()
                ->paginate(10)
                ->withQueryString()
                ->through(fn ($n) => [
                    'id'      => $n->id,
                    'type'    => $n->data['type'] ?? null,
                    'title'   => $n->data['title'] ?? '',
                    'message' => $n->data['message'] ?? '',
                    'is_read' => $n->read_at !== null,
                    'time'    => $n->created_at->locale('id')->diffForHumans(),
                ]),
            'isAdmin' => $user->role === 'admin',
        ]);
    }

    // Tandai satu notifikasi dibaca, lalu buka halaman terkait
    public function read(Request $request, string $id)
    {
        // Hanya bisa membuka notifikasi milik sendiri (milik orang lain = 404)
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? '/notifications');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}