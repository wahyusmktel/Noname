<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Tentor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Profil Pengguna (Hanya Ganti Foto)
     */
    public function show(Request $request): Response
    {
        $user = $request->user()->loadMissing('tenant');

        $extraDetails = [];

        // Jika tentor/guru bimbel, sertakan data tentor
        if ($user->role === 'tutor') {
            $tentor = Tentor::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            if ($tentor) {
                $extraDetails['tentor'] = [
                    'specialization' => $tentor->specialization ?: 'Mata Pelajaran Umum',
                    'title_suffix'   => $tentor->title_suffix,
                    'phone'          => $tentor->phone,
                ];
            }
        }

        // Jika siswa / orang tua, sertakan data siswa
        if ($user->role === 'siswa' || $user->role === 'orang_tua') {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->orWhere('username', $user->username)
                ->with(['studyGroup', 'academicYear'])
                ->first();

            if ($student) {
                $extraDetails['student'] = [
                    'nis'              => $student->username,
                    'study_group_name' => $student->studyGroup?->name ?? 'Belum Ditentukan',
                    'education_level'  => $student->studyGroup?->education_level ?? '-',
                    'academic_year'    => $student->academicYear?->name ?? '-',
                    'parent_phone'     => $student->parent_phone,
                ];
            }
        }

        return Inertia::render('User/Profile', [
            'profile' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username ?: '-',
                'email'      => $user->email ?: '-',
                'phone'      => $user->phone ?: '-',
                'role'       => $user->role,
                'avatar_url' => $user->avatar_url,
                'status'     => $user->status,
                'created_at' => $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-',
                'tenant_name'=> $user->tenant?->name ?? 'Lembaga Bimbel',
                'details'    => $extraDetails,
            ],
        ]);
    }

    /**
     * Perbarui Foto Profil Avatar Pengguna
     */
    public function updatePhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            ], [
                'photo.required' => 'Berkas foto wajib dipilih.',
                'photo.image'    => 'Berkas harus berupa gambar.',
                'photo.mimes'    => 'Format foto harus berupa jpeg, png, jpg, atau webp.',
                'photo.max'      => 'Ukuran foto maksimal 5MB.',
            ]);

            $user = $request->user();

            // Hapus foto lama jika ada
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Simpan foto baru
            $path = $request->file('photo')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();

            // Sinkronkan ke profil Tentor jika user adalah guru
            if ($user->role === 'tutor') {
                $query = Tentor::where('user_id', $user->id);
                if (!empty($user->email)) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->whereNotNull('email')->where('email', $user->email);
                    });
                }
                $query->update(['photo' => $path]);
            }

            // Sinkronkan ke profil Student jika user adalah siswa
            if ($user->role === 'siswa' || $user->role === 'orang_tua') {
                $query = Student::where('user_id', $user->id);
                if (!empty($user->username)) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->whereNotNull('username')->where('username', $user->username);
                    });
                }
                $query->update(['photo' => $path]);
            }

            return back()->with('success', 'Foto profil berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('Update avatar error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withErrors(['photo' => 'Gagal memperbarui foto profil. Silakan coba lagi.']);
        }
    }

    /**
     * Hapus Foto Profil Avatar Pengguna
     */
    public function deletePhoto(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = null;
            $user->save();

            if ($user->role === 'tutor') {
                $query = Tentor::where('user_id', $user->id);
                if (!empty($user->email)) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->whereNotNull('email')->where('email', $user->email);
                    });
                }
                $query->update(['photo' => null]);
            }

            if ($user->role === 'siswa' || $user->role === 'orang_tua') {
                $query = Student::where('user_id', $user->id);
                if (!empty($user->username)) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->whereNotNull('username')->where('username', $user->username);
                    });
                }
                $query->update(['photo' => null]);
            }

            return back()->with('success', 'Foto profil berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Delete avatar error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withErrors(['error' => 'Gagal menghapus foto profil. Silakan coba lagi.']);
        }
    }
}
