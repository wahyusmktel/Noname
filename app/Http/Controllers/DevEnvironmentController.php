<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DevEnvironmentController extends Controller
{
    /**
     * Tampilkan halaman kontrol status website development
     */
    public function index(Request $request): Response
    {
        $setting = SystemSetting::get('dev_environment_status', [
            'active'     => true,
            'message'    => 'Website development sedang tidak aktif. Akses dialihkan ke website utama.',
            'updated_at' => null,
            'updated_by' => null,
        ]);

        // Cek juga status fisik dari file jika ada
        $devStatusFile = '/var/www/bimbel-dev/storage/framework/dev_status.json';
        if (file_exists($devStatusFile)) {
            $fileData = json_decode(@file_get_contents($devStatusFile), true);
            if (is_array($fileData) && isset($fileData['active'])) {
                $setting['active'] = (bool) $fileData['active'];
                if (!empty($fileData['message'])) {
                    $setting['message'] = $fileData['message'];
                }
            }
        }

        return Inertia::render('System/DevEnvironment', [
            'status' => [
                'is_active'  => (bool) ($setting['active'] ?? true),
                'message'    => $setting['message'] ?? 'Website development sedang tidak aktif. Akses dialihkan ke website utama.',
                'updated_at' => $setting['updated_at'] ?? null,
                'updated_by' => $setting['updated_by'] ?? null,
            ],
            'dev_url' => 'https://dev.bimbelnoname.com',
        ]);
    }

    /**
     * Perbarui status aktif/nonaktif website development
     */
    public function toggle(Request $request): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
            'message'   => ['nullable', 'string', 'max:500'],
        ]);

        $isActive = (bool) $request->boolean('is_active');
        $defaultMsg = 'Website development sedang tidak aktif. Seluruh layanan resmi dialihkan ke website utama https://bimbelnoname.com.';
        $message = trim((string) $request->input('message')) ?: $defaultMsg;

        $payload = [
            'active'     => $isActive,
            'message'    => $message,
            'updated_at' => now()->toIso8601String(),
            'updated_by' => Auth::user()?->name ?? 'Superadmin',
        ];

        // 1. Simpan ke database
        SystemSetting::set('dev_environment_status', $payload, 'Status aktif/nonaktif website dev.bimbelnoname.com');

        // 2. Tulis ke file status lokal
        $localStatusFile = storage_path('framework/dev_status.json');
        @file_put_contents($localStatusFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 3. Tulis langsung ke path dev di server Ubuntu jika folder tersedia
        $remoteDevPath = '/var/www/bimbel-dev/storage/framework/dev_status.json';
        if (is_dir(dirname($remoteDevPath))) {
            @file_put_contents($remoteDevPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $feedbackMsg = $isActive 
            ? 'Website dev (dev.bimbelnoname.com) berhasil diaktifkan kembali.' 
            : 'Website dev (dev.bimbelnoname.com) berhasil dinonaktifkan. Pengunjung akan melihat halaman pemberitahuan.';

        return back()->with('success', $feedbackMsg);
    }
}
