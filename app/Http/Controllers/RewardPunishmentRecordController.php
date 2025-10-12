<?php

namespace App\Http\Controllers;

use App\Models\RewardPunishmentRecord;
use App\Models\RewardPunishmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RewardPunishmentRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher record not found'], 404);
        }

        $records = RewardPunishmentRecord::where('teacher_id', $teacher->id)
            ->with(['student:id,fullname,grade_id', 'rule', 'student.grade'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($records);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RewardPunishmentRecord $rewardPunishmentRecord)
    {
        return response()->json($rewardPunishmentRecord->load(['student', 'teacher', 'rule']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RewardPunishmentRecord $rewardPunishmentRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * Used by teachers to execute pending records.
     */
    public function update(Request $request, RewardPunishmentRecord $rewardPunishmentRecord)
    {
        $request->validate([
            'status' => 'required|in:done,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher record not found'], 404);
        }

        // Check if the teacher owns this record
        if ($rewardPunishmentRecord->teacher_id !== $teacher->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only allow updating pending records
        if ($rewardPunishmentRecord->status !== 'pending') {
            return response()->json(['message' => 'Only pending records can be updated'], 400);
        }

        DB::transaction(function () use ($request, $rewardPunishmentRecord) {
            $rewardPunishmentRecord->update([
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            // If executed (done), update the corresponding log
            if ($request->status === 'done') {
                RewardPunishmentLog::where('student_id', $rewardPunishmentRecord->student_id)
                    ->where('rules_id', $rewardPunishmentRecord->rule_id)
                    ->where('date', $rewardPunishmentRecord->given_date)
                    ->where('status', 'PENDING')
                    ->update(['status' => 'DONE']);
            }
        });

        return response()->json([
            'message' => 'Record updated successfully',
            'record' => $rewardPunishmentRecord->fresh()->load(['student', 'teacher', 'rule']),
        ]);
    }

    /**
     * Get students who received rewards and punishments from executed records.
     */
    public function studentsWithRecords(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:done,cancelled',
            'type' => 'nullable|in:reward,punishment',
            'month' => 'nullable|date_format:Y-m',
            'grade_id' => 'nullable|exists:grades,id',
        ]);

        $query = RewardPunishmentRecord::with(['student:id,fullname,grade_id', 'student.grade:id,name', 'rule', 'teacher:id,fullname']);

        // Filter by status (default to done if not specified)
        $status = $request->status ?? 'done';
        $query->where('status', $status);

        // Filter by type if specified
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by month if specified
        if ($request->month) {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $query->whereYear('given_date', $month->year)
                  ->whereMonth('given_date', $month->month);
        }

        // Filter by grade if specified
        if ($request->grade_id) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('grade_id', $request->grade_id);
            });
        }

        $records = $query->orderBy('given_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->get();

        // Group by student for better organization
        $studentsData = $records->groupBy('student_id')->map(function ($studentRecords) {
            $student = $studentRecords->first()->student;

            return [
                'student' => $student,
                'total_rewards' => $studentRecords->where('type', 'reward')->count(),
                'total_punishments' => $studentRecords->where('type', 'punishment')->count(),
                'records' => $studentRecords->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'type' => $record->type,
                        'description' => $record->description,
                        'status' => $record->status,
                        'given_date' => $record->given_date,
                        'notes' => $record->notes,
                        'rule' => $record->rule,
                        'teacher' => $record->teacher,
                        'created_at' => $record->created_at,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'filters' => [
                'status' => $status,
                'type' => $request->type,
                'month' => $request->month,
                'grade_id' => $request->grade_id,
            ],
            'total_students' => $studentsData->count(),
            'students' => $studentsData,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewardPunishmentRecord $rewardPunishmentRecord)
    {
        //
    }
}
