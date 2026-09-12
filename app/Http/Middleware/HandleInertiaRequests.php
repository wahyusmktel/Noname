<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? $request->user()->loadMissing('tenant') : null,
            ],
            'app_logo' => function () use ($request) {
                $user = $request->user();
                if ($user && $user->tenant_id && $user->tenant) {
                    return $user->tenant->logo_url;
                }
                $defaultTenant = \App\Models\Tenant::where('slug', 'bimbel-no-name')->first()
                    ?? \App\Models\Tenant::first();
                return $defaultTenant?->logo_url ?? '/images/logo_bnn.png';
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'recent_notifications' => function () use ($request) {
                $user = $request->user();
                if (!$user || !$user->tenant_id) {
                    return [];
                }

                try {
                    // Notifikasi khusus Siswa / Orang Tua (Berkaitan langsung dengan absensi kehadiran ananda)
                    if ($user->role === 'siswa' || $user->role === 'orang_tua') {
                        $student = \App\Models\Student::query()
                            ->where('user_id', $user->id)
                            ->orWhere('username', $user->username)
                            ->first();

                        if (!$student) {
                            return [];
                        }

                        $attendances = \App\Models\Attendance::query()
                            ->where('student_id', $student->id)
                            ->with(['attendanceSession.tentor', 'attendanceSession.studyGroup'])
                            ->latest()
                            ->limit(8)
                            ->get();

                        return $attendances->map(function ($att) {
                            $session = $att->attendanceSession;
                            $tentorName = $session?->tentor?->name ?? 'Tentor Bimbel';
                            $subjectName = $session?->subject_name ?: 'Pelajaran';
                            $date = $session?->date ? $session->date->translatedFormat('d M Y') : 'Sesi Pertemuan';
                            $statusLabel = $att->status === 'present' ? 'Hadir di Kelas' : 'Tidak Hadir';
                            $statusIcon = $att->status === 'present' ? '✅' : '⚠️';

                            return [
                                'id'         => (string) $att->id,
                                'title'      => "{$statusIcon} Presensi Ananda: {$statusLabel}",
                                'desc'       => "Mapel {$subjectName} bersama {$tentorName} ({$date})",
                                'time'       => $att->created_at ? $att->created_at->diffForHumans() : 'Baru saja',
                                'created_at' => $att->created_at ? $att->created_at->toIso8601String() : null,
                                'url'        => '/student/dashboard',
                            ];
                        })->values()->all();
                    }

                    // Notifikasi untuk Admin & Tentor (Aktivitas presensi guru terkini)
                    $sessions = \App\Models\AttendanceSession::query()
                        ->where('tenant_id', $user->tenant_id)
                        ->with(['tentor', 'studyGroup'])
                        ->withCount('attendances')
                        ->latest()
                        ->limit(8)
                        ->get();

                    return $sessions->map(function ($s) {
                        $tentorName = $s->tentor?->name ?? 'Tentor Bimbel';
                        $groupName = $s->studyGroup?->name ?? 'Kelas Bimbel';
                        $subjectName = $s->subject_name ?: 'Pelajaran';
                        return [
                            'id'         => (string) $s->id,
                            'title'      => "Presensi Baru: {$tentorName}",
                            'desc'       => "{$subjectName} • Kelompok {$groupName} ({$s->attendances_count} siswa dicatat)",
                            'time'       => $s->created_at ? $s->created_at->diffForHumans() : 'Baru saja',
                            'created_at' => $s->created_at ? $s->created_at->toIso8601String() : null,
                            'url'        => '/reports/tutor-attendance',
                        ];
                    })->values()->all();
                } catch (\Throwable $e) {
                    return [];
                }
            },
        ];
    }
}
