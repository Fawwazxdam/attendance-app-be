<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceJournal;
use App\Models\Student;
use App\Models\RewardPunishmentLog;
use App\Models\RewardPunishmentRecord;
use App\Models\RewardPunishmentRule;
use App\Models\StudentPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeveloperController extends Controller
{
    public function updateTimezone()
    {
        DB::table('attendances')->orderBy('id')->chunk(200, function ($records) {
            foreach ($records as $record) {
                DB::table('attendances')
                    ->where('id', $record->id)
                    ->update([
                        'created_at' => Carbon::parse($record->created_at)->addHours(7),
                        'updated_at' => Carbon::parse($record->updated_at)->addHours(7),
                    ]);
            }
        });

        return "Selesai memperbaiki timezone!";
    }

    public function rollbackAttendanceByDate(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = $request->input('date');


        DB::beginTransaction();
        try {
            $attendances = Attendance::whereDate('created_at', $date)->get();


            if ($attendances->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => "No attendances found for {$date}"], 404);
            }


            foreach ($attendances as $attendance) {
                $student = $attendance->student ?? ($attendance->student_id ? Student::find($attendance->student_id) : null);
                if ($student) {
                    $student->load('studentPoint');
                }

                // Adjust student points based on attendance status
                if ($student && $student->studentPoint) {
                    if ($attendance->status == 'present') {
                        $student->studentPoint->total_points -= 10; // Reverse points added for present
                    } elseif ($attendance->status == 'absent') {
                        $student->studentPoint->total_points += 10; // Reverse points subtracted for absent
                    }
                    $student->studentPoint->last_updated = now();
                    $student->studentPoint->save();
                }

                // Delete details then attendance
                $attendance->attendanceJournals()->delete();

                $attendance->delete();
            }


            DB::commit();
            return response()->json(['message' => "Rollback completed for {$date}", 'count' => $attendances->count()]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RollbackAttendance error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
    public function setAttendanceTimeTo0645(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = $request->input('date');

        DB::beginTransaction();
        try {
            // Update time to 06:45
            DB::table('attendances')->whereDate('created_at', $date)->update([
                'created_at' => DB::raw("DATE(updated_at) + INTERVAL 6 HOUR + INTERVAL 45 MINUTE")
            ]);

            // Get attendances and adjust status, points, logs
            $attendances = Attendance::whereDate('created_at', $date)->with('student.studentPoint')->get();

            foreach ($attendances as $attendance) {
                $old_status = $attendance->status;

                // Reverse old points
                $points_change = 0;
                switch ($old_status) {
                    case 'present':
                        $points_change = -5;
                        break;
                    case 'late':
                        $points_change = 5; // -(-5) = +5
                        break;
                    case 'absent':
                        $points_change = 15; // -(-15) = +15
                        break;
                    default:
                        $points_change = 0;
                }

                if ($attendance->student && $attendance->student->studentPoint) {
                    $attendance->student->studentPoint->total_points += $points_change;
                    $attendance->student->studentPoint->last_updated = now();
                    $attendance->student->studentPoint->save();
                }

                // Remove old punishment logs and records if applicable
                if (in_array($old_status, ['late', 'absent'])) {
                    $rule_name = $old_status == 'late' ? 'Terlambat' : 'Tidak Hadir';
                    $rule = RewardPunishmentRule::where('name', $rule_name)->first();
                    if ($rule) {
                        RewardPunishmentLog::where('student_id', $attendance->student_id)
                            ->where('rules_id', $rule->id)
                            ->where('date', $date)
                            ->delete();

                        RewardPunishmentRecord::where('student_id', $attendance->student_id)
                            ->where('rule_id', $rule->id)
                            ->where('given_date', $date)
                            ->where('status', 'pending')
                            ->delete();
                    }
                }

                // Set new status to excused (since 06:45 <= 06:55)
                $attendance->status = 'excused';
                $attendance->save();
            }

            DB::commit();
            return response()->json(['message' => 'Attendance times updated to 06:45, status adjusted to excused, and related data updated for ' . $date]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('setAttendanceTimeTo0645 error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
}
