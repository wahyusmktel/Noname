<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Tentor;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TentorController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Tabel & Daftar Tentor Bimbel
     */
    public function index(Request $request): Response
    {
        $query = Tentor::query();

        // Pencarian dinamis server-side (Rule #8)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        // Filter status jika ada
        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $tentors = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage)
                         ->withQueryString();

        // Daftar referensi gelar akademik populer di Indonesia
        $titlePrefixes = [
            'Dr.',
            'Drs.',
            'Dra.',
            'Prof.',
            'Ir.',
            'Ns.',
            'apt.',
            'Ustadz',
            'K.H.',
        ];

        $titleSuffixes = [
            'S.Pd.',
            'M.Pd.',
            'S.Si.',
            'M.Si.',
            'S.Kom.',
            'M.Kom.',
            'S.T.',
            'M.T.',
            'S.E.',
            'M.M.',
            'S.S.',
            'M.Hum.',
            'S.Sos.',
            'S.Psi.',
            'Ph.D.',
            'B.Sc.',
            'M.Sc.',
            'M.Ed.',
        ];

        // Daftar mata pelajaran aktif lembaga
        $subjects = Subject::where('is_active', true)
                           ->orderBy('name', 'asc')
                           ->get(['id', 'name']);

        return Inertia::render('Tentor/Index', [
            'tentors'       => $tentors,
            'filters'       => $request->only(['search', 'status', 'per_page']),
            'titlePrefixes' => $titlePrefixes,
            'titleSuffixes' => $titleSuffixes,
            'subjects'      => $subjects,
        ]);
    }

    /**
     * Tambah Tentor Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'title_prefix'   => 'nullable|string|max:50',
            'title_suffix'   => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:25',
            'email'          => 'nullable|email|max:150',
            'specialization' => 'nullable|string|max:100',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'         => 'nullable|in:active,inactive',
        ], [
            'name.required' => 'Nama tentor wajib diisi.',
            'photo.image'   => 'Berkas foto profil harus berupa gambar.',
            'photo.max'     => 'Ukuran foto profil maksimal 2MB.',
        ]);

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('tentors', 'public');
            }

            Tentor::create([
                'name'           => $validated['name'],
                'title_prefix'   => $validated['title_prefix'] ?? null,
                'title_suffix'   => $validated['title_suffix'] ?? null,
                'phone'          => $validated['phone'] ?? null,
                'email'          => $validated['email'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'photo'          => $photoPath,
                'status'         => $validated['status'] ?? 'active',
            ]);

            DB::commit();

            return back()->with('success', 'Tentor baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menambahkan data tentor. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Perbarui Data Tentor
     */
    public function update(Request $request, Tentor $tentor)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'title_prefix'   => 'nullable|string|max:50',
            'title_suffix'   => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:25',
            'email'          => 'nullable|email|max:150',
            'specialization' => 'nullable|string|max:100',
            'photo'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'         => 'nullable|in:active,inactive',
        ], [
            'name.required' => 'Nama tentor wajib diisi.',
            'photo.image'   => 'Berkas foto profil harus berupa gambar.',
            'photo.max'     => 'Ukuran foto profil maksimal 2MB.',
        ]);

        try {
            DB::beginTransaction();

            $photoPath = $tentor->photo;
            if ($request->hasFile('photo')) {
                if ($tentor->photo && Storage::disk('public')->exists($tentor->photo)) {
                    Storage::disk('public')->delete($tentor->photo);
                }
                $photoPath = $request->file('photo')->store('tentors', 'public');
            }

            $tentor->update([
                'name'           => $validated['name'],
                'title_prefix'   => $validated['title_prefix'] ?? null,
                'title_suffix'   => $validated['title_suffix'] ?? null,
                'phone'          => $validated['phone'] ?? null,
                'email'          => $validated['email'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'photo'          => $photoPath,
                'status'         => $validated['status'] ?? $tentor->status,
            ]);

            DB::commit();

            return back()->with('success', 'Data tentor berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui data tentor.',
            ]);
        }
    }

    /**
     * Hapus Data Tentor (Soft Deletes - Rule #4)
     */
    public function destroy(Tentor $tentor)
    {
        try {
            $tentor->delete();

            return back()->with('success', 'Data tentor berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus data tentor.',
            ]);
        }
    }

    /**
     * Generate Akun Login (Username & Password) untuk Tentor
     */
    public function generateAccounts(Request $request)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        config(['hashing.bcrypt.rounds' => 10]);

        try {
            DB::beginTransaction();

            $tentorsWithoutAccount = Tentor::where(function ($q) {
                $q->whereNull('user_id')->orWhereNull('username');
            })->orderBy('name', 'asc')->get();

            if ($tentorsWithoutAccount->isEmpty()) {
                DB::rollBack();
                return back()->with('info', 'Semua tentor sudah memiliki akun.');
            }

            $existingUserMap = User::where('username', 'like', '26%')->pluck('username')->flip()->all();
            $generatedCount = 0;
            $tenantId = auth()->user()?->tenant_id;

            foreach ($tentorsWithoutAccount as $tentor) {
                // Username: 6 digit angka berawalan 26 (contoh: 268492)
                do {
                    $username = '26' . str_pad((string) mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                } while (isset($existingUserMap[$username]));
                $existingUserMap[$username] = true;

                // Password: 6 digit angka acak (contoh: 749201)
                $plainPassword = (string) mt_rand(100000, 999999);

                $user = User::create([
                    'tenant_id' => $tentor->tenant_id ?? $tenantId,
                    'name'      => $tentor->full_name,
                    'username'  => $username,
                    'email'     => $tentor->email ?: null,
                    'password'  => $plainPassword,
                    'role'      => 'tutor',
                    'status'    => 'active',
                ]);

                $tentor->update([
                    'user_id'        => $user->id,
                    'username'       => $username,
                    'plain_password' => $plainPassword,
                ]);

                $generatedCount++;
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$generatedCount} akun tentor baru!");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('generateAccounts tentor error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal men-generate akun tentor.',
            ]);
        }
    }

    /**
     * Reset Password Tentor (Membuat 6 Digit Password Baru)
     */
    public function resetPassword(Request $request, Tentor $tentor)
    {
        try {
            DB::beginTransaction();

            $newPassword = (string) mt_rand(100000, 999999);

            if (!$tentor->user_id) {
                $existingUserMap = User::where('username', 'like', '26%')->pluck('username')->flip()->all();
                do {
                    $username = '26' . str_pad((string) mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                } while (isset($existingUserMap[$username]));

                $user = User::create([
                    'tenant_id' => $tentor->tenant_id ?? auth()->user()?->tenant_id,
                    'name'      => $tentor->full_name,
                    'username'  => $username,
                    'email'     => $tentor->email ?: null,
                    'password'  => $newPassword,
                    'role'      => 'tutor',
                    'status'    => 'active',
                ]);

                $tentor->update([
                    'user_id'        => $user->id,
                    'username'       => $username,
                    'plain_password' => $newPassword,
                ]);
            } else {
                $user = User::find($tentor->user_id);
                if ($user) {
                    $user->update([
                        'password' => $newPassword,
                    ]);
                }
                $tentor->update([
                    'plain_password' => $newPassword,
                ]);
            }

            DB::commit();

            return back()->with('success', "Password untuk tentor {$tentor->full_name} berhasil direset menjadi: {$newPassword}");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal mereset password tentor.',
            ]);
        }
    }

    /**
     * Unduh Rekap Akun Tentor (Format Excel)
     */
    public function exportAccounts(Request $request): StreamedResponse
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $tentors = Tentor::orderBy('name', 'asc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Akun Tentor');

        // Header Info
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'REKAP DATA AKUN TENTOR (GURU BIMBEL)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EA580C'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Dicetak pada: ' . date('d/m/Y H:i'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Header
        $headers = ['No', 'Nama Lengkap Tentor', 'Mata Pelajaran', 'Nomor HP', 'Email', 'Username', 'Password', 'Status'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        $sheet->getRowDimension(4)->setRowHeight(26);
        foreach ($headers as $idx => $header) {
            $col = $columns[$idx];
            $cell = $col . '4';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F97316'],
                ],
                'alignment' => [
                    'horizontal' => in_array($col, ['A', 'D', 'F', 'G', 'H']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Data Rows
        $row = 5;
        $no = 1;
        foreach ($tentors as $t) {
            $sheet->getRowDimension($row)->setRowHeight(22);
            $isEven = ($no % 2 === 0);
            $rowBg = $isEven ? 'FFF7ED' : 'FFFFFF';

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $t->full_name);
            $sheet->setCellValue('C' . $row, $t->specialization ?: '-');
            $sheet->setCellValue('D' . $row, $t->phone ?: '-');
            $sheet->setCellValue('E' . $row, $t->email ?: '-');
            $sheet->setCellValueExplicit('F' . $row, (string) ($t->username ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $row, (string) ($t->plain_password ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('H' . $row, $t->status === 'active' ? 'Aktif' : 'Nonaktif');

            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rowBg],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FED7AA'],
                    ],
                ],
            ]);

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);

            $row++;
        }

        if ($tentors->isEmpty()) {
            $sheet->mergeCells("A5:H5");
            $sheet->setCellValue("A5", "Belum ada data tentor.");
            $sheet->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A5")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('94A3B8'));
            $sheet->getRowDimension(5)->setRowHeight(24);
        }

        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Akun_Tentor_' . date('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
