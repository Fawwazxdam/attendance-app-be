<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    private string $tz = 'Asia/Jakarta';

    public function run(): void
    {
        $students = range(1, 10);

        $dates = [
            '2026-01-06',
            '2026-01-07',
            '2026-01-08',
            '2026-01-09',
        ];

        $specialCases = [
            '2026-01-06' => [5 => 'late'],
            '2026-01-07' => [4 => 'excused'],
            '2026-01-08' => [10 => 'late'],
            '2026-01-09' => [
                2 => 'late',
                4 => 'late',
            ],
        ];

        foreach ($dates as $date) {
            foreach ($students as $studentId) {

                $status = $specialCases[$date][$studentId] ?? 'present';

                // Tentukan jam random sesuai status (WIB)
                switch ($status) {
                    case 'excused':
                        $time = $this->randomTime('06:45:00', '07:00:00');
                        $remarks = 'Hadir toleransi';
                        break;

                    case 'late':
                        $time = $this->randomTime('07:01:00', '07:30:00');
                        $remarks = 'Terlambat';
                        break;

                    default:
                        $time = $this->randomTime('06:00:00', '06:44:59');
                        $remarks = 'Hadir tepat waktu';
                }

                // ⏰ Buat datetime dengan timezone Asia/Jakarta
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
                    'status'      => $status,
                    'remarks'     => $remarks,
                    'late_reason' => $status === 'late'
                        ? collect([
                            'Bangun kesiangan',
                            'Macet',
                            'Hujan deras',
                            'Kendaraan bermasalah',
                        ])->random()
                        : null,
                    'created_at'  => $checkInAt,
                    'updated_at'  => $checkInAt,
                ]);
            }
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
