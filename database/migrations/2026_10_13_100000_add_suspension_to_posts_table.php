<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ENUM_NEW = "'draft','submitted','published','rejected','archived','suspended'";
    private const ENUM_OLD = "'draft','submitted','published','rejected','archived'";

    public function up(): void
    {
        // Kolom status bertipe enum, jadi nilai baru "suspended" harus didaftarkan dulu.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE posts MODIFY status ENUM(' . self::ENUM_NEW . ") NOT NULL DEFAULT 'draft'");
        } else {
            // SQLite: enum hanyalah teks dengan batasan; diganti teks biasa
            Schema::table('posts', function (Blueprint $table) {
                $table->string('status')->default('draft')->change();
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            // Alasan penangguhan yang dilihat penulis (diisi admin dari hasil laporan)
            $table->text('suspension_reason')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('suspension_reason');
        });

        // Postingan ditangguhkan dikembalikan sebagai arsip supaya enum lama tetap valid
        DB::table('posts')->where('status', 'suspended')->update(['status' => 'archived', 'is_pinned' => false]);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE posts MODIFY status ENUM(' . self::ENUM_OLD . ") NOT NULL DEFAULT 'draft'");
        }
    }
};
