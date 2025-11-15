<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SchoolSeeder extends Seeder
{
    public function run()
    {
        // --- ADMIN USER ---
        $adminUuid = (string) Str::uuid();

        // DB::table('users')->insert([
        //     'uuid' => $adminUuid,
        //     'name' => 'Administrator',
        //     'username' => 'admin',
        //     'email' => 'admin@g.c',
        //     'email_verified_at' => Carbon::now(),
        //     'password' => Hash::make('password'),
        //     'role' => 'administrator',
        //     'remember_token' => Str::random(10),
        //     'created_at' => Carbon::now(),
        //     'updated_at' => Carbon::now(),
        // ]);

        // --- teachers users data (will be created first) ---
        $teachers = [
            // grade_name => teacher info (will be homeroom)
            'X-3'  => [
                'fullname' => 'Teacher X-3',
                'phone' => '081100000001',
                'subject' => 'Wali Kelas X-3',
                'hire_date' => '2015-08-01'
            ],
            'X-1'  => [
                'fullname' => 'Teacher X-1',
                'phone' => '081100000002',
                'subject' => 'Wali Kelas X-1',
                'hire_date' => '2014-07-10'
            ],
            'XI-1' => [
                'fullname' => 'Teacher XI-1',
                'phone' => '081100000003',
                'subject' => 'Wali Kelas XI-1',
                'hire_date' => '2016-09-15'
            ],
            'XI-2' => [
                'fullname' => 'Teacher XI-2',
                'phone' => '081100000004',
                'subject' => 'Wali Kelas XI-2',
                'hire_date' => '2017-01-20'
            ],
            'XI-4' => [
                'fullname' => 'Teacher XI-4',
                'phone' => '081100000005',
                'subject' => 'Wali Kelas XI-4',
                'hire_date' => '2018-03-05'
            ],
        ];

        $now = Carbon::now();

        // store mapping grade_name => teacher_id
        $teacherIdByGrade = [];

        foreach ($teachers as $gradeName => $t) {
            // create user for teacher
            $username = strtolower(Str::slug($t['fullname'], '_'));
            $email = $username . '@example.com';
            $userUuid = (string) Str::uuid();

            $userId = DB::table('users')->insertGetId([
                'uuid' => $userUuid,
                'name' => $t['fullname'],
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $now,
                'password' => Hash::make('password'), // default password: "password"
                'role' => 'teacher',
                'remember_token' => Str::random(10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // create teacher record
            $teacherUuid = (string) Str::uuid();
            $teacherId = DB::table('teachers')->insertGetId([
                'uuid' => $teacherUuid,
                'user_id' => $userId,
                'fullname' => $t['fullname'],
                'phone_number' => $t['phone'],
                'address' => 'Jl. Contoh No.1',
                'subject' => $t['subject'],
                'hire_date' => $t['hire_date'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $teacherIdByGrade[$gradeName] = $teacherId;
        }

        // --- grades (kelas) ---
        $gradeIds = [];
        foreach ($teacherIdByGrade as $gradeName => $homeroomTeacherId) {
            $gradeUuid = (string) Str::uuid();
            $gradeId = DB::table('grades')->insertGetId([
                'uuid' => $gradeUuid,
                'name' => $gradeName,
                'homeroom_teacher_id' => $homeroomTeacherId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $gradeIds[$gradeName] = $gradeId;
        }

        // --- students data dari input user ---
        $studentsRaw = [
            ['name' => 'Tsabita Sheza',               'grade' => 'X-3'],
            ['name' => 'Riski Firmansyah',            'grade' => 'X-3'],
            ['name' => 'Najwa Dayyana',               'grade' => 'X-1'],
            ['name' => 'Wildan Al Khatami',           'grade' => 'XI-1'],
            ['name' => 'Isyraaq Dyn',                 'grade' => 'XI-2'],
            ['name' => 'Farel Arkan',                 'grade' => 'XI-1'],
            ['name' => 'Kevin Sheva',                 'grade' => 'XI-2'],
            ['name' => 'Diandra Kennard',             'grade' => 'XI-4'],
            ['name' => "Ilma arba'a maulidah",        'grade' => 'X-3'],
            ['name' => 'Faradita zisane',             'grade' => 'X-1'],
        ];

        // Choose sensible birth dates by grade (X -> born ~2008-2009, XI -> ~2007-2008)
        foreach ($studentsRaw as $index => $s) {
            $fullname = $s['name'];
            $gradeName = strtoupper(str_replace(' ', '', $s['grade'])); // normalize (but our keys already match)

            // fallback if grade not found
            $gradeName = $s['grade'];
            $gradeId = $gradeIds[$gradeName] ?? null;
            if (! $gradeId) {
                // skip or throw — better to skip with warning comment in logs
                continue;
            }

            // generate username/email safely (add index to avoid duplicates)
            $baseUsername = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $fullname));
            $username = $baseUsername . '_' . ($index + 1);
            $email = $baseUsername . '@g.c';

            $userUuid = (string) Str::uuid();
            $userId = DB::table('users')->insertGetId([
                'uuid' => $userUuid,
                'name' => $fullname,
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $now,
                'password' => Hash::make('password'), // default password: "password"
                'role' => 'student',
                'remember_token' => Str::random(10),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // choose birth_date by grade (simple heuristic)
            if (str_starts_with($gradeName, 'XI')) {
                $birthDate = '2007-06-01';
            } else { // X
                $birthDate = '2008-06-01';
            }

            $studentUuid = (string) Str::uuid();
            DB::table('students')->insert([
                'uuid' => $studentUuid,
                'user_id' => $userId,
                'fullname' => $fullname,
                'grade_id' => $gradeId,
                'birth_date' => $birthDate,
                'address' => 'Jl. Siswa Contoh No.1',
                'phone_number' => null,
                'image' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Done
    }
}
