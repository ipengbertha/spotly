<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
        {
            User::firstOrCreate(
        ['email' => 'admin@spotly.test'],
        ['name' => 'Admin Spotly', 'password' => 'password', 'role' => 'admin']
    );

    foreach (['Ivana', 'Budi', 'Citra', 'Dimas', 'Elsa'] as $name) {
        User::firstOrCreate(
            ['email' => strtolower($name) . '@spotly.test'],
            ['name' => $name, 'password' => 'password', 'role' => 'user']
        );
    }
    }
}
