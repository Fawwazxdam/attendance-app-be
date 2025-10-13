<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceJournal;
use App\Models\StudentPoint;
use App\Models\RewardPunishmentLog;
use App\Models\RewardPunishmentRule;
use App\Models\RewardPunishmentRecord;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date = $request->query('date');
        $status = $request->query('status');
        $gradeId = $request->query('grade_id');

        if (!$date) {
            return response()->json(['message' => 'Date parameter is required'], 400);
        }

        // Validate date format
        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid date format. Use Y-m-d'], 400);
        }

        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            // If not a student (teachers and administrators), return all attendances
            $query = Attendance::where('date', $parsedDate)
                ->with('student:id,fullname,grade_id', 'student.studentPoint', 'student.grade', 'user:id,name,email', 'medias');

            // Apply status filter if provided
            if ($status) {
                $query->where('status', $status);
            }

            // Apply grade filter if provided
            if ($gradeId) {
                $query->whereHas('student', function ($q) use ($gradeId) {
                    $q->where('grade_id', $gradeId);
                });
            }

            $attendances = $query->get();

            // Debug logging
            Log::info('Attendance query result', [
                'date' => $parsedDate,
                'count' => $attendances->count(),
                'attendances' => $attendances->toArray()
            ]);

            // Add points_earned to each attendance
            $attendances->transform(function ($attendance) {
                $attendance->points_earned = $this->calculatePointsEarned($attendance->status);
                return $attendance;
            });

            return response()->json([
                'date' => $parsedDate,
                'attendances' => $attendances,
            ]);
        }

        // For students, return only their own attendance
        $attendance = Attendance::where('student_id', $student->id)
            ->where('date', $parsedDate)
            ->with('student:id,fullname,grade_id', 'student.studentPoint', 'medias')
            ->first();

        if ($attendance) {
            $attendance->points_earned = $this->calculatePointsEarned($attendance->status);
        }

        return response()->json([
            'date' => $parsedDate,
            'attendance' => $attendance,
        ]);
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
        try {
            // Validasi request
            $request->validate([
                'remarks' => 'nullable|string|max:255',
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = $request->user();

            // Check if user is administrator
            if ($user->role === 'administrator') {
                // For administrators, create a special attendance record with excused status
                $today = Carbon::today('Asia/Jakarta')->toDateString();
                $existingAttendance = Attendance::where('student_id', null)
                    ->where('date', $today)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingAttendance) {
                    return response()->json(['message' => 'Attendance already submitted for today'], 409);
                }

                // Create attendance for administrator
                $attendance = DB::transaction(function () use ($request, $user, $today) {
                    $now = Carbon::now('Asia/Jakarta');
                    $currentTime = $now->format('H:i');

                    // Administrators always get excused status
                    $status = 'excused';

                    $attendance = Attendance::create([
                        'student_id' => null, // No student record for admin
                        'user_id' => $user->id, // Store admin user ID
                        'date' => $today,
                        'status' => $status,
                        'remarks' => $request->remarks,
                    ]);

                    // Handle image uploads
                    if ($request->hasFile('images')) {
                        $images = $request->file('images');

                        foreach ($images as $image) {
                            if (!$image->isValid()) {
                                throw new \Exception('Invalid image file');
                            }

                            $path = $image->store('attendance_images', 'public');

                            Media::create([
                                'path' => $path,
                                'filename' => $image->getClientOriginalName(),
                                'mime_type' => $image->getMimeType(),
                                'size' => $image->getSize(),
                                'morphable_type' => Attendance::class,
                                'morphable_id' => $attendance->id,
                            ]);
                        }
                    }

                    // Create attendance journal
                    AttendanceJournal::create([
                        'attendance_id' => $attendance->id,
                        'note' => "Administrator attendance submitted at {$currentTime} with status {$status}",
                    ]);

                    return $attendance;
                });

                // Load relationships for response
                $attendance->load('medias');

                // Add points_earned to response (administrators get 0 points)
                $attendance->points_earned = 0;

                return response()->json([
                    'message' => 'Administrator attendance submitted successfully',
                    'attendance' => $attendance,
                ], 201);
            }

            $student = $user->student()->with('grade.homeroomTeacher')->first();

            if (!$student) {
                return response()->json(['message' => 'Student record not found for this user'], 404);
            }

            // Check if attendance already exists for today
            $today = Carbon::today('Asia/Jakarta')->toDateString();
            $existingAttendance = Attendance::where('student_id', $student->id)
                ->where('date', $today)
                ->first();

            if ($existingAttendance) {
                return response()->json(['message' => 'Attendance already submitted for today'], 409);
            }

            // Gunakan DB transaction
            $attendance = DB::transaction(function () use ($request, $student, $today) {
                // Get current time in Jakarta timezone
                $now = Carbon::now('Asia/Jakarta');
                $currentTime = $now->format('H:i');

                // Determine status
                $status = 'late'; // default
                if ($currentTime < '06:45') {
                    $status = 'present';
                } elseif ($currentTime <= '06:55') {
                    $status = 'excused';
                }

                // Create attendance
                $attendance = Attendance::create([
                    'student_id' => $student->id,
                    'date' => $today,
                    'status' => $status,
                    'remarks' => $request->remarks,
                ]);

                // Handle image uploads
                if ($request->hasFile('images')) {
                    $images = $request->file('images');

                    foreach ($images as $image) {
                        // Validate each image
                        if (!$image->isValid()) {
                            throw new \Exception('Invalid image file');
                        }

                        // Store the image
                        $path = $image->store('attendance_images', 'public');

                        // Create media record
                        Media::create([
                            'path' => $path,
                            'filename' => $image->getClientOriginalName(),
                            'mime_type' => $image->getMimeType(),
                            'size' => $image->getSize(),
                            'morphable_type' => Attendance::class,
                            'morphable_id' => $attendance->id,
                        ]);
                    }
                }

                // Create attendance journal
                AttendanceJournal::create([
                    'attendance_id' => $attendance->id,
                    'note' => "Attendance submitted at {$currentTime} with status {$status}",
                ]);

                // Apply reward/punishment based on attendance status
                $this->applyAttendanceRewardPunishment($student, $status, $today);

                return $attendance;
            });

            // Load relationships untuk response
            $attendance->load('student', 'student.studentPoint', 'medias');

            // Add points_earned to response
            $attendance->points_earned = $this->calculatePointsEarned($attendance->status);

            return response()->json([
                'message' => 'Attendance submitted successfully',
                'attendance' => $attendance,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Attendance submission error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to submit attendance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        //
    }

    /**
     * Calculate points earned based on attendance status.
     */
    private function calculatePointsEarned($status)
    {
        switch ($status) {
            case 'present':
                return 5;
            case 'late':
                return -5;
            case 'excused':
            case 'absent':
            default:
                return 0;
        }
    }

    /**
     * Apply reward or punishment based on attendance status.
     */
    private function applyAttendanceRewardPunishment($student, $status, $date)
    {
        $points = 0;
        $ruleName = '';
        $type = '';
        $logStatus = 'DONE';

        if ($status === 'present') {
            $points = 5;
            $ruleName = 'Attendance - Present';
            $type = 'reward';
        } elseif ($status === 'late') {
            $points = -5;
            $ruleName = 'Attendance - Late';
            $type = 'punishment';
            $logStatus = 'PENDING';
        } elseif ($status === 'excused') {
            // No points change
            return;
        }

        // Get or create student point record
        $studentPoint = StudentPoint::firstOrCreate(
            ['student_id' => $student->id],
            ['total_points' => 0]
        );

        // Update points
        $studentPoint->total_points += $points;
        $studentPoint->last_updated = now();
        $studentPoint->save();

        // Get the rule
        $rule = RewardPunishmentRule::where('name', $ruleName)->first();
        if (!$rule) {
            // If rule not found, skip logging
            return;
        }

        // Get homeroom teacher
        $homeroomTeacher = $student->grade->homeroomTeacher ?? null;
        if (!$homeroomTeacher) {
            // If no homeroom teacher, skip logging
            return;
        }

        // Create log
        $log = RewardPunishmentLog::create([
            'student_id' => $student->id,
            'rules_id' => $rule->id,
            'date' => $date,
            'given_by' => $homeroomTeacher->id,
            'remarks' => "Automatic attendance {$status} reward/punishment",
            'status' => $logStatus,
        ]);

        // For punishments, create a pending record for teacher execution
        if ($type === 'punishment') {
            RewardPunishmentRecord::create([
                'student_id' => $student->id,
                'teacher_id' => $homeroomTeacher->id,
                'rule_id' => $rule->id,
                'type' => $type,
                'description' => "Attendance - Late: Student was late for attendance",
                'status' => 'pending',
                'given_date' => $date,
                'notes' => "Automatically generated from attendance system",
            ]);
        }
    }
}
