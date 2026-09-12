<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentPortalController extends Controller
{
    /**
     * Tampilkan Portal Monitoring Kehadiran Anak untuk Orang Tua / Siswa
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Cari data peserta didik berdasarkan user_id atau username
        $student = Student::query()
            ->where('user_id', $user->id)
            ->orWhere('username', $user->username)
            ->with(['studyGroup', 'academicYear', 'tenant'])
            ->first();

        // Jika data siswa tidak ditemukan (misal akun belum dipetakan), tampilkan halaman penanganan ramah
        if (!$student) {
            return Inertia::render('Student/Dashboard', [
                'student'            => null,
                'kpi'                => null,
                'attendance_history' => [],
                'gallery'            => [],
                'subjects_analysis'  => [],
                'subjects_list'      => [],
                'tenant'             => $user->tenant,
            ]);
        }

        // Ambil seluruh rekam kehadiran anak beserta informasi sesi, mata pelajaran, dan tentor pengampu
        $attendances = Attendance::query()
            ->where('student_id', $student->id)
            ->with(['attendanceSession.tentor', 'attendanceSession.studyGroup'])
            ->get();

        // Urutkan secara kronologis terbalik (sesi paling baru di atas)
        $sortedAttendances = $attendances->sortByDesc(function ($att) {
            $date = $att->attendanceSession?->date?->format('Y-m-d') ?? '1970-01-01';
            $time = $att->attendanceSession?->created_at?->format('H:i:s') ?? '00:00:00';
            return $date . ' ' . $time;
        })->values();

        // Hitung metrik kehadiran untuk orang tua
        $totalSessions = $sortedAttendances->count();
        $presentCount  = $sortedAttendances->where('status', 'present')->count();
        $absentCount   = $sortedAttendances->where('status', 'absent')->count();
        $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;

        // Hitung streak kehadiran berurutan dari sesi terbaru
        $consecutiveStreak = 0;
        foreach ($sortedAttendances as $att) {
            if ($att->status === 'present') {
                $consecutiveStreak++;
            } else {
                break;
            }
        }

        // Tentukan Predikat Apresiasi Belajar & Pesan Hangat untuk Orang Tua
        if ($totalSessions === 0) {
            $badge = [
                'title'       => 'Mulai Petualangan Belajar 🌱',
                'grade'       => 'Baru Bergabung',
                'color'       => 'slate',
                'description' => 'Sesi pembelajaran ananda akan segera dimulai. Pantau terus kehadirannya di sini ya Ayah/Bunda!',
            ];
        } elseif ($attendanceRate >= 95) {
            $badge = [
                'title'       => 'Bintang Teladan Emas 🌟',
                'grade'       => 'Sangat Disiplin',
                'color'       => 'amber',
                'description' => 'Luar biasa! Ananda sangat konsisten dan penuh semangat mengikuti setiap sesi belajar. Pertahankan prestasi membanggakan ini!',
            ];
        } elseif ($attendanceRate >= 85) {
            $badge = [
                'title'       => 'Bintang Rajin Perak ⭐',
                'grade'       => 'Rajin & Aktif',
                'color'       => 'emerald',
                'description' => 'Bagus sekali! Ananda rajin mengikuti bimbingan belajar dengan tingkat kedisiplinan yang sangat baik.',
            ];
        } elseif ($attendanceRate >= 75) {
            $badge = [
                'title'       => 'Bintang Semangat Perunggu ✨',
                'grade'       => 'Cukup Baik',
                'color'       => 'blue',
                'description' => 'Ananda terus berproses. Berikan apresiasi dan dampingi ananda agar tidak melewatkan sesi materi berikutnya.',
            ];
        } else {
            $badge = [
                'title'       => 'Perlu Pendampingan Belajar 💡',
                'grade'       => 'Perlu Perhatian',
                'color'       => 'rose',
                'description' => 'Tingkat kehadiran ananda perlu ditingkatkan. Mari bersama luangkan waktu dan beri dorongan semangat belajar di rumah.',
            ];
        }

        // Sesi Pertemuan Terakhir yang Diikuti
        $latestSession = $sortedAttendances->first();

        // 1. Format Jurnal Riwayat Kehadiran
        $attendanceHistory = $sortedAttendances->map(function ($att) {
            $session = $att->attendanceSession;
            return [
                'id'                => (string) $att->id,
                'session_id'        => (string) ($session?->id ?? ''),
                'date'              => $session?->date ? $session->date->format('Y-m-d') : '-',
                'formatted_date'    => $session?->date ? $session->date->translatedFormat('l, d F Y') : '-',
                'time'              => $session?->created_at ? $session->created_at->format('H:i') . ' WIB' : '-',
                'subject_name'      => $session?->subject_name ?: 'Pelajaran Umum',
                'tentor_name'       => $session?->tentor?->full_name ?? ($session?->tentor?->name ?? 'Tentor Bimbel'),
                'study_group_name'  => $session?->studyGroup?->name ?? 'Kelas Belajar',
                'education_level'   => $session?->studyGroup?->education_level ?? '-',
                'topic_description' => $session?->topic_description ?: 'Materi dan latihan terpandu bersama tentor di kelas.',
                'status'            => $att->status,
                'status_label'      => $att->status === 'present' ? 'Hadir di Kelas' : 'Tidak Hadir',
                'notes'             => $att->notes,
                'photo_url'         => $session?->photo_url,
            ];
        })->values()->all();

        // 2. Format Galeri Dokumentasi Kegiatan Belajar Anak
        $gallery = $sortedAttendances
            ->filter(fn ($att) => !empty($att->attendanceSession?->photo_url))
            ->map(function ($att) {
                $session = $att->attendanceSession;
                return [
                    'id'             => (string) $session->id,
                    'photo_url'      => $session->photo_url,
                    'title'          => ($session->subject_name ?: 'Pelajaran') . ' • ' . ($session->studyGroup?->name ?? 'Kelas Bimbel'),
                    'date'           => $session->date ? $session->date->translatedFormat('d F Y') : '-',
                    'topic'          => $session->topic_description ?: 'Dokumentasi kegiatan belajar di kelas.',
                    'tentor'         => $session->tentor?->full_name ?? ($session->tentor?->name ?? 'Tentor Bimbel'),
                    'student_status' => $att->status,
                ];
            })
            ->values()
            ->all();

        // 3. Analisis Kehadiran per Mata Pelajaran
        $subjectsAnalysis = [];
        $bySubject = $sortedAttendances->groupBy(function ($att) {
            return $att->attendanceSession?->subject_name ?: 'Pelajaran Umum';
        });

        foreach ($bySubject as $subjectName => $items) {
            $subjTotal   = $items->count();
            $subjPresent = $items->where('status', 'present')->count();
            $subjRate    = $subjTotal > 0 ? round(($subjPresent / $subjTotal) * 100, 1) : 0;

            $subjectsAnalysis[] = [
                'subject_name'    => $subjectName,
                'total_sessions'  => $subjTotal,
                'present_count'   => $subjPresent,
                'absent_count'    => $subjTotal - $subjPresent,
                'attendance_rate' => $subjRate,
            ];
        }

        // Urutkan analisis: mapel dengan sesi terbanyak di atas
        usort($subjectsAnalysis, fn ($a, $b) => $b['total_sessions'] <=> $a['total_sessions']);

        $subjectsList = array_values(array_unique(array_column($subjectsAnalysis, 'subject_name')));

        return Inertia::render('Student/Dashboard', [
            'student' => [
                'id'                  => $student->id,
                'name'                => $student->name,
                'username'            => $student->username ?: '-',
                'nis'                 => $student->username ?: '-',
                'photo_url'           => $student->photo_url,
                'study_group_name'    => $student->studyGroup?->name ?? 'Belum Ditentukan',
                'education_level'     => $student->studyGroup?->education_level ?? '-',
                'academic_year_name'  => $student->academicYear?->name ?? 'Tahun Ajaran Aktif',
                'parent_phone'        => $student->parent_phone,
            ],
            'kpi' => [
                'total_sessions'     => $totalSessions,
                'present_count'      => $presentCount,
                'absent_count'       => $absentCount,
                'attendance_rate'    => $attendanceRate,
                'consecutive_streak' => $consecutiveStreak,
                'badge'              => $badge,
                'latest_session'     => $latestSession ? [
                    'date'         => $latestSession->attendanceSession?->date?->translatedFormat('d M Y'),
                    'subject'      => $latestSession->attendanceSession?->subject_name,
                    'tentor'       => $latestSession->attendanceSession?->tentor?->full_name ?? ($latestSession->attendanceSession?->tentor?->name ?? 'Tentor'),
                    'status'       => $latestSession->status,
                ] : null,
            ],
            'attendance_history' => $attendanceHistory,
            'gallery'            => $gallery,
            'subjects_analysis'  => $subjectsAnalysis,
            'subjects_list'      => $subjectsList,
            'tenant'             => $student->tenant,
        ]);
    }
}

