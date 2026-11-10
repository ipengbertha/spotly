<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributor_awards', function (Blueprint $table) {
            $table->id();
            $table->char('period', 7);                       // 'YYYY-MM', contoh '2026-10'
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rank');             // peringkat saat diumumkan
            // Salinan angka saat diumumkan, supaya pengumuman lama tidak berubah
            $table->unsignedInteger('posts_count');
            $table->unsignedInteger('likes_count');
            $table->unsignedInteger('comments_count');
            $table->foreignId('announced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period', 'user_id']);
            $table->unique(['period', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributor_awards');
    }
};
