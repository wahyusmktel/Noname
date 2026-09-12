<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Statistik ringkas lembaga
        $institutionStats = [
            'total_students'  => 142, // Siswa aktif
            'total_tutors'    => 12,  // Tutor pengajar
            'total_classes'   => 8,   // Ruang kelas
            'attendance_rate' => 90.1,// Rata-rata kehadiran
            'joined_since'    => $tenant->created_at ? $tenant->created_at->translatedFormat('d F Y') : '12 September 2026',
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
            'brand_color'      => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama lembaga bimbel wajib diisi.',
            'phone.required'=> 'Nomor kontak resmi wajib diisi.',
            'city.required' => 'Kota domisili lembaga wajib diisi.',
            'email.email'   => 'Format email lembaga tidak valid.',
        ]);

        try {
            DB::beginTransaction();

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
                'brand_color'     => $validated['brand_color'] ?? '#F97316',
            ]);

            DB::commit();

            return back()->with('success', 'Profil lembaga bimbel berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui profil lembaga. Silakan coba lagi.',
            ]);
        }
    }
}
