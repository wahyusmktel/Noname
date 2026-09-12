<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\StudyGroup;
use App\Models\Tentor;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TutorAttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Form Absensi Guru / Tentor
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Ambil data tentor terkait user login
        $tentor = $user->tentor;
        if (!$tentor) {
            // Fallback cari tentor berdasarkan user_id atau email jika belum ter-link
            $tentor = Tentor::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
        }

        if (!$tentor && ($user->isSuperAdmin() || $user->hasRole('admin_bimbel'))) {
            $tentor = Tentor::where('tenant_id', $user->tenant_id)->first();
        }

        // Tahun pelajaran aktif
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Ambil daftar kelompok bimbel aktif beserta siswa di dalamnya
        $studyGroups = StudyGroup::query()
            ->where('is_active', true)
            ->with(['students' => function ($q) {
                $q->where('status', 'active')
                  ->orderBy('name', 'asc');
            }])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($group) {
                return [
                    'id'              => $group->id,
                    'name'            => $group->name,
                    'education_level' => $group->education_level,
                    'students_count'  => $group->students->count(),
                    'students'        => $group->students->map(function ($st) {
                        return [
                            'id'            => $st->id,
                            'name'          => $st->name,
                            'photo_url'     => $st->photo_url,
                            'student_phone' => $st->student_phone,
                            'parent_phone'  => $st->parent_phone,
                        ];
                    }),
                ];
            });

        // Riwayat singkat 5 sesi absensi terakhir yang pernah diinput guru ini
        $recentSessions = [];
        if ($tentor) {
            $recentSessions = AttendanceSession::query()
                ->where('tentor_id', $tentor->id)
                ->with(['studyGroup:id,name,education_level'])
                ->withCount([
                    'attendances as total_students',
                    'attendances as present_students' => fn ($q) => $q->where('status', 'present'),
                ])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(fn ($s) => [
                    'id'               => $s->id,
                    'date'             => $s->date->format('Y-m-d'),
                    'formatted_date'   => $s->date->translatedFormat('d M Y'),
                    'study_group_name' => $s->studyGroup ? $s->studyGroup->name : '-',
                    'education_level'  => $s->studyGroup ? $s->studyGroup->education_level : '-',
                    'subject_name'     => $s->subject_name,
                    'total_students'   => $s->total_students,
                    'present_students' => $s->present_students,
                    'photo_url'        => $s->photo_url,
                ]);
        }

        return Inertia::render('Tutor/Attendance/Create', [
            'tentor' => [
                'id'             => $tentor ? $tentor->id : null,
                'name'           => $tentor ? $tentor->full_name : $user->name,
                'specialization' => $tentor && $tentor->specialization ? $tentor->specialization : 'Mata Pelajaran Umum',
                'phone'          => $tentor ? $tentor->phone : $user->phone,
                'photo_url'      => $tentor ? $tentor->photo_url : null,
            ],
            'study_groups'        => $studyGroups,
            'recent_sessions'     => $recentSessions,
            'active_academic_year'=> $activeAcademicYear ? $activeAcademicYear->name : null,
            'today_date'          => Carbon::today()->format('Y-m-d'),
        ]);
    }

    /**
     * Simpan Data Absensi Siswa
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $tentor = $user->tentor;

        if (!$tentor) {
            $tentor = Tentor::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
        }

        if (!$tentor && ($user->isSuperAdmin() || $user->hasRole('admin_bimbel'))) {
            $tentor = Tentor::where('tenant_id', $user->tenant_id)->first();
        }

        if (!$tentor) {
            return back()->withErrors([
                'error' => 'Profil tentor tidak ditemukan untuk akun Anda. Harap hubungi administrator bimbel.',
            ]);
        }

        $validated = $request->validate([
            'date'                  => 'required|date',
            'study_group_id'        => 'required|uuid|exists:study_groups,id',
            'topic_description'     => 'required|string|min:5|max:2000',
            'documentation_photo'   => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'attendances'           => 'required|array|min:1',
            'attendances.*.student_id' => 'required|uuid|exists:students,id',
            'attendances.*.status'     => 'required|in:present,absent',
            'attendances.*.notes'      => 'nullable|string|max:255',
        ], [
            'date.required'                  => 'Tanggal pertemuan wajib diisi.',
            'date.date'                      => 'Format tanggal pertemuan tidak valid.',
            'study_group_id.required'        => 'Kelompok bimbel wajib dipilih.',
            'study_group_id.exists'          => 'Kelompok bimbel tidak valid.',
            'topic_description.required'     => 'Deskripsi materi yang dipelajari wajib diisi.',
            'topic_description.min'          => 'Deskripsi materi minimal 5 karakter.',
            'documentation_photo.required'   => 'Foto dokumentasi kelas wajib diunggah.',
            'documentation_photo.image'      => 'Berkas dokumentasi harus berupa gambar.',
            'documentation_photo.max'        => 'Ukuran foto dokumentasi maksimal 10MB.',
            'attendances.required'           => 'Daftar kehadiran peserta didik wajib diisi.',
            'attendances.min'                => 'Minimal harus ada 1 peserta didik dalam kelompok.',
        ]);

        $tentorId = $tentor ? $tentor->id : null;

        // Validasi hindari 2 kali absensi untuk pertemuan yang sama terhadap guru tersebut
        $alreadyRecorded = AttendanceSession::query()
            ->where('tentor_id', $tentorId)
            ->where('study_group_id', $validated['study_group_id'])
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($alreadyRecorded) {
            $studyGroup = StudyGroup::find($validated['study_group_id']);
            $groupName = $studyGroup ? $studyGroup->name : 'kelompok ini';
            $dateFormatted = Carbon::parse($validated['date'])->translatedFormat('d F Y');

            return back()->withInput()->withErrors([
                'error' => "Absensi untuk {$groupName} pada tanggal {$dateFormatted} sudah pernah dicatat oleh Anda. Untuk menghindari duplikasi, absensi tidak dapat disimpan ulang.",
            ]);
        }

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->hasFile('documentation_photo')) {
                $photoPath = $request->file('documentation_photo')->store('attendance_photos', 'public');
            }

            $activeAcademicYear = AcademicYear::where('is_active', true)->first();

            $session = AttendanceSession::create([
                'tenant_id'            => $user->tenant_id,
                'tentor_id'            => $tentorId,
                'study_group_id'       => $validated['study_group_id'],
                'academic_year_id'     => $activeAcademicYear?->id,
                'date'                 => $validated['date'],
                'subject_name'         => $tentor && $tentor->specialization ? $tentor->specialization : 'Mata Pelajaran Umum',
                'topic_description'    => $validated['topic_description'],
                'documentation_photo'  => $photoPath,
                'created_by_user_id'   => $user->id,
            ]);

            // Catat presensi masing-masing siswa
            foreach ($validated['attendances'] as $att) {
                Attendance::create([
                    'tenant_id'             => $user->tenant_id,
                    'attendance_session_id' => $session->id,
                    'student_id'            => $att['student_id'],
                    'status'                => $att['status'],
                    'notes'                 => $att['notes'] ?? null,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Data absensi pertemuan berhasil disimpan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan absensi guru: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Terjadi kendala saat menyimpan absensi. Silakan coba lagi.',
            ]);
        }
    }
}
