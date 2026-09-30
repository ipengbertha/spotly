<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        // Validasi di server (tidak hanya mengandalkan validasi frontend)
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/', 'unique:users,phone'],
            'username' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[a-z0-9_.]+$/', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->symbols(), 'regex:/[A-Z]/'],
        ], [
            'email.unique'    => 'E-mail ini sudah terdaftar.',
            'phone.unique'    => 'Nomor telepon ini sudah terdaftar.',
            'phone.regex'     => 'Nomor telepon tidak valid. Contoh: 081234567890.',
            'username.unique' => 'Username ini sudah dipakai, coba yang lain.',
            'username.regex'  => 'Username hanya boleh huruf kecil, angka, titik, dan garis bawah.',
            'username.min'    => 'Username minimal 4 karakter.',
            'password.regex'  => 'Password harus mengandung minimal satu huruf besar.',
        ]);

        // Role SELALU 'user'. Nilai role dari request diabaikan,
        // sehingga tidak ada yang bisa mendaftar sebagai admin lewat form.
        // Password otomatis di-hash oleh cast 'hashed' di model User.
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role'     => 'user',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('user.dashboard');
    }
}