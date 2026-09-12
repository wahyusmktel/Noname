<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Daftar Tahun Pelajaran
     */
    public function index(Request $request): Response
    {
        $academicYears = AcademicYear::withCount(['students', 'studyGroups'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('AcademicYear/Index', [
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Tambah Tahun Pelajaran Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ], [
            'name.required'     => 'Nama tahun pelajaran wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ]);

        try {
            DB::beginTransaction();

            $isActive = $request->boolean('is_active');

            // Jika belum ada tahun pelajaran yang ada, jadikan aktif otomatis
            if (AcademicYear::count() === 0) {
                $isActive = true;
            }

            if ($isActive) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            AcademicYear::create([
                'name'       => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date'   => $validated['end_date'] ?? null,
                'is_active'  => $isActive,
            ]);

            DB::commit();

            return back()->with('success', 'Tahun pelajaran baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menambahkan tahun pelajaran.',
            ]);
        }
    }

    /**
     * Perbarui Tahun Pelajaran
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ], [
            'name.required'     => 'Nama tahun pelajaran wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ]);

        try {
            DB::beginTransaction();

            $isActive = $request->boolean('is_active');

            if ($isActive && !$academicYear->is_active) {
                AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
            }

            $academicYear->update([
                'name'       => $validated['name'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date'   => $validated['end_date'] ?? null,
                'is_active'  => $isActive,
            ]);

            DB::commit();

            return back()->with('success', 'Data tahun pelajaran berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui tahun pelajaran.',
            ]);
        }
    }

    /**
     * Jadikan Tahun Pelajaran Aktif Utama
     */
    public function setActive(AcademicYear $academicYear)
    {
        try {
            DB::beginTransaction();

            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);

            DB::commit();

            return back()->with('success', "Tahun Pelajaran {$academicYear->name} diaktifkan sebagai tahun ajaran aktif!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal mengubah tahun pelajaran aktif.',
            ]);
        }
    }

    /**
     * Hapus Tahun Pelajaran (Soft Deletes)
     */
    public function destroy(AcademicYear $academicYear)
    {
        try {
            if ($academicYear->is_active) {
                return back()->withErrors([
                    'error' => 'Tahun pelajaran yang sedang aktif tidak dapat dihapus. Silakan aktifkan tahun pelajaran lain terlebih dahulu.',
                ]);
            }

            $academicYear->delete();

            return back()->with('success', 'Tahun pelajaran berhasil diarsipkan.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus tahun pelajaran.',
            ]);
        }
    }
}
