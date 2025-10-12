<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@g.c',
            'password' => Hash::make('password'),
            'role' => 'administrator',
        ]);

        User::create([
            'name' => 'Teacher User',
            'username' => 'teacher',
            'email' => 'teacher@g.c',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);

        User::create([
            'name' => 'Student User',
            'username' => 'student',
            'email' => 'student@g.c',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);
    }
}
