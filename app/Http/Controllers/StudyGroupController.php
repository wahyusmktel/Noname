<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudyGroupController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman & Daftar Kelompok Bimbel
     */
    public function index(Request $request): Response
    {
        // Dapatkan tahun ajaran yang dipilih atau tahun ajaran aktif default
        $academicYearId = $request->query('academic_year_id');
        $currentYear = null;

        if ($academicYearId) {
            $currentYear = AcademicYear::find($academicYearId);
        }

        if (!$currentYear) {
            $currentYear = AcademicYear::where('is_active', true)->first()
                ?? AcademicYear::orderBy('created_at', 'desc')->first();
        }

        $allYears = AcademicYear::orderBy('is_active', 'desc')->orderBy('name', 'desc')->get();

        $query = StudyGroup::query();

        if ($currentYear) {
            $query->where('academic_year_id', $currentYear->id);
        }

        // Pencarian nama kelompok
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter jenjang pendidikan (SD, SMP, SMA)
        if ($educationLevel = $request->query('education_level')) {
            if (in_array($educationLevel, ['SD', 'SMP', 'SMA'], true)) {
                $query->where('education_level', $educationLevel);
            }
        }

        // Filter status aktif/nonaktif
        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $studyGroups = $query->withCount('students')
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage)
                            ->withQueryString();

        // Siswa pada tahun ajaran ini untuk kebutuhan modal mapping
        $studentsInYear = [];
        if ($currentYear) {
            $studentsInYear = Student::where('academic_year_id', $currentYear->id)
                ->where('status', 'active')
                ->select('id', 'name', 'study_group_id', 'student_phone')
                ->orderBy('name')
                ->get();
        }

        $stats = [
            'total'          => $currentYear ? StudyGroup::where('academic_year_id', $currentYear->id)->count() : 0,
            'active'         => $currentYear ? StudyGroup::where('academic_year_id', $currentYear->id)->where('is_active', true)->count() : 0,
            'total_students' => $currentYear ? Student::where('academic_year_id', $currentYear->id)->whereNotNull('study_group_id')->count() : 0,
        ];

        return Inertia::render('StudyGroup/Index', [
            'studyGroups'    => $studyGroups,
            'currentYear'    => $currentYear,
            'allYears'       => $allYears,
            'studentsInYear' => $studentsInYear,
            'stats'          => $stats,
            'filters'        => $request->only(['search', 'status', 'education_level', 'academic_year_id', 'per_page']),
        ]);
    }

    /**
     * Tambah Kelompok Bimbel Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'education_level'  => 'required|in:SD,SMP,SMA',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_active'        => 'nullable|boolean',
        ], [
            'name.required'             => 'Nama kelompok bimbel wajib diisi.',
            'education_level.required'  => 'Jenjang pendidikan wajib dipilih.',
            'education_level.in'        => 'Pilihan jenjang harus salah satu dari SD, SMP, atau SMA.',
            'academic_year_id.required' => 'Tahun pelajaran wajib dipilih.',
        ]);

        try {
            DB::beginTransaction();

            StudyGroup::create([
                'name'             => $validated['name'],
                'education_level'  => $validated['education_level'],
                'academic_year_id' => $validated['academic_year_id'],
                'is_active'        => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return back()->with('success', 'Kelompok bimbel baru berhasil dibuat!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal membuat kelompok bimbel.',
            ]);
        }
    }

    /**
     * Perbarui Data Kelompok Bimbel
     */
    public function update(Request $request, StudyGroup $studyGroup)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'education_level'  => 'required|in:SD,SMP,SMA',
            'academic_year_id' => 'required|exists:academic_years,id',
            'is_active'        => 'nullable|boolean',
        ], [
            'name.required'            => 'Nama kelompok bimbel wajib diisi.',
            'education_level.required' => 'Jenjang pendidikan wajib dipilih.',
            'education_level.in'       => 'Pilihan jenjang harus salah satu dari SD, SMP, atau SMA.',
        ]);

        try {
            DB::beginTransaction();

            $studyGroup->update([
                'name'             => $validated['name'],
                'education_level'  => $validated['education_level'],
                'academic_year_id' => $validated['academic_year_id'],
                'is_active'        => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return back()->with('success', 'Data kelompok bimbel berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui kelompok bimbel.',
            ]);
        }
    }

    /**
     * Hapus Kelompok Bimbel (Soft Deletes)
     */
    public function destroy(StudyGroup $studyGroup)
    {
        try {
            DB::beginTransaction();

            // Lepaskan siswa dari kelompok ini sebelum menghapus
            Student::where('study_group_id', $studyGroup->id)->update(['study_group_id' => null]);
            $studyGroup->delete();

            DB::commit();

            return back()->with('success', 'Kelompok bimbel berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus kelompok bimbel.',
            ]);
        }
    }

    /**
     * Fitur Mapping Siswa ke Kelompok Bimbel
     */
    public function mapStudents(Request $request, StudyGroup $studyGroup)
    {
        $validated = $request->validate([
            'student_ids' => 'present|array',
            'student_ids.*' => 'uuid|exists:students,id',
        ]);

        try {
            DB::beginTransaction();

            $selectedIds = $validated['student_ids'];

            // 1. Kosongkan siswa yang sebelumnya di kelompok ini tapi sekarang tidak dipilih
            Student::where('study_group_id', $studyGroup->id)
                ->whereNotIn('id', $selectedIds)
                ->update(['study_group_id' => null]);

            // 2. Pasang siswa yang terpilih ke kelompok ini
            if (!empty($selectedIds)) {
                Student::whereIn('id', $selectedIds)
                    ->update(['study_group_id' => $studyGroup->id]);
            }

            DB::commit();

            $count = count($selectedIds);
            return back()->with('success', "Sebanyak {$count} siswa berhasil dipetakan ke kelompok \"{$studyGroup->name}\"!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memetakan siswa ke kelompok.',
            ]);
        }
    }

    /**
     * Fitur Salin Anggota Kelompok dari Tahun Pelajaran Sebelumnya
     */
    public function copyFromPreviousYear(Request $request)
    {
        $validated = $request->validate([
            'source_year_id' => 'required|exists:academic_years,id',
            'target_year_id' => 'required|exists:academic_years,id|different:source_year_id',
        ], [
            'source_year_id.required'  => 'Pilih tahun pelajaran sumber.',
            'target_year_id.required'  => 'Pilih tahun pelajaran tujuan.',
            'target_year_id.different' => 'Tahun pelajaran tujuan harus berbeda dari tahun sumber.',
        ]);

        try {
            DB::beginTransaction();

            $sourceYearId = $validated['source_year_id'];
            $targetYearId = $validated['target_year_id'];

            $sourceGroups = StudyGroup::where('academic_year_id', $sourceYearId)->get();

            if ($sourceGroups->isEmpty()) {
                return back()->withErrors([
                    'error' => 'Tidak ada kelompok bimbel yang ditemukan pada tahun pelajaran sumber yang dipilih.',
                ]);
            }

            $copiedGroupsCount = 0;
            $mappedStudentsCount = 0;

            foreach ($sourceGroups as $sourceGroup) {
                // Buat atau temukan kelompok dengan nama yang sama di tahun tujuan
                $targetGroup = StudyGroup::firstOrCreate(
                    [
                        'academic_year_id' => $targetYearId,
                        'name'             => $sourceGroup->name,
                    ],
                    [
                        'education_level'  => $sourceGroup->education_level,
                        'is_active'        => $sourceGroup->is_active,
                    ]
                );

                if ($targetGroup->wasRecentlyCreated) {
                    $copiedGroupsCount++;
                }

                // Ambil seluruh siswa anggota kelompok sumber
                $sourceStudents = Student::where('study_group_id', $sourceGroup->id)->get();

                foreach ($sourceStudents as $sourceStudent) {
                    // Cari siswa di tahun tujuan dengan nama yang sama
                    $targetStudent = Student::where('academic_year_id', $targetYearId)
                        ->where('name', $sourceStudent->name)
                        ->first();

                    if ($targetStudent) {
                        $targetStudent->update(['study_group_id' => $targetGroup->id]);
                        $mappedStudentsCount++;
                    }
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil menyalin {$copiedGroupsCount} kelompok dan memetakan {$mappedStudentsCount} siswa ke tahun pelajaran baru!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menyalin kelompok dari tahun sebelumnya.',
            ]);
        }
    }
}
