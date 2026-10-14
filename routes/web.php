<?php

use App\Http\Controllers\Admin\ArchiveController as AdminArchiveController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PostShowController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\FeedController;
use App\Http\Controllers\User\LikedController;
use App\Http\Controllers\User\PostController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Halaman publik
Route::get('/', [PublicController::class, 'home'])->name('home');

//Detail admin
Route::get('/posts/{post:slug}', PostShowController::class)->name('posts.show');

// Profil publik penulis (siswa): bisa dibuka tamu
Route::get('/profil/{user}', [ProfileController::class, 'show'])->name('profiles.show');

// Like: hanya siswa. Tamu diarahkan ke login, admin mendapat 403.
Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])
    ->middleware(['auth', EnsureUserIsActive::class, 'role:user'])
    ->name('posts.like');

// Laporan: siswa melaporkan postingan tayang, lalu melihat hasilnya. Admin dan tamu ditolak.
Route::middleware(['auth', EnsureUserIsActive::class, 'role:user'])->group(function () {
    Route::post('/posts/{post}/report', [ReportController::class, 'store'])->name('posts.report');
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->name('reports.show');
});

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

// Lupa password: baru halaman (belum mengirim e-mail). Terbuka untuk tamu dan yang sudah login
Route::inertia('/forgot-password', 'Auth/ForgotPassword')->name('password.request');

// Mading: bisa dibuka user dan admin (sengaja di luar grup admin/user)
Route::middleware(['auth', EnsureUserIsActive::class])
    ->get('/user/beranda', [FeedController::class, 'index'])->name('user.beranda');

// Notifikasi: bisa dibuka user dan admin
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

// Pengaturan: bisa dibuka user dan admin
// Pengaturan: bisa dibuka user dan admin
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    // POST (bukan PATCH) karena form membawa file foto
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    // Hanya admin (dicek juga di controller dengan abort_unless)
    Route::put('/settings/mading', [SettingsController::class, 'updateMading'])->name('settings.mading');
});

// Area Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{post}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{post}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/posts/{post}/suspend', [AdminReportController::class, 'suspend'])->name('reports.suspend');
    Route::post('/reports/posts/{post}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');

    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::patch('/posts/{post}/expiry', [AdminPostController::class, 'updateExpiry'])->name('posts.expiry');
    Route::post('/posts/{post}/pin', [AdminPostController::class, 'pin'])->name('posts.pin');
    Route::delete('/posts/{post}/pin', [AdminPostController::class, 'unpin'])->name('posts.unpin');
    Route::post('/posts/{post}/archive', [AdminPostController::class, 'archive'])->name('posts.archive');
    Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/archive', [AdminArchiveController::class, 'index'])->name('archive.index');
    Route::post('/archive/{post}/republish', [AdminArchiveController::class, 'republish'])->name('archive.republish');

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

    Route::get('/disukai', [LikedController::class, 'index'])->name('liked');
});