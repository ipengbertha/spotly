<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    // Mengisi username untuk akun lama yang masih kosong. Akun yang sudah punya username tidak disentuh.
    public function up(): void
    {
        $taken = array_flip(
            DB::table('users')->whereNotNull('username')->pluck('username')->all()
        );

        $users = DB::table('users')->whereNull('username')->orderBy('id')->get(['id', 'name', 'email']);

        foreach ($users as $u) {
            $base = Str::limit(Str::slug($u->name, '_'), 24, '');
            if ($base === '') {
                $base = Str::limit(preg_replace('/[^a-z0-9_.]/', '', strtolower(Str::before($u->email, '@'))), 24, '');
            }
            if ($base === '') {
                $base = 'user';
            }

            $candidate = $base;
            $i = 1;
            while (isset($taken[$candidate])) {
                $candidate = $base . '_' . $i++;
            }

            $taken[$candidate] = true;
            DB::table('users')->where('id', $u->id)->update(['username' => $candidate]);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: kita tidak tahu username mana yang diisi otomatis dan mana yang diisi pengguna.
    }
};