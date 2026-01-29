<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Attendancev5Seeder extends Seeder
{
    private string $tz = 'Asia/Jakarta';

    public function run(): void
    {
        $students = range(1, 10);

        $dates = [
            '2026-01-26',
            '2026-01-27',
            '2026-01-28',
            '2026-01-29',
        ];

        $specialCases = [
            '2026-01-26' => [
                5 => 'late',
                7 => 'late',
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

                    default:
                        $time = $this->randomTime('06:25:00', '06:44:59');
                        $remarks = 'Hadir tepat waktu';
                        $lateReason = null;
                }

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
