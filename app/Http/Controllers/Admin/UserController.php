<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        return Inertia::render('Admin/Users/Index', [
            'users' => User::where('role', 'user')
                ->withCount('posts')
                ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                }))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $u) => [
                    'id'          => $u->id,
                    'name'        => $u->name,
                    'email'       => $u->email,
                    'posts_count' => $u->posts_count,
                    'is_active'   => $u->is_active,
                    'joined'      => $u->created_at->translatedFormat('d M Y'),
                ]),
            'filters' => ['q' => $q],
        ]);
    }

    public function deactivate(User $user)
    {
        return $this->setActive($user, false);
    }

    public function activate(User $user)
    {
        return $this->setActive($user, true);
    }

    private function setActive(User $user, bool $active)
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Akun admin tidak bisa diubah dari halaman ini.']);
        }

        $user->setActive($active);

        return back();
    }
}