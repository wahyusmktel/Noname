<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Tentor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Tampilkan Halaman Utama Dashboard Admin Bimbel
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Jika user adalah role tutor / guru, arahkan ke dashboard absensi guru
        if ($user->isTutor()) {
            return redirect()->route('tutor.attendance');
        }

        // Jika user adalah role siswa / orang tua, arahkan ke portal monitoring orang tua
        if ($user->isStudent() || $user->isParent()) {
            return redirect()->route('student.dashboard');
        }

        $tenant = $user->tenant;

        // 1. Metrik Real-Time Database
        $totalStudents = Student::query()->where('status', 'active')->count();
        $totalTutors   = Tentor::query()->count();
        $totalClasses  = StudyGroup::query()->where('is_active', true)->count();
        $totalSessionsToday = AttendanceSession::query()->whereDate('date', Carbon::today())->count();

        // Total sesi presensi bulan ini
        $monthlySessionsCount = AttendanceSession::query()
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->count();

        // Hitung rata-rata tingkat kehadiran siswa bulan ini
        $totalAttendancesMonth = Attendance::query()
            ->whereHas('attendanceSession', function ($q) {
                $q->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
            })
            ->count();

        $presentAttendancesMonth = Attendance::query()
            ->where('status', 'present')
            ->whereHas('attendanceSession', function ($q) {
                $q->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
            })
            ->count();

        $monthlyAttendanceRate = $totalAttendancesMonth > 0
            ? round(($presentAttendancesMonth / $totalAttendancesMonth) * 100, 1)
            : 100.0;

        $stats = [
            'total_students'       => $totalStudents,
            'total_tutors'         => $totalTutors,
            'total_classes'        => $totalClasses,
            'today_sessions'       => $totalSessionsToday,
            'monthly_sessions'     => $monthlySessionsCount,
            'attendance_rate'      => $monthlyAttendanceRate,
            'academic_year_name'   => AcademicYear::where('is_active', true)->value('name') ?? '2025/2026',
        ];

        // 2. Sesi Presensi Belajar Terbaru (Lengkap dengan dokumentasi foto)
        $recentSessions = AttendanceSession::query()
            ->with(['tentor:id,name', 'studyGroup:id,name,education_level'])
            ->withCount([
                'attendances as total_students',
                'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendances as late_count'    => fn ($q) => $q->where('status', 'late'),
            ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(fn ($s) => [
                'id'               => $s->id,
                'date'             => $s->date->format('Y-m-d'),
                'formatted_date'   => $s->date->translatedFormat('d F Y'),
                'tutor_name'       => $s->tentor ? $s->tentor->name : '-',
                'class_name'       => $s->studyGroup ? $s->studyGroup->name : '-',
                'education_level'  => $s->studyGroup ? $s->studyGroup->education_level : '-',
                'subject'          => $s->subject_name,
                'topic'            => $s->topic_description,
                'photo_url'        => $s->photo_url,
                'total_students'   => $s->total_students,
                'present_count'    => $s->present_count,
                'late_count'       => $s->late_count,
                'created_at_human' => $s->created_at->diffForHumans(),
            ]);

        // 3. Log Presensi Siswa Terbaru
        $recentStudentLogs = Attendance::query()
            ->with([
                'student:id,name,username,photo',
                'attendanceSession:id,date,subject_name,study_group_id',
                'attendanceSession.studyGroup:id,name,education_level',
            ])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'student_name' => $a->student ? $a->student->name : '-',
                'nis'          => $a->student ? ($a->student->username ?: '-') : '-',
                'class_name'   => $a->attendanceSession && $a->attendanceSession->studyGroup ? $a->attendanceSession->studyGroup->name : '-',
                'subject_name' => $a->attendanceSession ? $a->attendanceSession->subject_name : '-',
                'date'         => $a->attendanceSession ? $a->attendanceSession->date->translatedFormat('d M Y') : '-',
                'status'       => $a->status,
                'notes'        => $a->notes,
                'time_human'   => $a->created_at->diffForHumans(),
            ]);

        return Inertia::render('Dashboard/Index', [
            'stats'             => $stats,
            'recentSessions'    => $recentSessions,
            'recentStudentLogs' => $recentStudentLogs,
            'tenant'            => $tenant,
        ]);
    }
}
