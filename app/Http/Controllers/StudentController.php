<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    use ApiResponse;

    /**
     * Tampilkan Halaman & Daftar Peserta Didik
     */
    public function index(Request $request): Response
    {
        // Dapatkan tahun ajaran yang dipilih atau tahun ajaran aktif default
        $academicYearId = $request->query('academic_year_id');
        $currentYear = null;

        if ($academicYearId) {
            $currentYear = AcademicYear::find($academicYearId);
        }

        if (!$currentYear) {
            $currentYear = AcademicYear::where('is_active', true)->first()
                ?? AcademicYear::orderBy('created_at', 'desc')->first();
        }

        $allYears = AcademicYear::orderBy('is_active', 'desc')->orderBy('name', 'desc')->get();

        $query = Student::query()->with('studyGroup');

        if ($currentYear) {
            $query->where('academic_year_id', $currentYear->id);
        }

        // Pencarian nama, nomor HP siswa, atau nomor HP orang tua (Rule #8)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('student_phone', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan kelompok bimbel
        if ($studyGroupId = $request->query('study_group_id')) {
            if ($studyGroupId === 'unassigned') {
                $query->whereNull('study_group_id');
            } else {
                $query->where('study_group_id', $studyGroupId);
            }
        }

        // Filter status (aktif / nonaktif keluar)
        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $students = $query->orderBy('name', 'asc')
                          ->paginate($perPage)
                          ->withQueryString();

        // Daftar kelompok bimbel pada tahun ajaran ini
        $studyGroups = [];
        if ($currentYear) {
            $studyGroups = StudyGroup::where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'education_level']);
        }

        // Statistik ringkas siswa
        $stats = [
            'total'      => $currentYear ? Student::where('academic_year_id', $currentYear->id)->count() : 0,
            'active'     => $currentYear ? Student::where('academic_year_id', $currentYear->id)->where('status', 'active')->count() : 0,
            'inactive'   => $currentYear ? Student::where('academic_year_id', $currentYear->id)->where('status', 'inactive')->count() : 0,
            'unassigned' => $currentYear ? Student::where('academic_year_id', $currentYear->id)->whereNull('study_group_id')->count() : 0,
        ];

        return Inertia::render('Student/Index', [
            'students'    => $students,
            'currentYear' => $currentYear,
            'allYears'    => $allYears,
            'studyGroups' => $studyGroups,
            'stats'       => $stats,
            'filters'     => $request->only(['search', 'status', 'study_group_id', 'academic_year_id', 'per_page']),
        ]);
    }

    /**
     * Tambah Data Peserta Didik Baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id'   => 'nullable|exists:study_groups,id',
            'name'             => 'required|string|max:150',
            'parent_phone'     => 'nullable|string|max:25',
            'student_phone'    => 'nullable|string|max:25',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'           => 'nullable|in:active,inactive',
        ], [
            'name.required'             => 'Nama peserta didik wajib diisi.',
            'academic_year_id.required' => 'Tahun pelajaran wajib ditentukan.',
            'photo.image'               => 'Berkas foto profil harus berupa gambar.',
            'photo.max'                 => 'Ukuran foto profil maksimal 2MB.',
        ]);

        try {
            DB::beginTransaction();

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
            }

            Student::create([
                'academic_year_id' => $validated['academic_year_id'],
                'study_group_id'   => $validated['study_group_id'] ?? null,
                'name'             => $validated['name'],
                'parent_phone'     => $validated['parent_phone'] ?? null,
                'student_phone'    => $validated['student_phone'] ?? null,
                'photo'            => $photoPath,
                'status'           => $validated['status'] ?? 'active',
            ]);

            DB::commit();

            return back()->with('success', 'Peserta didik baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menambahkan data peserta didik.',
            ]);
        }
    }

    /**
     * Perbarui Data Peserta Didik
     */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'study_group_id'   => 'nullable|exists:study_groups,id',
            'name'             => 'required|string|max:150',
            'parent_phone'     => 'nullable|string|max:25',
            'student_phone'    => 'nullable|string|max:25',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'           => 'nullable|in:active,inactive',
        ], [
            'name.required' => 'Nama peserta didik wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            $photoPath = $student->photo;
            if ($request->hasFile('photo')) {
                if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                    Storage::disk('public')->delete($student->photo);
                }
                $photoPath = $request->file('photo')->store('students', 'public');
            }

            $student->update([
                'academic_year_id' => $validated['academic_year_id'],
                'study_group_id'   => $validated['study_group_id'] ?? null,
                'name'             => $validated['name'],
                'parent_phone'     => $validated['parent_phone'] ?? null,
                'student_phone'    => $validated['student_phone'] ?? null,
                'photo'            => $photoPath,
                'status'           => $validated['status'] ?? $student->status,
            ]);

            // Sinkronkan ke akun User terkait jika sudah ada akun login
            if ($student->user_id) {
                User::where('id', $student->user_id)->update([
                    'name'   => $student->name,
                    'avatar' => $photoPath,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Data peserta didik berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal memperbarui data peserta didik.',
            ]);
        }
    }

    /**
     * Toggle Status Siswa (Menonaktifkan Siswa Keluar / Mengaktifkan Kembali)
     */
    public function toggleStatus(Student $student)
    {
        try {
            $newStatus = $student->status === 'active' ? 'inactive' : 'active';
            $student->update(['status' => $newStatus]);

            $label = $newStatus === 'active' ? 'diaktifkan kembali' : 'dinonaktifkan (keluar)';
            return back()->with('success', "Peserta didik \"{$student->name}\" berhasil {$label}.");
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal mengubah status peserta didik.',
            ]);
        }
    }

    /**
     * Hapus Data Peserta Didik (Soft Deletes)
     */
    public function destroy(Student $student)
    {
        try {
            $student->delete();

            return back()->with('success', 'Data peserta didik berhasil dihapus (soft delete).');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menghapus data peserta didik.',
            ]);
        }
    }

    /**
     * Unduh Format Template Import Excel (.xlsx)
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Siswa');

        // Header Columns
        $headers = [
            'A1' => 'Nama Siswa *',
            'B1' => 'No HP Orang Tua',
            'C1' => 'No HP Siswa',
            'D1' => 'Nama Kelompok Bimbel',
            'E1' => 'Status (Aktif / Nonaktif)',
            'F1' => 'Jenjang (SD / SMP / SMA)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style Header Row
        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F97316'], // Soft Modern Orange
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'EA580C'],
                ],
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Contoh Data Baris 2 & 3
        $sampleData = [
            ['Ahmad Fauzi', '081234567810', '085712345678', 'CYNDAQUIL', 'Aktif', 'SMA'],
            ['Siti Aisyah', '081234567811', '085712345679', '8D', 'Aktif', 'SMP'],
            ['Budi Santoso', '081234567812', '', '6A', 'Aktif', 'SD'],
            ['Nabila Zahra', '081234567813', '085712345680', '', 'Nonaktif', ''],
        ];

        $rowNum = 2;
        foreach ($sampleData as $row) {
            $sheet->setCellValue("A{$rowNum}", $row[0]);
            $sheet->setCellValueExplicit("B{$rowNum}", $row[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", $row[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowNum}", $row[3]);
            $sheet->setCellValue("E{$rowNum}", $row[4]);
            $sheet->setCellValue("F{$rowNum}", $row[5] ?? '');
            $rowNum++;
        }

        // Auto-fit column width
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Template_Import_Peserta_Didik.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Import Data Peserta Didik dari Berkas Excel / CSV
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'file'             => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required'             => 'Silakan pilih berkas Excel atau CSV yang akan diunggah.',
            'file.mimes'                => 'Format berkas harus berupa .xlsx, .xls, atau .csv.',
            'file.max'                  => 'Ukuran berkas maksimal 5MB.',
            'academic_year_id.required' => 'Tahun pelajaran wajib dipilih.',
        ]);

        try {
            DB::beginTransaction();

            $academicYearId = $request->input('academic_year_id');
            $file = $request->file('file');

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) <= 1) {
                return back()->withErrors(['error' => 'Berkas Excel kosong atau tidak memiliki baris data.']);
            }

            $importedCount = 0;
            $groupsCache = [];

            // Lewati baris 1 (Header)
            for ($i = 2; $i <= count($rows); $i++) {
                $row = $rows[$i] ?? null;
                if (!$row) continue;

                $name = trim($row['A'] ?? '');
                if (empty($name)) continue;

                $parentPhone = trim($row['B'] ?? '');
                $studentPhone = trim($row['C'] ?? '');
                $groupName = trim($row['D'] ?? '');
                $statusText = strtolower(trim($row['E'] ?? 'aktif'));
                $status = ($statusText === 'nonaktif' || $statusText === 'inactive' || $statusText === 'keluar') ? 'inactive' : 'active';

                // Otomatis mencari atau membuat kelompok bimbel jika kolom kelompok diisi
                $studyGroupId = null;
                if (!empty($groupName)) {
                    if (!isset($groupsCache[$groupName])) {
                        // Deteksi jenjang otomatis berdasarkan aturan:
                        // 5 - 6 = SD, 7 - 9 = SMP, Nama Karakter/Pokemon = SMA
                        $detectedLevel = 'SMA';
                        $trimmedGroup = trim($groupName);
                        if (preg_match('/^[5-6]/', $trimmedGroup)) {
                            $detectedLevel = 'SD';
                        } elseif (preg_match('/^[7-9]/', $trimmedGroup)) {
                            $detectedLevel = 'SMP';
                        }

                        // Jika berkas menyertakan kolom F untuk jenjang secara eksplisit
                        $explicitLevel = strtoupper(trim($row['F'] ?? ''));
                        if (in_array($explicitLevel, ['SD', 'SMP', 'SMA'], true)) {
                            $detectedLevel = $explicitLevel;
                        }

                        $group = StudyGroup::firstOrCreate(
                            [
                                'academic_year_id' => $academicYearId,
                                'name'             => $groupName,
                            ],
                            [
                                'education_level'  => $detectedLevel,
                                'is_active'        => true,
                            ]
                        );
                        $groupsCache[$groupName] = $group->id;
                    }
                    $studyGroupId = $groupsCache[$groupName];
                }

                Student::create([
                    'academic_year_id' => $academicYearId,
                    'study_group_id'   => $studyGroupId,
                    'name'             => $name,
                    'parent_phone'     => $parentPhone ?: null,
                    'student_phone'    => $studentPhone ?: null,
                    'status'           => $status,
                ]);

                $importedCount++;
            }

            DB::commit();

            return back()->with('success', "Sebanyak {$importedCount} data peserta didik berhasil diimpor dan dipetakan ke kelompok bimbel!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan saat memproses berkas Excel. Pastikan format kolom sesuai template.',
            ]);
        }
    }

    /**
     * Salin Data Peserta Didik dari Tahun Pelajaran Sebelumnya
     */
    public function copyFromPreviousYear(Request $request)
    {
        $validated = $request->validate([
            'source_year_id' => 'required|exists:academic_years,id',
            'target_year_id' => 'required|exists:academic_years,id|different:source_year_id',
        ], [
            'source_year_id.required'  => 'Pilih tahun pelajaran sumber.',
            'target_year_id.required'  => 'Pilih tahun pelajaran tujuan.',
            'target_year_id.different' => 'Tahun pelajaran tujuan harus berbeda dari tahun sumber.',
        ]);

        try {
            DB::beginTransaction();

            $sourceYearId = $validated['source_year_id'];
            $targetYearId = $validated['target_year_id'];

            $sourceStudents = Student::where('academic_year_id', $sourceYearId)
                ->where('status', 'active')
                ->get();

            if ($sourceStudents->isEmpty()) {
                return back()->withErrors([
                    'error' => 'Tidak ditemukan peserta didik aktif pada tahun pelajaran sumber yang dipilih.',
                ]);
            }

            $copiedCount = 0;

            foreach ($sourceStudents as $student) {
                // Cek apakah sudah ada siswa bernama sama di tahun tujuan
                $exists = Student::where('academic_year_id', $targetYearId)
                    ->where('name', $student->name)
                    ->exists();

                if (!$exists) {
                    Student::create([
                        'academic_year_id' => $targetYearId,
                        'study_group_id'   => null, // Kelompok diatur terpisah lewat mapping atau salin kelompok
                        'name'             => $student->name,
                        'parent_phone'     => $student->parent_phone,
                        'student_phone'    => $student->student_phone,
                        'photo'            => $student->photo,
                        'status'           => 'active',
                    ]);
                    $copiedCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Sebanyak {$copiedCount} data peserta didik berhasil disalin ke tahun pelajaran baru!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal menyalin data peserta didik dari tahun sebelumnya.',
            ]);
        }
    }

    /**
     * Generate Akun Login (Username & Password) untuk Peserta Didik
     */
    public function generateAccounts(Request $request)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        config(['hashing.bcrypt.rounds' => 10]);

        try {
            DB::beginTransaction();

            $academicYearId = $request->input('academic_year_id');
            $currentYear = null;
            if ($academicYearId) {
                $currentYear = AcademicYear::find($academicYearId);
            }
            if (!$currentYear) {
                $currentYear = AcademicYear::where('is_active', true)->first()
                    ?? AcademicYear::orderBy('created_at', 'desc')->first();
            }

            // Ekstrak 2 digit tahun pelajaran (default 26)
            $yearDigits = '26';
            if ($currentYear && preg_match('/(\d{2})(?:\D|$)/', $currentYear->name, $matches)) {
                $yearDigits = $matches[1];
            }
            // Prefix: tahun (2 digit) + nomor lembaga/bimbel '1' => '261'
            $prefix = $yearDigits . '1';

            // Ambil semua siswa pada tahun ajaran ini yang belum memiliki user_id atau username
            $query = Student::query();
            if ($currentYear) {
                $query->where('academic_year_id', $currentYear->id);
            }
            $studentsWithoutAccount = $query->where(function ($q) {
                $q->whereNull('user_id')->orWhereNull('username');
            })->orderBy('name', 'asc')->get();

            if ($studentsWithoutAccount->isEmpty()) {
                DB::rollBack();
                return back()->with('info', 'Semua peserta didik pada tahun ajaran ini sudah memiliki akun.');
            }

            // Preload existing usernames ke memory hash map untuk mencegah query berulang
            $existingUsernames = Student::where('username', 'like', "{$prefix}%")->pluck('username');
            $existingUserMap = User::where('username', 'like', "{$prefix}%")->pluck('username')->flip()->all();

            $maxSeq = 0;
            foreach ($existingUsernames as $u) {
                $seq = (int) substr($u, strlen($prefix));
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }

            $currentSeq = $maxSeq + 1;
            $generatedCount = 0;
            $tenantId = auth()->user()?->tenant_id;

            foreach ($studentsWithoutAccount as $student) {
                // Generate username: prefix + 3 digit nomor urut (001, 002, ...)
                $username = $prefix . str_pad((string) $currentSeq, 3, '0', STR_PAD_LEFT);
                $currentSeq++;

                // Pastikan tidak ada tabrakan di memory map (sangat cepat, 0 query DB)
                while (isset($existingUserMap[$username])) {
                    $username = $prefix . str_pad((string) $currentSeq, 3, '0', STR_PAD_LEFT);
                    $currentSeq++;
                }
                $existingUserMap[$username] = true;

                // Password 4 digit angka acak (contoh: 4829)
                $plainPassword = (string) mt_rand(1000, 9999);

                $user = User::create([
                    'tenant_id' => $student->tenant_id ?? $tenantId,
                    'name'      => $student->name,
                    'username'  => $username,
                    'email'     => null,
                    'password'  => $plainPassword, // User model 'password' => 'hashed' cast
                    'role'      => 'siswa',
                    'avatar'    => $student->photo,
                    'status'    => 'active',
                ]);

                $student->update([
                    'user_id'        => $user->id,
                    'username'       => $username,
                    'plain_password' => $plainPassword,
                ]);

                $generatedCount++;
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$generatedCount} akun peserta didik baru!");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('generateAccounts error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal men-generate akun peserta didik.',
            ]);
        }
    }

    /**
     * Reset Password Peserta Didik (Membuat 4 Digit Password Baru)
     */
    public function resetPassword(Request $request, Student $student)
    {
        try {
            DB::beginTransaction();

            $newPassword = (string) mt_rand(1000, 9999);

            if (!$student->user_id) {
                $yearDigits = '26';
                $prefix = $yearDigits . '1';
                $existingUsernames = Student::where('username', 'like', "{$prefix}%")->pluck('username');
                $maxSeq = 0;
                foreach ($existingUsernames as $u) {
                    $seq = (int) substr($u, strlen($prefix));
                    if ($seq > $maxSeq) {
                        $maxSeq = $seq;
                    }
                }
                $username = $prefix . str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
                while (User::where('username', $username)->exists()) {
                    $maxSeq++;
                    $username = $prefix . str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
                }

                $user = User::create([
                    'tenant_id' => $student->tenant_id ?? auth()->user()?->tenant_id,
                    'name'      => $student->name,
                    'username'  => $username,
                    'email'     => null,
                    'password'  => $newPassword,
                    'role'      => 'siswa',
                    'avatar'    => $student->photo,
                    'status'    => 'active',
                ]);

                $student->update([
                    'user_id'        => $user->id,
                    'username'       => $username,
                    'plain_password' => $newPassword,
                ]);
            } else {
                $user = User::find($student->user_id);
                if ($user) {
                    $user->update([
                        'name'     => $student->name,
                        'password' => $newPassword,
                        'avatar'   => $user->avatar ?: $student->photo,
                    ]);
                }
                $student->update([
                    'plain_password' => $newPassword,
                ]);
            }

            DB::commit();

            return back()->with('success', "Password untuk {$student->name} berhasil direset menjadi: {$newPassword}");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => config('app.debug') ? $e->getMessage() : 'Gagal mereset password peserta didik.',
            ]);
        }
    }

    /**
     * Unduh Rekap Akun Peserta Didik (Multi-Sheet: 1 Kelompok 1 Sheet)
     */
    public function exportAccounts(Request $request): StreamedResponse
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $academicYearId = $request->query('academic_year_id');
        $currentYear = null;
        if ($academicYearId) {
            $currentYear = AcademicYear::find($academicYearId);
        }
        if (!$currentYear) {
            $currentYear = AcademicYear::where('is_active', true)->first()
                ?? AcademicYear::orderBy('created_at', 'desc')->first();
        }

        // Ambil semua kelompok bimbel pada tahun ajaran ini
        $studyGroups = StudyGroup::where('academic_year_id', $currentYear?->id)
            ->orderBy('name', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $usedTitles = [];
        $sheetIndex = 0;

        // Function helper untuk styling sheet
        $styleSheet = function ($sheet, $title, $students, $groupName, $level) {
            // Header Judul
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'REKAP AKUN PESERTA DIDIK - ' . mb_strtoupper($groupName));
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EA580C'));
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:G2');
            $sheet->setCellValue('A2', 'Jenjang: ' . ($level ?: 'Umum') . ' | Tanggal Unduh: ' . date('d/m/Y H:i'));
            $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Table Header
            $headers = ['No', 'Nama Peserta Didik', 'Kelompok', 'Jenjang', 'Username', 'Password', 'Status'];
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

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
                        'horizontal' => in_array($col, ['A', 'C', 'D', 'E', 'F', 'G']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }

            // Data Rows
            $row = 5;
            $no = 1;
            foreach ($students as $s) {
                $sheet->getRowDimension($row)->setRowHeight(22);
                $isEven = ($no % 2 === 0);
                $rowBg = $isEven ? 'FFF7ED' : 'FFFFFF';

                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $s->name);
                $sheet->setCellValue('C' . $row, $groupName);
                $sheet->setCellValue('D' . $row, $level ?: '-');
                $sheet->setCellValueExplicit('E' . $row, (string) ($s->username ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F' . $row, (string) ($s->plain_password ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('G' . $row, $s->status === 'active' ? 'Aktif' : 'Nonaktif');

                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
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
                $sheet->getStyle("C{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$row}:F{$row}")->getFont()->setBold(true);

                $row++;
            }

            if ($students->isEmpty()) {
                $sheet->mergeCells("A5:G5");
                $sheet->setCellValue("A5", "Belum ada data peserta didik pada kelompok ini.");
                $sheet->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A5")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('94A3B8'));
                $sheet->getRowDimension(5)->setRowHeight(24);
            }

            // Auto-width
            foreach ($columns as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        };

        // Buat sheet per kelompok
        foreach ($studyGroups as $group) {
            $studentsInGroup = Student::where('study_group_id', $group->id)
                ->orderBy('name', 'asc')
                ->get();

            $sheet = ($sheetIndex === 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheetIndex++;

            $rawTitle = preg_replace('/[\\*:\\/\\\\?\\\[\\]]/', ' ', $group->name);
            $cleanTitle = trim(mb_substr($rawTitle, 0, 25));
            if ($cleanTitle === '') {
                $cleanTitle = 'Kelompok ' . $sheetIndex;
            }
            $finalTitle = $cleanTitle;
            $suffix = 1;
            while (in_array(mb_strtolower($finalTitle), $usedTitles)) {
                $suffix++;
                $finalTitle = mb_substr($cleanTitle, 0, 22) . ' ' . $suffix;
            }
            $usedTitles[] = mb_strtolower($finalTitle);

            $sheet->setTitle($finalTitle);
            $styleSheet($sheet, $finalTitle, $studentsInGroup, $group->name, $group->education_level);
        }

        // Siswa tanpa kelompok
        $unassignedStudents = Student::query();
        if ($currentYear) {
            $unassignedStudents->where('academic_year_id', $currentYear->id);
        }
        $unassigned = $unassignedStudents->whereNull('study_group_id')->orderBy('name', 'asc')->get();

        if ($unassigned->isNotEmpty() || $sheetIndex === 0) {
            $sheet = ($sheetIndex === 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheetTitle = 'Tanpa Kelompok';
            $sheet->setTitle($sheetTitle);
            $styleSheet($sheet, $sheetTitle, $unassigned, 'Tanpa Kelompok', '-');
            $sheetIndex++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Akun_Siswa_' . date('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
