<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Arsipkan postingan yang masa tayangnya habis. Butuh `php artisan schedule:work` (lokal)
// atau cron `* * * * * php artisan schedule:run` (server). Halaman tetap akurat tanpa ini.
Schedule::command('posts:archive-expired')->hourly();
