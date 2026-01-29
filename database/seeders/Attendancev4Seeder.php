<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Attendancev4Seeder extends Seeder
{
    private string $tz = 'Asia/Jakarta';

    public function run(): void
    {
        $students = range(1, 10);

        $dates = [
            '2026-01-20',
            '2026-01-21',
            '2026-01-22',
            '2026-01-23',
        ];

        $specialCases = [
            '2026-01-20' => [
                2 => 'late',
            ],
            '2026-01-21' => [
                5 => 'late',
                7 => 'absent',
            ],
            '2026-01-22' => [
                5 => 'excused',
                7 => 'late',
            ],
            '2026-01-23' => [
                5 => 'excused',
            ],
        ];

        foreach ($dates as $date) {
            foreach ($students as $studentId) {

                $status = $specialCases[$date][$studentId] ?? 'present';

                switch ($status) {
                    case 'late':
                        $time = $this->randomTime('07:01:00', '07:30:00');
                        $remarks = 'Terlambat';
                        $lateReason = collect([
                            'Bangun kesiangan',
                            'Macet',
                            'Hujan deras',
                            'Kendaraan bermasalah',
                        ])->random();
                        break;

                    case 'excused':
                        $time = null;
                        $remarks = 'Izin Sakit';
                        $lateReason = null;
                        break;

                    case 'absent':
                        $time = null;
                        $remarks = 'Izin Tidak Hadir';
                        $lateReason = null;
                        break;

                    default:
                        $time = $this->randomTime('06:25:00', '06:44:59');
                        $remarks = 'Hadir tepat waktu';
                        $lateReason = null;
                }

                $checkInAt = $time
                    ? Carbon::createFromFormat('Y-m-d H:i:s', "$date $time", $this->tz)
                    : Carbon::createFromFormat('Y-m-d H:i:s', "$date 06:00:00", $this->tz);

                DB::table('attendances')->insert([
                    'uuid'        => Str::uuid(),
                    'student_id'  => $studentId,
                    'user_id'     => null,
                    'date'        => $date,
                    'status'      => $status,
                    'remarks'     => $remarks,
                    'late_reason' => $lateReason,
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
