<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Tenant;
use App\Models\Tentor;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionProfileController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Profil Lembaga Bimbel
     */
    public function show(Request $request): Response
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(404, 'Data lembaga bimbel tidak ditemukan.');
        }

        // Statistik riil lembaga dari database
        $totalStudents = Student::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();

        $totalTutors = Tentor::query()
            ->where('tenant_id', $tenant->id)
            ->count();

        $totalGroups = StudyGroup::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $totalAttendances = Attendance::query()
            ->where('tenant_id', $tenant->id)
            ->count();

        $presentAttendances = Attendance::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'present')
            ->count();

        $attendanceRate = $totalAttendances > 0
            ? round(($presentAttendances / $totalAttendances) * 100, 1)
            : 100.0;

        $institutionStats = [
            'total_students'  => $totalStudents,
            'total_tutors'    => $totalTutors,
            'total_groups'    => $totalGroups,
            'attendance_rate' => $attendanceRate,
            'joined_since'    => $tenant->created_at ? $tenant->created_at->translatedFormat('d F Y') : now()->translatedFormat('d F Y'),
        ];

        return Inertia::render('Institution/Profile', [
            'tenant' => $tenant,
            'stats'  => $institutionStats,
        ]);
    }

    /**
     * Perbarui Informasi Profil Lembaga Bimbel
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(404, 'Data lembaga bimbel tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'tagline'          => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'phone'            => 'required|string|max:25',
            'whatsapp_sender'  => 'nullable|string|max:25',
            'email'            => 'nullable|email|max:150',
            'website'          => 'nullable|string|max:150',
            'address'          => 'nullable|string|max:255',
            'city'             => 'required|string|max:100',
            'province'         => 'nullable|string|max:100',
            'postal_code'      => 'nullable|string|max:10',
            'operating_hours'  => 'nullable|string|max:100',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_logo'      => 'nullable|boolean',
        ], [
            'name.required' => 'Nama lembaga bimbel wajib diisi.',
            'phone.required'=> 'Nomor kontak resmi wajib diisi.',
            'city.required' => 'Kota domisili lembaga wajib diisi.',
            'email.email'   => 'Format email lembaga tidak valid.',
            'logo.image'    => 'Berkas logo harus berupa gambar.',
            'logo.max'      => 'Ukuran berkas logo maksimal 5MB.',
        ]);

        try {
            DB::beginTransaction();

            $logoPath = $tenant->logo;

            if ($request->boolean('remove_logo')) {
                if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                    Storage::disk('public')->delete($tenant->logo);
                }
                $logoPath = null;
            } elseif ($request->hasFile('logo')) {
                if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                    Storage::disk('public')->delete($tenant->logo);
                }
                $logoPath = $request->file('logo')->store('tenant_logos', 'public');
            }

            $tenant->update([
                'name'            => $validated['name'],
                'tagline'         => $validated['tagline'] ?? null,
                'description'     => $validated['description'] ?? null,
                'phone'           => $validated['phone'],
                'whatsapp_sender' => $validated['whatsapp_sender'] ?? $validated['phone'],
                'email'           => $validated['email'] ?? null,
                'website'         => $validated['website'] ?? null,
                'address'         => $validated['address'] ?? null,
                'city'            => $validated['city'],
                'province'        => $validated['province'] ?? null,
                'postal_code'     => $validated['postal_code'] ?? null,
                'operating_hours' => $validated['operating_hours'] ?? null,
                'logo'            => $logoPath,
            ]);

            DB::commit();

            return back()->with('success', 'Profil lembaga bimbel berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Gagal memperbarui profil lembaga: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui profil lembaga. Silakan coba lagi.',
            ]);
        }
    }
}
