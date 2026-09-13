<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Superadmin selalu memiliki akses penuh ke seluruh rute
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Cek apakah role user ada di dalam daftar role yang diizinkan
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // Jika request berbasis JSON / API
        if ($request->expectsJson()) {
            return response()->json([
                'success'    => false,
                'message'    => 'Akses ditolak: Anda tidak memiliki hak akses ke resource ini.',
                'error_code' => 'FORBIDDEN_ROLE_ACCESS'
            ], 403);
        }

        // Jika role adalah tutor, arahkan kembali ke form absensi guru miliknya dengan pesan peringatan
        if ($user->isTutor()) {
            return redirect()->route('tutor.attendance')->with('error', 'Akses ditolak: Halaman rekapitulasi & administrasi hanya dapat diakses oleh Admin.');
        }

        // Jika role adalah siswa / orang tua, arahkan ke portal monitoring
        if ($user->isStudent() || $user->isParent()) {
            return redirect()->route('student.dashboard')->with('error', 'Akses ditolak: Anda tidak memiliki izin untuk membuka halaman tersebut.');
        }

        abort(403, 'Akses Ditolak: Hak akses Anda tidak mencukupi untuk membuka halaman ini.');
    }
}
