<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // Pelapor
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);          // kunci alasan, mis. "hoax"
            $table->text('note')->nullable();      // catatan dari pelapor
            // pending (menunggu admin) | upheld (ditindaklanjuti) | dismissed (tidak ada pelanggaran)
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Satu siswa hanya bisa melaporkan satu postingan satu kali
            $table->unique(['post_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
