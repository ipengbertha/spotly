<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_id', 'user_id']);
            $table->index('created_at');
        });

        // Isi dengan pembaca yang sudah tercatat sebelumnya
        DB::table('post_views')->insertOrIgnoreUsing(
            ['post_id', 'user_id', 'created_at'],
            DB::table('reading_histories')->select('post_id', 'user_id', 'created_at'),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};