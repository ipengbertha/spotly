<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\FeedController;
use App\Http\Controllers\User\PostController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Halaman publik
Route::get('/', [PublicController::class, 'home'])->name('home');

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

// Mading: bisa dibuka user dan admin (sengaja di luar grup admin/user)
Route::middleware(['auth', EnsureUserIsActive::class])
    ->get('/user/beranda', [FeedController::class, 'index'])->name('user.beranda');

// Area Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{post}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{post}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::patch('/posts/{post}/expiry', [AdminPostController::class, 'updateExpiry'])->name('posts.expiry');
    Route::post('/posts/{post}/pin', [AdminPostController::class, 'pin'])->name('posts.pin');
    Route::delete('/posts/{post}/pin', [AdminPostController::class, 'unpin'])->name('posts.unpin');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
});

// Area User
Route::middleware(['auth', EnsureUserIsActive::class, 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

    Route::resource('posts', PostController::class)->except(['show']);
    Route::post('posts/{post}/submit', [PostController::class, 'submit'])->name('posts.submit');
});