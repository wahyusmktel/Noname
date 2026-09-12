<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Tampilkan Halaman Utama Dashboard Admin Bimbel
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        // Data statistik sementara untuk tampilan UI modern
        $stats = [
            'total_students'      => 142,
            'students_present'    => 128,
            'attendance_rate'     => 90.1,
            'active_sessions'     => 6,
            'total_tutors'        => 12,
            'tutors_active_today' => 8,
            'monthly_target'      => 95.0,
        ];

        // Sesi jadwal kelas hari ini
        $todaySessions = [
            [
                'id'            => 'sess-1',
                'class_name'    => 'Kelas Intensif UTBK TPS A',
                'tutor_name'    => 'Dr. Aris Sudrajat, M.Si.',
                'subject'       => 'Penalaran Matematika & TPS',
                'room'          => 'Ruang Einstein 1',
                'time_start'    => '08:00',
                'time_end'      => '10:00',
                'total_students'=> 24,
                'present_count' => 23,
                'status'        => 'completed', // completed, ongoing, upcoming
            ],
            [
                'id'            => 'sess-2',
                'class_name'    => 'Kelas 12 SMA Reguler Saintek',
                'tutor_name'    => 'Siti Nurhaliza, S.Pd.',
                'subject'       => 'Fisika Kuantum & Dinamika',
                'room'          => 'Ruang Newton 2',
                'time_start'    => '10:30',
                'time_end'      => '12:00',
                'total_students'=> 20,
                'present_count' => 18,
                'status'        => 'ongoing',
            ],
            [
                'id'            => 'sess-3',
                'class_name'    => 'Kelas 9 SMP Persiapan Ujian',
                'tutor_name'    => 'Reza Rahardian, S.Si.',
                'subject'       => 'Matematika Aljabar',
                'room'          => 'Ruang Galileo',
                'time_start'    => '13:30',
                'time_end'      => '15:00',
                'total_students'=> 18,
                'present_count' => 0,
                'status'        => 'upcoming',
            ],
            [
                'id'            => 'sess-4',
                'class_name'    => 'English Academic Mastery (TOEFL)',
                'tutor_name'    => 'Amanda Putri, M.Ed.',
                'subject'       => 'Bahasa Inggris',
                'room'          => 'Ruang Oxford',
                'time_start'    => '15:30',
                'time_end'      => '17:00',
                'total_students'=> 15,
                'present_count' => 0,
                'status'        => 'upcoming',
            ],
        ];

        // Aktivitas presensi terbaru (real-time stream)
        $recentLogs = [
            [
                'id'           => 'log-1',
                'student_name' => 'Dimas Arya Pratama',
                'nis'          => '2026-0089',
                'class'        => 'Kelas Intensif UTBK TPS A',
                'time'         => '07:54 WIB',
                'method'       => 'QR Code Scanner',
                'status'       => 'Hadir Tepat Waktu',
                'status_type'  => 'success',
            ],
            [
                'id'           => 'log-2',
                'student_name' => 'Nabila Salsabila',
                'nis'          => '2026-0045',
                'class'        => 'Kelas Intensif UTBK TPS A',
                'time'         => '07:58 WIB',
                'method'       => 'Self Check-in',
                'status'       => 'Hadir Tepat Waktu',
                'status_type'  => 'success',
            ],
            [
                'id'           => 'log-3',
                'student_name' => 'Rifqi Ramadhan',
                'nis'          => '2026-0112',
                'class'        => 'Kelas 12 SMA Reguler',
                'time'         => '10:35 WIB',
                'method'       => 'Manual Input Tutor',
                'status'       => 'Terlambat 5 Menit',
                'status_type'  => 'warning',
            ],
            [
                'id'           => 'log-4',
                'student_name' => 'Adinda Kirana',
                'nis'          => '2026-0063',
                'class'        => 'Kelas 12 SMA Reguler',
                'time'         => '10:40 WIB',
                'method'       => 'Surat Izin Dokter',
                'status'       => 'Izin Sakit',
                'status_type'  => 'info',
            ],
        ];

        return Inertia::render('Dashboard/Index', [
            'stats'         => $stats,
            'todaySessions' => $todaySessions,
            'recentLogs'    => $recentLogs,
            'tenant'        => $tenant,
        ]);
    }
}
