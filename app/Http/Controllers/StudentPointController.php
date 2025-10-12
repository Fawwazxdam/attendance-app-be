<?php

namespace App\Http\Controllers;

use App\Models\StudentPoint;
use App\Models\RewardPunishmentLog;
use App\Models\RewardPunishmentRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentPointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $studentPoints = StudentPoint::with('student.user')->get();
        return response()->json($studentPoints);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'total_points' => 'required|integer|min:0',
            'last_updated' => 'nullable|date',
        ]);

        // Ensure the student doesn't already have a student point record
        if (StudentPoint::where('student_id', $request->student_id)->exists()) {
            return response()->json(['error' => 'Student already has a point record'], 400);
        }

        $studentPoint = StudentPoint::create($request->all());

        return response()->json($studentPoint->load('student.user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StudentPoint $studentPoint)
    {
        return response()->json($studentPoint->load('student.user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudentPoint $studentPoint)
    {
        $request->validate([
            'total_points' => 'sometimes|required|integer|min:0',
            'last_updated' => 'sometimes|nullable|date',
        ]);

        $studentPoint->update($request->all());

        return response()->json($studentPoint->load('student.user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentPoint $studentPoint)
    {
        $studentPoint->delete();
        return response()->json(['message' => 'Student point deleted successfully']);
    }

    /**
     * Get monthly recap and discipline report for students.
     */
    public function monthlyReport(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'grade_id' => 'nullable|exists:grades,id',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $query = StudentPoint::with(['student:id,fullname,grade_id', 'student.grade:id,name']);

        if ($request->grade_id) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('grade_id', $request->grade_id);
            });
        }

        $studentPoints = $query->get();

        $reports = $studentPoints->map(function ($studentPoint) use ($month, $endOfMonth) {
            // Get logs for the month
            $logs = RewardPunishmentLog::where('student_id', $studentPoint->student_id)
                ->whereBetween('date', [$month->toDateString(), $endOfMonth->toDateString()])
                ->with('rule')
                ->get();

            // Get executed records for the month
            $executedRecords = RewardPunishmentRecord::where('student_id', $studentPoint->student_id)
                ->where('status', 'done')
                ->whereBetween('given_date', [$month->toDateString(), $endOfMonth->toDateString()])
                ->with('rule')
                ->get();

            // Calculate discipline level based on points
            $disciplineLevel = $this->calculateDisciplineLevel($studentPoint->total_points);

            return [
                'student' => $studentPoint->student,
                'total_points' => $studentPoint->total_points,
                'discipline_level' => $disciplineLevel,
                'logs_count' => $logs->count(),
                'executed_punishments' => $executedRecords->where('type', 'punishment')->count(),
                'executed_rewards' => $executedRecords->where('type', 'reward')->count(),
                'logs' => $logs,
                'executed_records' => $executedRecords,
            ];
        });

        return response()->json([
            'month' => $request->month,
            'grade_id' => $request->grade_id,
            'reports' => $reports,
        ]);
    }

    /**
     * Calculate discipline level based on total points.
     */
    private function calculateDisciplineLevel($points)
    {
        if ($points >= 50) {
            return 'Excellent';
        } elseif ($points >= 20) {
            return 'Good';
        } elseif ($points >= 0) {
            return 'Average';
        } elseif ($points >= -20) {
            return 'Needs Improvement';
        } else {
            return 'Poor';
        }
    }
}
