<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\User\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Halaman publik
Route::get('/', fn () => Inertia::render('Home'))->name('home');

// Hanya untuk tamu (yang belum login)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // throttle: maksimal 5 percobaan login per menit (anti brute force)
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Area Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
});

// Area User
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [PostController::class, 'index'])->name('dashboard');
    Route::resource('posts', PostController::class)->except(['index', 'show']);
    Route::post('posts/{post}/submit', [PostController::class, 'submit'])->name('posts.submit');
});
