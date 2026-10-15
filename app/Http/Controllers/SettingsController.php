<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\Setting;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Settings/Index', [
            'isAdmin' => $user->isAdmin(),
            'profile' => [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'bio' => $user->bio,
            'role_label' => $user->isAdmin() ? 'Administrator' : 'Siswa',
            'avatar_url' => $user->avatar ? '/storage/' . $user->avatar : null,
            ],
            'preferences' => collect($this->notificationKeys($user))
                ->mapWithKeys(fn ($key) => [$key => $user->wantsNotification($key)]),
            'mading' => $user->isAdmin() ? Setting::mading() : null,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio'           => ['nullable', 'string', 'max:200'],
            'avatar'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'E-mail ini sudah dipakai akun lain.',
            'bio.max'      => 'Bio maksimal 200 karakter.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'avatar.max'   => 'Ukuran foto maksimal 2 MB.',
        ]);

        // role, username, dan status akun sengaja tidak bisa diubah dari sini
        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
            'bio'   => $data['bio'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
        }

        $user->save();

        return back();
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->numbers()->symbols(), 'regex:/[A-Z]/', 'different:current_password'],
        ], [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.regex'                    => 'Password harus mengandung minimal satu huruf besar.',
            'password.different'                => 'Password baru tidak boleh sama dengan password saat ini.',
            'password.confirmed'                => 'Konfirmasi password tidak cocok.',
        ]);

        // Di-hash otomatis oleh cast 'hashed' di model User
        $request->user()->update(['password' => $request->input('password')]);

        return back();
    }
        
    public function updateNotifications(Request $request)
    {
        $user = $request->user();

        // Hanya kunci milik perannya yang diterima; kunci lain diabaikan
        $rules = collect($this->notificationKeys($user))
            ->mapWithKeys(fn ($key) => [$key => ['required', 'boolean']])
            ->all();
        $data = $request->validate($rules);

        $user->forceFill([
            'notification_preferences' => array_merge($user->notification_preferences ?? [], $data),
        ])->save();

        return back();
    }

    private function notificationKeys($user): array
    {
        return $user->isAdmin()
            ? ['post_submitted']
            : ['post_approved', 'post_rejected', 'comment_received'];
    }

        // Hanya admin
    public function updateMading(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'mading_name'        => ['required', 'string', 'max:30'],
            'mading_description' => ['required', 'string', 'max:80'],
            'posts_per_page'     => ['required', 'integer', Rule::in(Setting::PER_PAGE_OPTIONS)],
        ], [
            'mading_name.required'        => 'Nama mading wajib diisi.',
            'mading_name.max'             => 'Nama mading maksimal 30 karakter.',
            'mading_description.required' => 'Deskripsi wajib diisi.',
            'mading_description.max'      => 'Deskripsi maksimal 80 karakter.',
            'posts_per_page.in'           => 'Pilihan jumlah per halaman tidak valid.',
        ]);

        Setting::put('mading_name', $data['mading_name']);
        Setting::put('mading_description', $data['mading_description']);
        Setting::put('posts_per_page', (string) $data['posts_per_page']);

        return back();
    }
}