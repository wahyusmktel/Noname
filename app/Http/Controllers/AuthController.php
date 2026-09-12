<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan halaman Login & Wizard Registrasi Bimbel
     */
    public function showLogin(Request $request): Response
    {
        return Inertia::render('Auth/AuthPage', [
            'initialTab' => $request->query('tab', 'login'),
        ]);
    }

    /**
     * Tampilkan halaman Registrasi Wizard secara langsung
     */
    public function showRegister(Request $request): Response
    {
        return Inertia::render('Auth/AuthPage', [
            'initialTab' => 'register',
        ]);
    }

    /**
     * Proses Login Pengguna Lembaga Bimbel
     */
    public function login(Request $request)
    {
        $loginInput = trim((string) $request->input('email'));
        $throttleKey = Str::lower($loginInput) . '|' . $request->ip();

        // Rate Limiter: Maksimal 5 percobaan per menit (Pedoman Keamanan Anti-Brute Force)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Silakan tunggu {$seconds} detik lagi.",
            ]);
        }

        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Alamat email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        try {
            $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $credentials = [
                $field => $loginInput,
                'password' => $request->input('password'),
            ];

            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                $user = Auth::user();

                // Validasi status akun
                if ($user->status !== 'active') {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi pengelola lembaga.',
                    ]);
                }

                return redirect()->intended('/dashboard')->with('success', "Selamat datang kembali, {$user->name}!");
            }

            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'Kombinasi email/username dan kata sandi tidak cocok dengan data kami.',
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'email' => config('app.debug') ? $e->getMessage() : 'Terjadi kendala pada sistem autentikasi. Silakan coba sesaat lagi.',
            ]);
        }
    }

    /**
     * Proses Wizard Registrasi Lembaga Bimbel Baru (Multi-Tenant)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            // Step 1: Data Lembaga Bimbel
            'institution_name' => 'required|string|max:150',
            'slug'             => 'required|string|max:50|alpha_dash|unique:tenants,slug',
            'city'             => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'address'          => 'nullable|string|max:255',

            // Step 2: Data Admin Lembaga
            'name'             => 'required|string|max:100',
            'email'            => 'required|string|email|max:150|unique:users,email',
            'admin_phone'      => 'required|string|max:20',
            'password'         => 'required|string|min:8|confirmed',

            // Step 3: Layanan Bimbel
            'package_type'     => 'nullable|string|in:trial,starter,pro,enterprise',
            'service_levels'   => 'nullable|array',
            'estimated_students' => 'nullable|string',
        ], [
            'institution_name.required' => 'Nama lembaga bimbel wajib diisi.',
            'slug.required'             => 'ID / Slug lembaga wajib diisi.',
            'slug.unique'               => 'ID Lembaga ini sudah digunakan. Silakan gunakan nama/ID lain.',
            'city.required'             => 'Kota domisili lembaga wajib diisi.',
            'phone.required'            => 'Nomor telepon lembaga wajib diisi.',
            'name.required'             => 'Nama lengkap admin wajib diisi.',
            'email.required'            => 'Email administrator wajib diisi.',
            'email.unique'              => 'Alamat email ini sudah terdaftar.',
            'password.required'         => 'Kata sandi wajib diisi.',
            'password.min'              => 'Kata sandi minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        DB::beginTransaction();
        try {
            // 1. Buat Tenant (Lembaga Bimbel) dengan UUID v4
            $tenant = Tenant::create([
                'name'         => $validated['institution_name'],
                'slug'         => Str::slug($validated['slug']),
                'email'        => $validated['email'],
                'phone'        => $validated['phone'],
                'city'         => $validated['city'],
                'address'      => $validated['address'] ?? null,
                'brand_color'  => '#F97316', // Soft Modern Orange
                'package_type' => $validated['package_type'] ?? 'trial',
                'status'       => 'active',
            ]);

            // 2. Buat User Admin Bimbel dengan UUID v4
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'phone'     => $validated['admin_phone'],
                'password'  => Hash::make($validated['password']),
                'role'      => 'admin_bimbel',
                'status'    => 'active',
            ]);

            DB::commit();

            // Otomatis login setelah registrasi berhasil
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat! Lembaga Bimbel Anda berhasil didaftarkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'register_error' => config('app.debug') ? $e->getMessage() : 'Gagal memproses pendaftaran lembaga bimbel. Silakan hubungi dukungan kami.',
            ]);
        }
    }

    /**
     * Logout Pengguna
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
