<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\StudentPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get general dashboard statistics for all roles
     */
    public function stats()
    {
        try {
            $today = Carbon::today()->toDateString();

            // Basic counts
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalGrades = Grade::count();

            // Today's attendance stats
            $todayAttendance = Attendance::where('date', $today)->get();
            $present = $todayAttendance->where('status', 'present')->count();
            $late = $todayAttendance->where('status', 'late')->count();
            $absent = $totalStudents - ($present + $late);
            $attendanceRate = $totalStudents > 0 ? round((($present + $late) / $totalStudents) * 100, 1) : 0;

            // Weekly trend (last 7 days)
            $weeklyTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->toDateString();
                $dayAttendance = Attendance::where('date', $date)->get();
                $dayPresent = $dayAttendance->where('status', 'present')->count();
                $dayLate = $dayAttendance->where('status', 'late')->count();
                $dayAbsent = $totalStudents - ($dayPresent + $dayLate);

                $weeklyTrend[] = [
                    'date' => $date,
                    'present' => $dayPresent,
                    'late' => $dayLate,
                    'absent' => $dayAbsent,
                    'rate' => $totalStudents > 0 ? round((($dayPresent + $dayLate) / $totalStudents) * 100, 1) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'total_classes' => $totalGrades,
                    'today_attendance' => [
                        'present' => $present,
                        'late' => $late,
                        'absent' => $absent,
                        'rate' => $attendanceRate
                    ],
                    'weekly_trend' => $weeklyTrend
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student-specific dashboard data
     */
    public function studentDashboard(Request $request, $studentId)
    {
        try {
            $student = Student::with(['user', 'grade', 'studentPoint', 'attendances' => function($query) {
                $query->orderBy('date', 'desc')->limit(10);
            }])->findOrFail($studentId);

            // Personal attendance stats for current month
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $monthlyAttendance = Attendance::where('student_id', $studentId)
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->get();

            $monthlyPresent = $monthlyAttendance->where('status', 'present')->count();
            $monthlyLate = $monthlyAttendance->where('status', 'late')->count();
            $totalDays = Carbon::now()->day; // Days so far this month
            $monthlyRate = $totalDays > 0 ? round((($monthlyPresent + $monthlyLate) / $totalDays) * 100, 1) : 0;

            // Current attendance streak
            $currentStreak = $this->calculateAttendanceStreak($studentId);

            // Recent attendance (last 7 days)
            $recentAttendance = Attendance::where('student_id', $studentId)
                ->where('date', '>=', Carbon::today()->subDays(7))
                ->orderBy('date', 'desc')
                ->get()
                ->map(function($attendance) {
                    return [
                        'date' => $attendance->date,
                        'status' => $attendance->status,
                        'time' => $attendance->created_at ? $attendance->created_at->format('H:i') : null,
                        'points' => $this->getPointsForAttendance($attendance->status)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'fullname' => $student->fullname,
                        'grade' => $student->grade ? $student->grade->name : null
                    ],
                    'personal_stats' => [
                        'monthly_attendance_rate' => $monthlyRate,
                        'current_streak' => $currentStreak,
                        'total_points' => $student->studentPoint ? $student->studentPoint->total_points : 0
                    ],
                    'recent_attendance' => $recentAttendance,
                    'today_status' => $this->getTodayAttendanceStatus($studentId)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch student dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teacher-specific dashboard data
     */
    public function teacherDashboard(Request $request, $teacherId)
    {
        try {
            $teacher = Teacher::with('user')->findOrFail($teacherId);

            // Get classes taught by this teacher (homeroom classes)
            $classes = Grade::where('homeroom_teacher_id', $teacherId)->with('students')->get();

            $classesToday = [];
            $absentStudents = [];

            foreach ($classes as $grade) {
                $today = Carbon::today()->toDateString();

                // Today's attendance for this class
                $classAttendance = Attendance::whereHas('student', function($query) use ($grade) {
                    $query->where('grade_id', $grade->id);
                })->where('date', $today)->get();

                $totalStudents = $grade->students->count();
                $present = $classAttendance->where('status', 'present')->count();
                $late = $classAttendance->where('status', 'late')->count();
                $absent = $totalStudents - ($present + $late);

                $classesToday[] = [
                    'class_name' => $grade->name,
                    'total_students' => $totalStudents,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'rate' => $totalStudents > 0 ? round((($present + $late) / $totalStudents) * 100, 1) : 0
                ];

                // Find absent students
                $presentStudentIds = $classAttendance->pluck('student_id')->toArray();
                $absentStudentsInClass = $grade->students->filter(function($student) use ($presentStudentIds) {
                    return !in_array($student->id, $presentStudentIds);
                });

                foreach ($absentStudentsInClass as $absentStudent) {
                    $absentStudents[] = [
                        'name' => $absentStudent->fullname,
                        'class' => $grade->name,
                        'last_attendance' => $this->getLastAttendanceDate($absentStudent->id)
                    ];
                }
            }

            // Monthly trends for last 6 months
            $monthlyTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $month = $date->month;
                $year = $date->year;

                $monthlyAttendance = Attendance::whereHas('student', function($query) use ($teacherId) {
                    $query->whereHas('grade', function($q) use ($teacherId) {
                        $q->where('homeroom_teacher_id', $teacherId);
                    });
                })->whereMonth('date', $month)->whereYear('date', $year)->get();

                $totalPresent = $monthlyAttendance->where('status', 'present')->count();
                $totalLate = $monthlyAttendance->where('status', 'late')->count();
                $totalRecords = $monthlyAttendance->count();

                $monthlyTrends[] = [
                    'month' => $date->format('M Y'),
                    'attendance_rate' => $totalRecords > 0 ? round((($totalPresent + $totalLate) / $totalRecords) * 100, 1) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'fullname' => $teacher->fullname
                    ],
                    'classes_today' => $classesToday,
                    'absent_students' => array_slice($absentStudents, 0, 10), // Limit to 10
                    'monthly_trends' => $monthlyTrends
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admin dashboard data
     */
    public function adminDashboard()
    {
        try {
            // System overview
            $totalUsers = User::count();
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalGrades = Grade::count();

            // Today's system-wide attendance
            $today = Carbon::today()->toDateString();
            $todayAttendance = Attendance::where('date', $today)->get();

            $present = $todayAttendance->where('status', 'present')->count();
            $late = $todayAttendance->where('status', 'late')->count();
            $absent = $totalStudents - ($present + $late);

            // Recent system activity (last 10 attendances)
            $recentActivity = Attendance::with(['student.user', 'student.grade'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($attendance) {
                    return [
                        'student_name' => $attendance->student->fullname,
                        'grade' => $attendance->student->grade ? $attendance->student->grade->name : 'N/A',
                        'status' => $attendance->status,
                        'time' => $attendance->created_at->format('H:i'),
                        'date' => $attendance->date
                    ];
                });

            // System health (users registered this month)
            $thisMonthUsers = User::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'system_overview' => [
                        'total_users' => $totalUsers,
                        'total_students' => $totalStudents,
                        'total_teachers' => $totalTeachers,
                        'total_classes' => $totalGrades,
                        'new_users_this_month' => $thisMonthUsers
                    ],
                    'today_stats' => [
                        'present' => $present,
                        'late' => $late,
                        'absent' => $absent,
                        'attendance_rate' => $totalStudents > 0 ? round((($present + $late) / $totalStudents) * 100, 1) : 0
                    ],
                    'recent_activity' => $recentActivity
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chart data for attendance trends
     */
    public function attendanceTrend(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // month, week, year
            $limit = $request->get('limit', 12);

            $data = [];
            $labels = [];

            if ($period === 'day') {
                // Last 7 days attendance for this student
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->toDateString();

                    $dailyAttendance = Attendance::where('student_id', $studentId)
                        ->where('date', $date)
                        ->get();

                    $presentRecords = $dailyAttendance->where('status', 'present');
                    $lateRecords = $dailyAttendance->where('status', 'late');

                    $labels[] = Carbon::parse($date)->format('M d');
                    $data[] = [
                        'present' => $presentRecords->count(),
                        'late' => $lateRecords->count(),
                        'absent' => $dailyAttendance->where('status', 'absent')->count(),
                        'present_times' => $presentRecords->pluck('created_at')->map(fn($time) => $time ? $time->format('H:i') : null)->toArray(),
                        'late_times' => $lateRecords->pluck('created_at')->map(fn($time) => $time ? $time->format('H:i') : null)->toArray()
                    ];
                }
            } elseif ($period === 'month') {
                // Last 12 months
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subMonths($i);
                    $month = $date->month;
                    $year = $date->year;

                    $monthlyData = Attendance::whereMonth('date', $month)
                        ->whereYear('date', $year)
                        ->select('status', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

                    $labels[] = $date->format('M Y');
                    $data[] = [
                        'present' => $monthlyData['present'] ?? 0,
                        'late' => $monthlyData['late'] ?? 0,
                        'absent' => $monthlyData['absent'] ?? 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Hadir',
                            'data' => array_column($data, 'present'),
                            'borderColor' => '#10B981',
                            'backgroundColor' => '#10B981'
                        ],
                        [
                            'label' => 'Terlambat',
                            'data' => array_column($data, 'late'),
                            'borderColor' => '#F59E0B',
                            'backgroundColor' => '#F59E0B'
                        ],
                        [
                            'label' => 'Tidak Hadir',
                            'data' => array_column($data, 'absent'),
                            'borderColor' => '#EF4444',
                            'backgroundColor' => '#EF4444'
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance trend data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chart data for class performance
     */
    public function classPerformance(Request $request)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            $grades = Grade::with('students')->get();

            $labels = [];
            $data = [];

            foreach ($grades as $grade) {
                $labels[] = $grade->name;

                $gradeAttendance = Attendance::whereHas('student', function($query) use ($grade) {
                    $query->where('grade_id', $grade->id);
                })->whereMonth('date', $month)->whereYear('date', $year)->get();

                $totalStudents = $grade->students->count();
                $present = $gradeAttendance->where('status', 'present')->count();
                $late = $gradeAttendance->where('status', 'late')->count();

                $attendanceRate = $totalStudents > 0 ? round((($present + $late) / $totalStudents) * 100, 1) : 0;
                $data[] = $attendanceRate;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Tingkat Kehadiran (%)',
                            'data' => $data,
                            'backgroundColor' => [
                                '#3B82F6', '#10B981', '#F59E0B',
                                '#EF4444', '#8B5CF6', '#EC4899',
                                '#06B6D4', '#84CC16', '#F97316'
                            ]
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance chart data for a specific student
     */
    public function studentAttendanceChart(Request $request, $user_id)
    {
        try {
            // $studentId = Student::where('user_id', $user_id)->value('id');
            $studentId = $user_id;
            $period = $request->get('period', 'week'); // day, month, week, year
            $limit = $request->get('limit', $period === 'week' ? 1 : ($period === 'day' ? 7 : 12));

            $data = [];
            $labels = [];

            if ($period === 'day') {
                // Last 7 days attendance for this student
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->toDateString();

                    $dailyAttendance = Attendance::where('student_id', $studentId)
                        ->where('date', $date)
                        ->get();

                    $presentRecords = $dailyAttendance->where('status', 'present');
                    $lateRecords = $dailyAttendance->where('status', 'late');

                    $labels[] = Carbon::parse($date)->format('M d');
                    $data[] = [
                        'present' => $presentRecords->count(),
                        'late' => $lateRecords->count(),
                        'absent' => $dailyAttendance->where('status', 'absent')->count(),
                        'present_times' => $presentRecords->pluck('created_at')->map(fn($time) => $time ? $time->format('H:i') : null)->toArray(),
                        'late_times' => $lateRecords->pluck('created_at')->map(fn($time) => $time ? $time->format('H:i') : null)->toArray()
                    ];
                }
            } elseif ($period === 'month') {
                // Last 12 weeks
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
                    $endOfWeek = $startOfWeek->copy()->endOfWeek();

                    $weeklyAttendance = Attendance::where('student_id', $studentId)
                        ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                        ->select('status', 'created_at', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

                    $labels[] = $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d');
                    $data[] = [
                        'present' => $weeklyAttendance['present'] ?? 0,
                        'late' => $weeklyAttendance['late'] ?? 0,
                        'absent' => $weeklyAttendance['absent'] ?? 0
                    ];
                }
            } elseif ($period === 'year') {
                // Last 5 years
                for ($i = $limit - 1; $i >= 0; $i--) {
                    $year = Carbon::now()->subYears($i)->year;

                    $yearlyAttendance = Attendance::where('student_id', $studentId)
                        ->whereYear('date', $year)
                        ->select('status', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

                    $labels[] = $year;
                    $data[] = [
                        'present' => $yearlyAttendance['present'] ?? 0,
                        'late' => $yearlyAttendance['late'] ?? 0,
                        'absent' => $yearlyAttendance['absent'] ?? 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Hadir',
                            'data' => array_column($data, 'present'),
                            'borderColor' => '#10B981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                            'fill' => true
                        ],
                        [
                            'label' => 'Terlambat',
                            'data' => array_column($data, 'late'),
                            'borderColor' => '#F59E0B',
                            'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                            'fill' => true
                        ],
                        [
                            'label' => 'Tidak Hadir',
                            'data' => array_column($data, 'absent'),
                            'borderColor' => '#EF4444',
                            'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                            'fill' => true
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch student attendance chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper methods

    private function calculateAttendanceStreak($studentId)
    {
        $streak = 0;
        $date = Carbon::today();

        while (true) {
            $attendance = Attendance::where('student_id', $studentId)
                ->where('date', $date->toDateString())
                ->whereIn('status', ['present', 'late'])
                ->first();

            if ($attendance) {
                $streak++;
                $date = $date->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    private function getPointsForAttendance($status)
    {
        switch ($status) {
            case 'present': return 10;
            case 'late': return 5;
            default: return 0;
        }
    }

    private function getTodayAttendanceStatus($studentId)
    {
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('student_id', $studentId)
            ->where('date', $today)
            ->first();

        return $attendance ? [
            'status' => $attendance->status,
            'time' => $attendance->created_at ? $attendance->created_at->format('H:i') : null
        ] : null;
    }

    private function getLastAttendanceDate($studentId)
    {
        $lastAttendance = Attendance::where('student_id', $studentId)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('date', 'desc')
            ->first();

        return $lastAttendance ? $lastAttendance->date : null;
    }
}
