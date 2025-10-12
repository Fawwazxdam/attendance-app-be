<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\User;
use App\Models\Grade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = Grade::all();
        if ($grades->isEmpty()) {
            // If no grades, create one
            $grades = collect([Grade::firstOrCreate(['name' => 'Grade 10A'], ['homeroom_teacher_id' => 1])]);
        }

        $grade = $grades->first();

        $studentsData = [
            [
                'name' => 'Student -25 Points',
                'username' => 'student_m25',
                'email' => 'student_m25@g.c',
                'fullname' => 'Ahmad Rahman',
                'points' => -25,
            ],
            [
                'name' => 'Student -10 Points',
                'username' => 'student_m10',
                'email' => 'student_m10@g.c',
                'fullname' => 'Siti Aminah',
                'points' => -10,
            ],
            [
                'name' => 'Student -5 Points',
                'username' => 'student_m5',
                'email' => 'student_m5@g.c',
                'fullname' => 'Budi Santoso',
                'points' => -5,
            ],
            [
                'name' => 'Student 0 Points',
                'username' => 'student_0',
                'email' => 'student_0@g.c',
                'fullname' => 'Dewi Lestari',
                'points' => 0,
            ],
            [
                'name' => 'Student 5 Points',
                'username' => 'student_5',
                'email' => 'student_5@g.c',
                'fullname' => 'Eko Prasetyo',
                'points' => 5,
            ],
            [
                'name' => 'Student 10 Points',
                'username' => 'student_10',
                'email' => 'student_10@g.c',
                'fullname' => 'Fitri Nurhaliza',
                'points' => 10,
            ],
            [
                'name' => 'Student 15 Points',
                'username' => 'student_15',
                'email' => 'student_15@g.c',
                'fullname' => 'Gilang Ramadhan',
                'points' => 15,
            ],
        ];

        foreach ($studentsData as $studentData) {
            // Create user
            $user = User::firstOrCreate(
                ['username' => $studentData['username']],
                [
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                ]
            );

            // Create student
            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'fullname' => $studentData['fullname'],
                    'grade_id' => $grade->id,
                    'birth_date' => Carbon::now()->subYears(rand(15, 18))->toDateString(),
                    'address' => 'Jl. Contoh No. ' . rand(1, 100),
                    'phone_number' => '081' . rand(10000000, 99999999),
                ]
            );

            // Create student point
            StudentPoint::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'total_points' => $studentData['points'],
                    'last_updated' => now(),
                ]
            );
        }
    }
}