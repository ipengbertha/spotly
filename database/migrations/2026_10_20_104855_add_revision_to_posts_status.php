<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('status', [
                'draft', 'submitted', 'published', 'rejected', 'archived', 'suspended', 'revision',
            ])->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan postingan "revision" ke draf dulu supaya nilai enum bisa dihapus
        DB::table('posts')->where('status', 'revision')->update(['status' => 'draft']);

        Schema::table('posts', function (Blueprint $table) {
            $table->enum('status', [
                'draft', 'submitted', 'published', 'rejected', 'archived', 'suspended',
            ])->default('draft')->change();
        });
    }
};