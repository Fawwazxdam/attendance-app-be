<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\StudentPointController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RewardPunishmentRuleController;
use App\Http\Controllers\RewardPunishmentLogController;
use App\Http\Controllers\RewardPunishmentRecordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    $user = $request->user();
    if ($user->role === 'student') {
        $user->load('student');
    } elseif ($user->role === 'teacher') {
        $user->load('teacher');
    }
    return $user;
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/student/{id}', [DashboardController::class, 'studentDashboard']);
    Route::get('/dashboard/teacher/{id}', [DashboardController::class, 'teacherDashboard']);
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);

    // Chart data routes
    Route::get('/charts/attendance-trend', [DashboardController::class, 'attendanceTrend']);
    Route::get('/charts/class-performance', [DashboardController::class, 'classPerformance']);
    Route::get('/charts/student-attendance/{studentId}', [DashboardController::class, 'studentAttendanceChart']);

    // Existing routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('teachers', TeacherController::class);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('grades', GradeController::class);
    Route::apiResource('targets', TargetController::class);
    Route::apiResource('student-points', StudentPointController::class);
    Route::apiResource('faqs', FaqController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::get('attendances/export', [AttendanceController::class, 'export']);
    Route::apiResource('attendances', AttendanceController::class);
    Route::apiResource('reward-punishment-rules', RewardPunishmentRuleController::class);
    Route::apiResource('reward-punishment-logs', RewardPunishmentLogController::class);
    Route::apiResource('reward-punishment-records', RewardPunishmentRecordController::class);
    Route::get('reward-punishment-records/students/list', [RewardPunishmentRecordController::class, 'studentsWithRecords']);
    Route::post('reward-punishment-records/bulk-update-done', [RewardPunishmentRecordController::class, 'bulkUpdateDone']);
    Route::get('student-points/monthly-report', [StudentPointController::class, 'monthlyReport']);

    // Media routes for serving images (no auth required for images)
    Route::get('/storage/{path}', function ($path) {
        // Handle URL encoding issues
        $decodedPath = urldecode($path);
        $fullPath = storage_path('app/public/' . $decodedPath);

        Log::info('Storage request', [
            'original_path' => $path,
            'decoded_path' => $decodedPath,
            'full_path' => $fullPath,
            'exists' => file_exists($fullPath)
        ]);

        if (!file_exists($fullPath)) {
            Log::error('File not found: ' . $fullPath);
            return response()->json(['message' => 'File not found'], 404);
        }

        $mimeType = mime_content_type($fullPath);
        Log::info('Serving file', ['mime_type' => $mimeType]);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600'
        ]);
    })->where('path', '.*')->withoutMiddleware(['auth:sanctum']);
});
