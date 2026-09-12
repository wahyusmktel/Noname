<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountSettingController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman Pengaturan Akun & Keamanan
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->loadMissing('tenant');

        return Inertia::render('User/Settings', [
            'account' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username ?: '-',
                'email'      => $user->email ?: '-',
                'phone'      => $user->phone ?: '-',
                'role'       => $user->role,
                'status'     => $user->status,
                'tenant_name'=> $user->tenant?->name ?? 'Lembaga Bimbel',
                'created_at' => $user->created_at ? $user->created_at->translatedFormat('d F Y, H:i') . ' WIB' : '-',
                'updated_at' => $user->updated_at ? $user->updated_at->translatedFormat('d F Y, H:i') . ' WIB' : '-',
            ],
        ]);
    }

    /**
     * Perbarui Kata Sandi Akun Pengguna
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'current_password'      => 'required|string',
                'password'              => ['required', 'string', 'confirmed', Password::min(8)],
                'password_confirmation' => 'required|string',
            ], [
                'current_password.required'      => 'Kata sandi saat ini wajib diisi.',
                'password.required'              => 'Kata sandi baru wajib diisi.',
                'password.min'                   => 'Kata sandi baru minimal 8 karakter.',
                'password.confirmed'             => 'Konfirmasi kata sandi baru tidak cocok.',
                'password_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
            ]);

            // Periksa apakah kata sandi saat ini benar
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
                ]);
            }

            // Update kata sandi
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Jika user adalah siswa, sinkronkan plain_password agar admin tetap dapat melihat jika dibutuhkan
            if ($user->role === 'siswa' || $user->role === 'orang_tua') {
                Student::where('user_id', $user->id)
                    ->orWhere('username', $user->username)
                    ->update(['plain_password' => $validated['password']]);
            }

            return back()->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('Update password error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withErrors([
                'error' => 'Gagal memperbarui kata sandi. Silakan coba beberapa saat lagi.',
            ]);
        }
    }
}
