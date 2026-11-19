<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // User yang terkena aksi (penulis postingan atau akun yang diubah).
            // Tanpa foreign key dan nama disalin: log harus tetap terbaca kalau user dihapus.
            $table->unsignedBigInteger('target_user_id')->nullable()->after('subject_title');
            $table->string('target_user_name', 100)->nullable()->after('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['target_user_id', 'target_user_name']);
        });
    }
};