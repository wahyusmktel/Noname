<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman & Daftar Mata Pelajaran
     */
    public function index(Request $request): Response
    {
        $query = Subject::query();

        // Pencarian dinamis server-side (Rule #8)
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter status jika ada
        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $subjects = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage)
                          ->withQueryString();

        // Statistik ringkas mata pelajaran
        $stats = [
            'total'    => Subject::count(),
            'active'   => Subject::where('is_active', true)->count(),
            'inactive' => Subject::where('is_active', false)->count(),
        ];

        return Inertia::render('Subject/Index', [
            'subjects' => $subjects,
            'stats'    => $stats,
            'filters'  => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Tambah Mata Pelajaran Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama mata pelajaran wajib diisi.',
            'name.max'      => 'Nama mata pelajaran maksimal 150 karakter.',
        ]);

        try {
            DB::beginTransaction();

            Subject::create([
                'name'      => $validated['name'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return back()->with('success', 'Mata pelajaran baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menambahkan data mata pelajaran. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Perbarui Data Mata Pelajaran
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama mata pelajaran wajib diisi.',
            'name.max'      => 'Nama mata pelajaran maksimal 150 karakter.',
        ]);

        try {
            DB::beginTransaction();

            $subject->update([
                'name'      => $validated['name'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return back()->with('success', 'Mata pelajaran berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui data mata pelajaran.',
            ]);
        }
    }

    /**
     * Hapus Data Mata Pelajaran (Soft Deletes - Rule #4)
     */
    public function destroy(Subject $subject)
    {
        try {
            $subject->delete();

            return back()->with('success', 'Mata pelajaran berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus mata pelajaran.',
            ]);
        }
    }
}
