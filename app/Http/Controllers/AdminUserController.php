<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman & Daftar Pengguna Admin & Staff
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $query = User::query()
            ->whereIn('role', ['admin_bimbel', 'staff']);

        if (!$user->isSuperAdmin() && $tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Pencarian dinamis server-side (Rule #8)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter role
        if ($role = $request->query('role')) {
            if (in_array($role, ['admin_bimbel', 'staff'])) {
                $query->where('role', $role);
            }
        }

        // Filter status
        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'])) {
                $query->where('status', $status);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $users = $query->orderBy('created_at', 'desc')
                       ->paginate($perPage)
                       ->withQueryString();

        // Hitung statistik admin & staff
        $baseStatsQuery = User::query()->whereIn('role', ['admin_bimbel', 'staff']);
        if (!$user->isSuperAdmin() && $tenantId) {
            $baseStatsQuery->where('tenant_id', $tenantId);
        }

        $stats = [
            'total'        => (clone $baseStatsQuery)->count(),
            'admin_bimbel' => (clone $baseStatsQuery)->where('role', 'admin_bimbel')->count(),
            'staff'        => (clone $baseStatsQuery)->where('role', 'staff')->count(),
            'inactive'     => (clone $baseStatsQuery)->where('status', 'inactive')->count(),
        ];

        return Inertia::render('AdminUser/Index', [
            'users'   => $users,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'role', 'status', 'per_page']),
        ]);
    }

    /**
     * Tambah Pengguna Admin Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|min:3|max:50|alpha_dash|unique:users,username',
            'email'    => 'nullable|email|max:150|unique:users,email',
            'phone'    => 'nullable|string|max:25',
            'role'     => ['required', Rule::in(['admin_bimbel', 'staff'])],
            'password' => 'required|string|min:6',
            'status'   => ['nullable', Rule::in(['active', 'inactive'])],
        ], [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan oleh akun lain.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan garis bawah.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah terdaftar.',
            'role.required'     => 'Role pengguna wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        try {
            DB::beginTransaction();

            $tenantId = $request->user()->tenant_id;

            User::create([
                'tenant_id' => $tenantId,
                'name'      => $validated['name'],
                'username'  => strtolower($validated['username']),
                'email'     => $validated['email'] ?? null,
                'phone'     => $validated['phone'] ?? null,
                'role'      => $validated['role'],
                'password'  => $validated['password'],
                'status'    => $validated['status'] ?? 'active',
            ]);

            DB::commit();

            return back()->with('success', 'Akun pengguna admin berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error store AdminUser: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menambahkan akun admin. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Perbarui Data Pengguna Admin
     */
    public function update(Request $request, User $admin_user)
    {
        // Validasi tenant access
        if (!$request->user()->isSuperAdmin() && $admin_user->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($admin_user->id),
            ],
            'email'    => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($admin_user->id),
            ],
            'phone'    => 'nullable|string|max:25',
            'role'     => ['required', Rule::in(['admin_bimbel', 'staff'])],
            'password' => 'nullable|string|min:6',
            'status'   => ['nullable', Rule::in(['active', 'inactive'])],
        ], [
            'name.required'     => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan oleh akun lain.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah terdaftar.',
            'role.required'     => 'Role pengguna wajib dipilih.',
            'password.min'      => 'Password baru minimal 6 karakter.',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name'     => $validated['name'],
                'username' => strtolower($validated['username']),
                'email'    => $validated['email'] ?? null,
                'phone'    => $validated['phone'] ?? null,
                'role'     => $validated['role'],
            ];

            // Cegah self-deactivation jika akun yang diedit adalah akun sendiri
            if ($admin_user->id === $request->user()->id) {
                $updateData['status'] = 'active';
            } elseif (!empty($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            if (!empty($validated['password'])) {
                $updateData['password'] = $validated['password'];
            }

            $admin_user->update($updateData);

            DB::commit();

            return back()->with('success', 'Data akun admin berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error update AdminUser: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui akun admin. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Hapus Pengguna Admin (Soft Delete)
     */
    public function destroy(Request $request, User $admin_user)
    {
        // Validasi tenant access
        if (!$request->user()->isSuperAdmin() && $admin_user->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Cegah menghapus diri sendiri
        if ($admin_user->id === $request->user()->id) {
            return back()->withErrors([
                'error' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
        }

        try {
            DB::beginTransaction();

            $admin_user->delete();

            DB::commit();

            return back()->with('success', 'Akun admin berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error destroy AdminUser: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus akun admin.',
            ]);
        }
    }

    /**
     * Toggle Status Pengguna Admin
     */
    public function toggleStatus(Request $request, User $admin_user)
    {
        // Validasi tenant access
        if (!$request->user()->isSuperAdmin() && $admin_user->tenant_id !== $request->user()->tenant_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Cegah nonaktifkan diri sendiri
        if ($admin_user->id === $request->user()->id) {
            return back()->withErrors([
                'error' => 'Anda tidak dapat mengubah status akun Anda sendiri.',
            ]);
        }

        try {
            $newStatus = $admin_user->status === 'active' ? 'inactive' : 'active';
            $admin_user->update(['status' => $newStatus]);

            $statusLabel = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';

            return back()->with('success', "Akun berhasil {$statusLabel}!");
        } catch (\Throwable $e) {
            Log::error('Error toggleStatus AdminUser: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Gagal mengubah status akun.',
            ]);
        }
    }
}
