<?php

namespace Database\Seeders;

use App\Models\RewardPunishmentLog;
use App\Models\RewardPunishmentRule;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RewardPunishmentLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::with('studentPoint')->get();
        $rules = RewardPunishmentRule::all();
        $teacher = Teacher::first();

        if ($students->isEmpty() || $rules->isEmpty() || !$teacher) {
            return; // Skip if dependencies not met
        }

        foreach ($students as $student) {
            $currentPoints = $student->studentPoint ? $student->studentPoint->total_points : 0;

            // Create logs based on current points
            $this->createLogsForStudent($student, $currentPoints, $rules, $teacher);
        }
    }

    private function createLogsForStudent($student, $targetPoints, $rules, $teacher)
    {
        $logs = [];
        $currentPoints = 0;

        // Define the sequence of rules to reach the target points
        $ruleSequence = $this->getRuleSequenceForPoints($targetPoints);

        foreach ($ruleSequence as $ruleName) {
            $rule = $rules->where('name', $ruleName)->first();
            if ($rule) {
                $currentPoints += $rule->points;
                $logs[] = [
                    'student_id' => $student->id,
                    'rules_id' => $rule->id,
                    'date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                    'given_by' => $teacher->id,
                    'remarks' => "Seeded log for {$rule->name}",
                    'status' => 'DONE',
                ];
            }
        }

        // Create the logs
        foreach ($logs as $log) {
            RewardPunishmentLog::create($log);
        }
    }

    private function getRuleSequenceForPoints($targetPoints)
    {
        $sequences = [
            -25 => ['Serious Violation'],
            -10 => ['Minor Violation'],
            -5 => ['Attendance - Late'],
            0 => [], // No logs
            5 => ['Attendance - Present'],
            10 => ['Good Behavior'],
            15 => ['Excellent Performance'],
        ];

        return $sequences[$targetPoints] ?? [];
    }
}