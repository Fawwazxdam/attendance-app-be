<?php

namespace App\Http\Controllers;

use App\Models\RewardPunishmentLog;
use App\Models\StudentPoint;
use App\Models\RewardPunishmentRule;
use Illuminate\Http\Request;

class RewardPunishmentLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logs = RewardPunishmentLog::with(['student', 'rule', 'teacher'])->get();
        return response()->json($logs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'rules_id' => 'required|exists:reward_punishment_rules,id',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $user = $request->user();
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json(['message' => 'Teacher record not found for this user'], 404);
        }

        // Get the rule to apply points
        $rule = RewardPunishmentRule::find($request->rules_id);

        // Get or create student point record
        $studentPoint = StudentPoint::firstOrCreate(
            ['student_id' => $request->student_id],
            ['total_points' => 0]
        );

        // Apply points
        $studentPoint->total_points += $rule->points;
        $studentPoint->last_updated = now();
        $studentPoint->save();

        // Create log
        $log = RewardPunishmentLog::create([
            'student_id' => $request->student_id,
            'rules_id' => $request->rules_id,
            'date' => $request->date,
            'given_by' => $teacher->id,
            'remarks' => $request->remarks,
            'status' => 'DONE',
        ]);

        return response()->json($log->load(['student', 'rule', 'teacher']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RewardPunishmentLog $rewardPunishmentLog)
    {
        return response()->json($rewardPunishmentLog->load(['student', 'rule', 'teacher']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RewardPunishmentLog $rewardPunishmentLog)
    {
        $request->validate([
            'remarks' => 'nullable|string',
        ]);

        $rewardPunishmentLog->update($request->only(['remarks']));

        return response()->json($rewardPunishmentLog->load(['student', 'rule', 'teacher']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RewardPunishmentLog $rewardPunishmentLog)
    {
        // Reverse the points
        $studentPoint = StudentPoint::where('student_id', $rewardPunishmentLog->student_id)->first();
        if ($studentPoint) {
            $studentPoint->total_points -= $rewardPunishmentLog->rule->points;
            $studentPoint->last_updated = now();
            $studentPoint->save();
        }

        $rewardPunishmentLog->delete();
        return response()->json(['message' => 'Reward punishment log deleted successfully']);
    }
}
