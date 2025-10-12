<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create teacher user
        $teacherUser = User::where('role', 'teacher')->first();

        if (!$teacherUser) {
            $teacherUser = User::create([
                'name' => 'Teacher User',
                'username' => 'teacher',
                'email' => 'teacher@g.c',
                'password' => Hash::make('password'),
                'role' => 'teacher',
            ]);
        }

        // Create teacher record
        Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'fullname' => 'Mr. Ahmad Susanto',
                'phone_number' => '081234567890',
                'address' => 'Jl. Guru No. 1',
                'subject' => 'Mathematics',
                'hire_date' => now()->subYears(5)->toDateString(),
            ]
        );

        // Create additional teachers
        $additionalTeachers = [
            [
                'name' => 'Mrs. Siti Nurhaliza',
                'username' => 'teacher2',
                'email' => 'teacher2@g.c',
                'fullname' => 'Mrs. Siti Nurhaliza',
                'phone' => '081234567891',
                'subject' => 'English',
            ],
            [
                'name' => 'Mr. Budi Santoso',
                'username' => 'teacher3',
                'email' => 'teacher3@g.c',
                'fullname' => 'Mr. Budi Santoso',
                'phone' => '081234567892',
                'subject' => 'Physics',
            ],
        ];

        foreach ($additionalTeachers as $teacherData) {
            $user = User::firstOrCreate(
                ['username' => $teacherData['username']],
                [
                    'name' => $teacherData['name'],
                    'email' => $teacherData['email'],
                    'password' => Hash::make('password'),
                    'role' => 'teacher',
                ]
            );

            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'fullname' => $teacherData['fullname'],
                    'phone_number' => $teacherData['phone'],
                    'address' => 'Jl. Guru No. ' . rand(2, 10),
                    'subject' => $teacherData['subject'],
                    'hire_date' => now()->subYears(rand(1, 10))->toDateString(),
                ]
            );
        }
    }
}