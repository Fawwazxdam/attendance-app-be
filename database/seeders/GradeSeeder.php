<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First create a teacher for homeroom
        $teacher = Teacher::first();

        if (!$teacher) {
            // If no teacher exists, create one
            $teacher = Teacher::create([
                'user_id' => 2, // Assuming teacher user exists
                'fullname' => 'Homeroom Teacher',
                'phone_number' => '081234567890',
            ]);
        }

        $grades = [
            [
                'name' => 'Grade 10A',
                'homeroom_teacher_id' => $teacher->id,
            ],
            [
                'name' => 'Grade 10B',
                'homeroom_teacher_id' => $teacher->id,
            ],
            [
                'name' => 'Grade 11A',
                'homeroom_teacher_id' => $teacher->id,
            ],
            [
                'name' => 'Grade 11B',
                'homeroom_teacher_id' => $teacher->id,
            ],
            [
                'name' => 'Grade 12A',
                'homeroom_teacher_id' => $teacher->id,
            ],
        ];

        foreach ($grades as $grade) {
            Grade::firstOrCreate(
                ['name' => $grade['name']],
                $grade
            );
        }
    }
}