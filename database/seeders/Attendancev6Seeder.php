<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Attendancev6Seeder extends Seeder
{
    private string $tz = 'Asia/Jakarta';

    public function run(): void
    {
        $students = range(1, 10);
        $date = '2026-01-30';

        foreach ($students as $studentId) {

            $time = $this->randomTime('06:26:00', '06:44:59');
            $remarks = 'Hadir tepat waktu';

            $checkInAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                "$date $time",
                $this->tz
            );

            DB::table('attendances')->insert([
                'uuid'        => Str::uuid(),
                'student_id'  => $studentId,
                'user_id'     => null,
                'date'        => $date,
                'status'      => 'present',
                'remarks'     => $remarks,
                'late_reason' => null,
                'created_at'  => $checkInAt,
                'updated_at'  => $checkInAt,
            ]);
        }
    }

    /**
     * Generate random time (H:i:s) dalam rentang WIB
     */
    private function randomTime(string $start, string $end): string
    {
        return Carbon::createFromTimestamp(
            rand(
                Carbon::parse($start, $this->tz)->timestamp,
                Carbon::parse($end, $this->tz)->timestamp
            ),
            $this->tz
        )->format('H:i:s');
    }
}
