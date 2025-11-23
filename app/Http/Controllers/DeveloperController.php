<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceJournal;
use App\Models\Student;
use App\Models\RewardPunishmentLog;
use App\Models\RewardPunishmentRecord;
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
}
