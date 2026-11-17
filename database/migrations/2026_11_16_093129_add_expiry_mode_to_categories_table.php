<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // dated | none | school_year
            $table->string('expiry_mode', 20)->default('dated')->after('slug');
        });

        // Prestasi: tayang sampai pergantian tahun ajaran
        DB::table('categories')->where('slug', 'prestasi')->update(['expiry_mode' => 'school_year']);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('expiry_mode');
        });
    }
};