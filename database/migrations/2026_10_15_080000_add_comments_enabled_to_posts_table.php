<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Penulis bisa mematikan komentar di postingannya. Postingan lama tetap bisa dikomentari.
            $table->boolean('comments_enabled')->default(true)->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('comments_enabled');
        });
    }
};
