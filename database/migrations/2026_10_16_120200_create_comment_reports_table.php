<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            // Pelapor
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);          // kunci alasan, sama dengan laporan postingan
            $table->text('note')->nullable();      // catatan dari pelapor
            // pending (menunggu admin) | dismissed (tidak ada pelanggaran)
            // Kalau admin menghapus komentarnya, laporannya ikut terhapus (cascade).
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Satu siswa hanya bisa melaporkan satu komentar satu kali
            $table->unique(['comment_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reports');
    }
};
