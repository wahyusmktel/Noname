<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\Tentor;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ManualAttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Input Presensi Manual (Solusi Darurat & Guru yang Lupa/Terkendala Alat)
     */
    public function index(Request $request): Response
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenant_id;

            // 1. Ambil Tahun Pelajaran Aktif
            $activeAcademicYear = AcademicYear::where('is_active', true)->first();

            // 2. Ambil Daftar Kelompok Belajar Aktif beserta Peserta Didik di dalamnya
            $studyGroups = StudyGroup::query()
                ->where('is_active', true)
                ->with(['students' => function ($q) {
                    $q->where('status', 'active')
                      ->orderBy('name', 'asc');
                }])
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn ($g) => [
                    'id'              => $g->id,
                    'name'            => $g->name,
                    'education_level' => $g->education_level,
                    'students_count'  => $g->students->count(),
                    'students'        => $g->students->map(fn ($s) => [
                        'id'            => $s->id,
                        'name'          => $s->name,
                        'nis'           => $s->nis ?? '-',
                        'photo_url'     => $s->photo_url,
                        'student_phone' => $s->student_phone,
                        'parent_phone'  => $s->parent_phone,
                    ]),
                ]);

            // 3. Ambil Daftar Tentor / Guru Bimbel
            $tentors = Tentor::query()
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn ($t) => [
                    'id'    => $t->id,
                    'name'  => $t->name,
                    'nip'   => $t->nip ?? '-',
                    'title' => $t->title ?? 'Tutor Bimbel',
                ]);

            // 4. Ambil Daftar Mata Pelajaran
            $subjects = Subject::query()
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            // 5. Riwayat 10 Sesi Presensi Terbaru
            $recentSessions = AttendanceSession::query()
                ->with(['tentor:id,name', 'studyGroup:id,name,education_level'])
                ->withCount([
                    'attendances as total_students',
                    'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
                    'attendances as absent_count' => fn ($q) => $q->where('status', 'absent'),
                ])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(fn ($s) => [
                    'id'               => $s->id,
                    'date'             => $s->date->format('Y-m-d'),
                    'formatted_date'   => $s->date->translatedFormat('d F Y'),
                    'tentor_name'      => $s->tentor ? $s->tentor->name : '-',
                    'study_group_name' => $s->studyGroup ? $s->studyGroup->name : '-',
                    'education_level'  => $s->studyGroup ? $s->studyGroup->education_level : '-',
                    'subject_name'     => $s->subject_name,
                    'topic_description'=> $s->topic_description,
                    'photo_url'        => $s->photo_url,
                    'total_students'   => $s->total_students,
                    'present_count'    => $s->present_count,
                    'absent_count'     => $s->absent_count,
                    'created_at_human' => $s->created_at->diffForHumans(),
                ]);

            return Inertia::render('Attendance/Manual', [
                'studyGroups'        => $studyGroups,
                'tentors'            => $tentors,
                'subjects'           => $subjects,
                'activeAcademicYear' => $activeAcademicYear,
                'recentSessions'     => $recentSessions,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal memuat halaman input presensi manual: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Attendance/Manual', [
                'studyGroups'        => [],
                'tentors'            => [],
                'subjects'           => [],
                'activeAcademicYear' => null,
                'recentSessions'     => [],
            ]);
        }
    }

    /**
     * Simpan Data Presensi Manual Susulan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'study_group_id'       => 'required|uuid|exists:study_groups,id',
            'tentor_id'            => 'required|uuid|exists:tentors,id',
            'date'                 => 'required|date',
            'subject_name'         => 'required|string|max:150',
            'topic_description'    => 'required|string|max:1000',
            'documentation_photo'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'students'             => 'required|array|min:1',
            'students.*.student_id'=> 'required|uuid|exists:students,id',
            'students.*.status'    => 'required|in:present,absent',
            'students.*.notes'     => 'nullable|string|max:255',
        ], [
            'study_group_id.required'    => 'Kelompok belajar wajib dipilih.',
            'tentor_id.required'         => 'Guru / Tentor pengampu wajib dipilih.',
            'date.required'              => 'Tanggal sesi presensi wajib diisi.',
            'subject_name.required'      => 'Mata pelajaran wajib diisi.',
            'topic_description.required' => 'Materi/jurnal pembelajaran wajib diisi.',
            'documentation_photo.image'  => 'Berkas foto dokumentasi harus berupa gambar.',
            'documentation_photo.max'    => 'Ukuran foto dokumentasi maksimal 10MB.',
            'students.required'          => 'Daftar kehadiran siswa wajib disertakan.',
            'students.min'               => 'Minimal 1 siswa harus terdaftar di presensi.',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $tenantId = $user->tenant_id;
            $activeAcademicYear = AcademicYear::where('is_active', true)->first();

            // Simpan foto dokumentasi jika ada yang diunggah
            $photoPath = null;
            if ($request->hasFile('documentation_photo')) {
                $photoFile = $request->file('documentation_photo');
                $photoPath = $photoFile->store('attendance_photos/' . $tenantId, 'public');
            }

            // 1. Simpan Sesi Absensi
            $session = AttendanceSession::create([
                'tenant_id'          => $tenantId,
                'tentor_id'          => $validated['tentor_id'],
                'study_group_id'     => $validated['study_group_id'],
                'academic_year_id'   => $activeAcademicYear?->id,
                'date'               => Carbon::parse($validated['date'])->toDateString(),
                'subject_name'       => $validated['subject_name'],
                'topic_description'  => $validated['topic_description'],
                'documentation_photo'=> $photoPath,
                'created_by_user_id' => $user->id,
            ]);

            // 2. Simpan Data Kehadiran Setiap Siswa
            foreach ($validated['students'] as $st) {
                Attendance::create([
                    'tenant_id'             => $tenantId,
                    'attendance_session_id' => $session->id,
                    'student_id'            => $st['student_id'],
                    'status'                => $st['status'],
                    'notes'                 => $st['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Data presensi manual berhasil disimpan dan disinkronkan ke rekapitulasi serta portal orang tua.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menyimpan presensi manual: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'general' => config('app.debug') ? $e->getMessage() : 'Terjadi kendala saat menyimpan data presensi manual. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Hapus Sesi Presensi Manual (Soft Delete)
     */
    public function destroy(AttendanceSession $attendanceSession)
    {
        try {
            DB::beginTransaction();

            // Hapus attendances terkait
            $attendanceSession->attendances()->delete();
            $attendanceSession->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Data sesi presensi berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal menghapus sesi presensi: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->withErrors([
                'general' => 'Gagal menghapus data sesi presensi.',
            ]);
        }
    }
}
