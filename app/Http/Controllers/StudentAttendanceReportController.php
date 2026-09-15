<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAttendanceReportController extends Controller
{
    /**
     * Tampilkan Halaman Rekapitulasi Kehadiran Peserta Didik
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id;
        $filters = $this->resolveFilters($request);

        $reportData = $this->gatherReportData($tenantId, $filters);

        // Opsi untuk filter dropdown
        $studyGroups = StudyGroup::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'education_level']);

        $registeredSubjects = Subject::query()
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $sessionSubjects = AttendanceSession::query()
            ->distinct()
            ->pluck('subject_name')
            ->filter()
            ->toArray();

        $subjects = array_values(array_unique(array_merge($registeredSubjects, $sessionSubjects)));
        sort($subjects);

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        return Inertia::render('Report/StudentAttendance', [
            'filters'             => $filters,
            'kpi'                 => $reportData['kpi'],
            'students_recap'      => $reportData['students_recap'],
            'session_logs'        => $reportData['session_logs'],
            'study_groups'        => $studyGroups,
            'subjects'            => $subjects,
            'education_levels'    => ['SD', 'SMP', 'SMA'],
            'active_academic_year'=> $activeAcademicYear?->name,
        ]);
    }

    /**
     * Ekspor Laporan Rekapitulasi Kehadiran ke Format Excel (.xlsx)
     */
    public function export(Request $request): StreamedResponse
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $user = $request->user();
        $tenant = $user?->tenant;
        $tenantId = $user?->tenant_id;
        $filters = $this->resolveFilters($request);

        $reportData = $this->gatherReportData($tenantId, $filters);
        $kpi = $reportData['kpi'];
        $students = $reportData['students_recap'];
        $sessions = $reportData['session_logs'];

        $spreadsheet = new Spreadsheet();

        // -------------------------------------------------------------
        // SHEET 1: REKAPITULASI PESERTA DIDIK
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekap Kehadiran Siswa');

        // Header Dokumen Bimbel
        $sheet1->mergeCells('A1:J1');
        $sheet1->setCellValue('A1', strtoupper($tenant ? $tenant->name : 'BIMBEL MULTI-TENANT'));
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('EA580C'));
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet1->mergeCells('A2:J2');
        $sheet1->setCellValue('A2', 'LAPORAN REKAPITULASI KEHADIRAN PESERTA DIDIK');
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Keterangan Filter Terpilih
        $periodeText = $this->getPeriodLabel($filters);
        $sheet1->mergeCells('A3:J3');
        $sheet1->setCellValue('A3', "Periode: {$periodeText} | Jenjang: " . ($filters['education_level'] === 'all' ? 'Semua Jenjang' : $filters['education_level']) . " | Mapel: " . ($filters['subject_name'] === 'all' ? 'Semua Mapel' : $filters['subject_name']) . " | Dicetak: " . date('d/m/Y H:i'));
        $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));

        // Box KPI Summary di dalam Excel (Baris 5-6)
        $this->renderKpiSummaryInExcel($sheet1, 5, $kpi);

        // Table Header Siswa (Baris 8)
        $studentHeaders = [
            'No',
            'NIS / Username',
            'Nama Lengkap Peserta Didik',
            'Jenjang',
            'Kelompok Bimbel',
            'Total Pertemuan',
            'Hadir',
            'Tidak Hadir',
            '% Kehadiran',
            'Status Evaluasi'
        ];
        $studentCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $headerRow = 8;
        $sheet1->getRowDimension($headerRow)->setRowHeight(26);
        foreach ($studentHeaders as $idx => $header) {
            $col = $studentCols[$idx];
            $cell = $col . $headerRow;
            $sheet1->setCellValue($cell, $header);
            $sheet1->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F172A'], // Dark Navy Slate
                ],
                'alignment' => [
                    'horizontal' => in_array($col, ['A', 'B', 'D', 'F', 'G', 'H', 'I', 'J']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Data Rows Siswa
        $row = 9;
        $no = 1;
        foreach ($students as $s) {
            $sheet1->getRowDimension($row)->setRowHeight(21);
            $isEven = ($no % 2 === 0);
            $rowBg = $isEven ? 'F8FAFC' : 'FFFFFF';

            $sheet1->setCellValue('A' . $row, $no++);
            $sheet1->setCellValueExplicit('B' . $row, (string) ($s['username'] ?? '-'), DataType::TYPE_STRING);
            $sheet1->setCellValue('C' . $row, $s['name']);
            $sheet1->setCellValue('D' . $row, $s['education_level']);
            $sheet1->setCellValue('E' . $row, $s['study_group_name']);
            $sheet1->setCellValue('F' . $row, $s['total_sessions']);
            $sheet1->setCellValue('G' . $row, $s['present_count']);
            $sheet1->setCellValue('H' . $row, $s['absent_count']);
            $sheet1->setCellValue('I' . $row, $s['attendance_rate'] / 100);
            $sheet1->setCellValue('J' . $row, $s['status_label']);

            // Styling baris
            $sheet1->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rowBg],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("F{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format Percentage
            $sheet1->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

            // Text Color untuk Status
            if ($s['status_label'] === 'Sangat Disiplin') {
                $sheet1->getStyle("J{$row}")->getFont()->setBold(true)->setColor(new Color('15803D'));
            } elseif ($s['status_label'] === 'Cukup Disiplin') {
                $sheet1->getStyle("J{$row}")->getFont()->setBold(true)->setColor(new Color('B45309'));
            } elseif ($s['status_label'] === 'Perlu Evaluasi') {
                $sheet1->getStyle("J{$row}")->getFont()->setBold(true)->setColor(new Color('DC2626'));
            }

            $row++;
        }

        if (empty($students)) {
            $sheet1->mergeCells("A9:J9");
            $sheet1->setCellValue("A9", "Tidak ada data kehadiran peserta didik pada filter ini.");
            $sheet1->getStyle("A9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("A9")->getFont()->setItalic(true)->setColor(new Color('94A3B8'));
            $sheet1->getRowDimension(9)->setRowHeight(24);
            $row = 10;
        } else {
            // Summary Total Row di bawah tabel
            $lastDataRow = $row - 1;
            $sheet1->getRowDimension($row)->setRowHeight(24);
            $sheet1->mergeCells("A{$row}:E{$row}");
            $sheet1->setCellValue("A{$row}", "TOTAL & RATA-RATA KESELURUHAN");
            $sheet1->setCellValue("F{$row}", "=SUM(F9:F{$lastDataRow})");
            $sheet1->setCellValue("G{$row}", "=SUM(G9:G{$lastDataRow})");
            $sheet1->setCellValue("H{$row}", "=SUM(H9:H{$lastDataRow})");
            $sheet1->setCellValue("I{$row}", "=AVERAGE(I9:I{$lastDataRow})");
            $sheet1->setCellValue("J{$row}", "-");

            $sheet1->getStyle("A{$row}:J{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '0F172A']],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle("F{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
        }

        foreach ($studentCols as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->freezePane('A9');

        // -------------------------------------------------------------
        // SHEET 2: LOG RIWAYAT SESI PERTEMUAN KELAS
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Riwayat Sesi Pertemuan');

        // Header Sheet 2
        $sheet2->mergeCells('A1:J1');
        $sheet2->setCellValue('A1', 'LOG RIWAYAT SESI PERTEMUAN & JURNAL KELAS');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));

        $sheet2->mergeCells('A2:J2');
        $sheet2->setCellValue('A2', "Periode: {$periodeText} | Filter: Jenjang ({$filters['education_level']}), Mapel ({$filters['subject_name']})");
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));

        $sessionHeaders = [
            'No',
            'Tanggal Sesi',
            'Jenjang',
            'Kelompok Bimbel',
            'Mata Pelajaran',
            'Tentor Pengampu',
            'Materi / Topik Pembelajaran',
            'Jumlah Hadir',
            'Jumlah Tidak Hadir',
            'Tingkat Kehadiran (%)'
        ];
        $sessionCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $sHeaderRow = 4;
        $sheet2->getRowDimension($sHeaderRow)->setRowHeight(26);
        foreach ($sessionHeaders as $idx => $header) {
            $col = $sessionCols[$idx];
            $cell = $col . $sHeaderRow;
            $sheet2->setCellValue($cell, $header);
            $sheet2->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EA580C'], // Bimbel Brand Orange
                ],
                'alignment' => [
                    'horizontal' => in_array($col, ['A', 'B', 'C', 'H', 'I', 'J']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $sRow = 5;
        $sNo = 1;
        foreach ($sessions as $sess) {
            $sheet2->getRowDimension($sRow)->setRowHeight(21);
            $isEven = ($sNo % 2 === 0);
            $rowBg = $isEven ? 'FFF7ED' : 'FFFFFF';

            $totalAtt = $sess['present_count'] + $sess['absent_count'];
            $rate = $totalAtt > 0 ? ($sess['present_count'] / $totalAtt) : 0;

            $sheet2->setCellValue('A' . $sRow, $sNo++);
            $sheet2->setCellValue('B' . $sRow, $sess['formatted_date']);
            $sheet2->setCellValue('C' . $sRow, $sess['education_level']);
            $sheet2->setCellValue('D' . $sRow, $sess['study_group_name']);
            $sheet2->setCellValue('E' . $sRow, $sess['subject_name']);
            $sheet2->setCellValue('F' . $sRow, $sess['tentor_name']);
            $sheet2->setCellValue('G' . $sRow, $sess['topic_description']);
            $sheet2->setCellValue('H' . $sRow, $sess['present_count']);
            $sheet2->setCellValue('I' . $sRow, $sess['absent_count']);
            $sheet2->setCellValue('J' . $sRow, $rate);

            $sheet2->getStyle("A{$sRow}:J{$sRow}")->applyFromArray([
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
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet2->getStyle("A{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("B{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("C{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("H{$sRow}:J{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("J{$sRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

            $sRow++;
        }

        if (empty($sessions)) {
            $sheet2->mergeCells("A5:J5");
            $sheet2->setCellValue("A5", "Belum ada riwayat sesi pertemuan pada filter ini.");
            $sheet2->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("A5")->getFont()->setItalic(true)->setColor(new Color('94A3B8'));
            $sheet2->getRowDimension(5)->setRowHeight(24);
        }

        foreach ($sessionCols as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet2->freezePane('A5');

        // Set active kembali ke Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Kehadiran_Siswa_' . $filters['period'] . '_' . date('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Resolusi parameter filter request
     */
    private function resolveFilters(Request $request): array
    {
        $period = $request->input('period', 'monthly');
        $educationLevel = $request->input('education_level', 'all');
        $studyGroupId = $request->input('study_group_id', 'all');
        $subjectName = $request->input('subject_name', 'all');
        $search = trim($request->input('search', ''));

        $today = Carbon::today();
        $date = $request->input('date', $today->format('Y-m-d'));
        $month = $request->input('month', $today->format('Y-m'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Hitung batas rentang tanggal efektif berdasarkan periode
        switch ($period) {
            case 'daily':
                $effectiveStart = Carbon::parse($date)->startOfDay();
                $effectiveEnd   = Carbon::parse($date)->endOfDay();
                break;
            case 'weekly':
                $effectiveStart = Carbon::now()->subDays(6)->startOfDay();
                $effectiveEnd   = Carbon::now()->endOfDay();
                break;
            case 'all_time':
                $effectiveStart = null;
                $effectiveEnd   = null;
                break;
            case 'custom':
                $effectiveStart = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
                $effectiveEnd   = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
                break;
            case 'monthly':
            default:
                $period = 'monthly';
                $parsedMonth = Carbon::createFromFormat('Y-m', $month ?: $today->format('Y-m'));
                $effectiveStart = $parsedMonth->copy()->startOfMonth();
                $effectiveEnd   = $parsedMonth->copy()->endOfMonth();
                break;
        }

        return [
            'period'           => $period,
            'education_level'  => $educationLevel,
            'study_group_id'   => $studyGroupId,
            'subject_name'     => $subjectName,
            'search'           => $search,
            'date'             => $date,
            'month'            => $month,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'effective_start'  => $effectiveStart,
            'effective_end'    => $effectiveEnd,
        ];
    }

    /**
     * Kumpulkan data agregasi rekapitulasi kehadiran
     */
    private function gatherReportData(?string $tenantId, array $filters): array
    {
        // 1. Query Sesi Pertemuan Kelas
        $sessionQuery = AttendanceSession::query()
            ->with(['studyGroup', 'tentor', 'attendances'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($filters['effective_start'] && $filters['effective_end']) {
            $startDate = $filters['effective_start']->format('Y-m-d');
            $endDate = $filters['effective_end']->format('Y-m-d');
            if ($startDate === $endDate) {
                $sessionQuery->whereDate('date', $startDate);
            } else {
                $sessionQuery->whereDate('date', '>=', $startDate)
                             ->whereDate('date', '<=', $endDate);
            }
        }

        if ($filters['subject_name'] !== 'all') {
            $sessionQuery->where('subject_name', $filters['subject_name']);
        }

        if ($filters['study_group_id'] !== 'all') {
            $sessionQuery->where('study_group_id', $filters['study_group_id']);
        }

        if ($filters['education_level'] !== 'all') {
            $sessionQuery->whereHas('studyGroup', function ($q) use ($filters) {
                $q->where('education_level', $filters['education_level']);
            });
        }

        $sessions = $sessionQuery->get();
        $sessionIds = $sessions->pluck('id')->toArray();

        // 2. Format Sesi Logs untuk Tab Riwayat
        $sessionLogs = $sessions->map(function ($s) {
            $presentCount = $s->attendances->where('status', 'present')->count();
            $absentCount = $s->attendances->where('status', 'absent')->count();
            $totalCount = $presentCount + $absentCount;

            return [
                'id'                => $s->id,
                'date'              => $s->date->format('Y-m-d'),
                'formatted_date'    => $s->date->translatedFormat('d M Y'),
                'study_group_name'  => $s->studyGroup?->name ?? '-',
                'education_level'   => $s->studyGroup?->education_level ?? '-',
                'subject_name'      => $s->subject_name,
                'tentor_name'       => $s->tentor?->full_name ?? 'Tentor Bimbel',
                'topic_description' => $s->topic_description,
                'photo_url'         => $s->photo_url,
                'present_count'     => $presentCount,
                'absent_count'      => $absentCount,
                'total_count'       => $totalCount,
                'attendance_rate'   => $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 0,
            ];
        })->values()->all();

        // 3. Query Data Siswa Aktif
        $studentQuery = Student::query()
            ->where('status', 'active')
            ->with(['studyGroup', 'user'])
            ->orderBy('name', 'asc');

        if ($filters['study_group_id'] !== 'all') {
            $studentQuery->where('study_group_id', $filters['study_group_id']);
        }

        if ($filters['education_level'] !== 'all') {
            $studentQuery->whereHas('studyGroup', function ($q) use ($filters) {
                $q->where('education_level', $filters['education_level']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $studentQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%");
                  });
            });
        }

        $students = $studentQuery->get();

        // Ambil semua records attendance pada sesi yang terfilter
        $attendancesGrouped = Attendance::query()
            ->whereIn('attendance_session_id', $sessionIds)
            ->with('attendanceSession')
            ->get()
            ->groupBy('student_id');

        // Hitung total sesi per study_group_id untuk menentukan kuota sesi siswa
        $sessionsCountByGroup = $sessions->groupBy('study_group_id')->map->count();

        $studentsRecap = [];
        $totalPresentOverall = 0;
        $totalAbsentOverall = 0;

        foreach ($students as $student) {
            $studentAttendances = $attendancesGrouped->get($student->id, collect());
            
            $presentCount = $studentAttendances->where('status', 'present')->count();
            $absentCount = $studentAttendances->where('status', 'absent')->count();

            // Total sesi kelas yang relevan untuk siswa ini
            $totalGroupSessions = $sessionsCountByGroup->get($student->study_group_id, 0);

            // Jika sesi spesifik tercatat di tabel attendance
            $totalRecorded = $presentCount + $absentCount;
            $effectiveTotal = max($totalGroupSessions, $totalRecorded);

            $rate = $effectiveTotal > 0 ? round(($presentCount / $effectiveTotal) * 100, 1) : 0;

            if ($effectiveTotal === 0) {
                $statusLabel = 'Belum Ada Sesi';
            } elseif ($rate >= 90) {
                $statusLabel = 'Sangat Disiplin';
            } elseif ($rate >= 75) {
                $statusLabel = 'Cukup Disiplin';
            } else {
                $statusLabel = 'Perlu Evaluasi';
            }

            $totalPresentOverall += $presentCount;
            $totalAbsentOverall += $absentCount;

            // Detail riwayat presensi siswa
            $details = $studentAttendances->map(function ($att) {
                return [
                    'session_id'     => $att->attendance_session_id,
                    'date'           => $att->attendanceSession?->date?->format('Y-m-d'),
                    'formatted_date' => $att->attendanceSession?->date?->translatedFormat('d M Y'),
                    'subject_name'   => $att->attendanceSession?->subject_name ?? '-',
                    'topic'          => $att->attendanceSession?->topic_description ?? '-',
                    'status'         => $att->status,
                    'notes'          => $att->notes,
                ];
            })->sortByDesc('date')->values()->all();

            $studentsRecap[] = [
                'id'                 => $student->id,
                'name'               => $student->name,
                'nis'                => $student->nis,
                'username'           => $student->user?->username ?? $student->nis ?? '-',
                'study_group_id'     => $student->study_group_id,
                'study_group_name'   => $student->studyGroup?->name ?? 'Belum Ditentukan',
                'education_level'    => $student->studyGroup?->education_level ?? '-',
                'total_sessions'     => $effectiveTotal,
                'present_count'      => $presentCount,
                'absent_count'       => $absentCount,
                'attendance_rate'    => $rate,
                'status_label'       => $statusLabel,
                'attendance_details' => $details,
            ];
        }

        // 4. Hitung Metrik KPI Keseluruhan
        $totalAllAttendances = $totalPresentOverall + $totalAbsentOverall;
        $overallAttendanceRate = $totalAllAttendances > 0 ? round(($totalPresentOverall / $totalAllAttendances) * 100, 1) : 0;

        $kpi = [
            'total_sessions'          => $sessions->count(),
            'total_present'           => $totalPresentOverall,
            'total_absent'            => $totalAbsentOverall,
            'overall_attendance_rate' => $overallAttendanceRate,
            'total_students'          => count($studentsRecap),
        ];

        return [
            'kpi'            => $kpi,
            'students_recap' => $studentsRecap,
            'session_logs'   => $sessionLogs,
        ];
    }

    /**
     * Label Deskripsi Periode Waktu
     */
    private function getPeriodLabel(array $filters): string
    {
        switch ($filters['period']) {
            case 'daily':
                return 'Harian (' . Carbon::parse($filters['date'])->translatedFormat('d F Y') . ')';
            case 'weekly':
                return 'Mingguan (7 Hari Terakhir)';
            case 'all_time':
                return 'Selamanya (Seluruh Data Histori)';
            case 'custom':
                $start = $filters['start_date'] ? Carbon::parse($filters['start_date'])->translatedFormat('d M Y') : 'Awal';
                $end = $filters['end_date'] ? Carbon::parse($filters['end_date'])->translatedFormat('d M Y') : 'Sekarang';
                return "Kustom ({$start} s/d {$end})";
            case 'monthly':
            default:
                return 'Bulanan (' . Carbon::createFromFormat('Y-m', $filters['month'])->translatedFormat('F Y') . ')';
        }
    }

    /**
     * Render KPI visual blocks ke sheet Excel
     */
    private function renderKpiSummaryInExcel($sheet, int $startRow, array $kpi): void
    {
        // Kotak 1: Total Sesi
        $sheet->setCellValue('A' . $startRow, 'TOTAL SESI KELAS');
        $sheet->setCellValue('A' . ($startRow + 1), $kpi['total_sessions'] . ' Sesi');
        $sheet->mergeCells("A{$startRow}:B{$startRow}");
        $sheet->mergeCells("A" . ($startRow + 1) . ":B" . ($startRow + 1));

        // Kotak 2: Total Hadir
        $sheet->setCellValue('D' . $startRow, 'TOTAL KEHADIRAN (HADIR)');
        $sheet->setCellValue('D' . ($startRow + 1), $kpi['total_present'] . ' Kehadiran');
        $sheet->mergeCells("D{$startRow}:E{$startRow}");
        $sheet->mergeCells("D" . ($startRow + 1) . ":E" . ($startRow + 1));

        // Kotak 3: Total Tidak Hadir
        $sheet->setCellValue('G' . $startRow, 'TOTAL TIDAK HADIR');
        $sheet->setCellValue('G' . ($startRow + 1), $kpi['total_absent'] . ' Tidak Hadir');
        $sheet->mergeCells("G{$startRow}:H{$startRow}");
        $sheet->mergeCells("G" . ($startRow + 1) . ":H" . ($startRow + 1));

        // Kotak 4: Rata-rata Kehadiran
        $sheet->setCellValue('I' . $startRow, 'RATA-RATA KEHADIRAN');
        $sheet->setCellValue('I' . ($startRow + 1), $kpi['overall_attendance_rate'] . '%');
        $sheet->mergeCells("I{$startRow}:J{$startRow}");
        $sheet->mergeCells("I" . ($startRow + 1) . ":J" . ($startRow + 1));

        // Styling Box KPI
        $boxes = [
            ['cols' => "A{$startRow}:B" . ($startRow + 1), 'bg' => 'F8FAFC', 'border' => 'CBD5E1', 'valColor' => '0F172A'],
            ['cols' => "D{$startRow}:E" . ($startRow + 1), 'bg' => 'F0FDF4', 'border' => '86EFAC', 'valColor' => '15803D'],
            ['cols' => "G{$startRow}:H" . ($startRow + 1), 'bg' => 'FEF2F2', 'border' => 'FECACA', 'valColor' => 'B91C1C'],
            ['cols' => "I{$startRow}:J" . ($startRow + 1), 'bg' => 'FFF7ED', 'border' => 'FED7AA', 'valColor' => 'C2410C'],
        ];

        foreach ($boxes as $box) {
            $sheet->getStyle($box['cols'])->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $box['bg']],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $box['border']],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Header font styling
        $sheet->getStyle("A{$startRow}")->getFont()->setSize(8)->setBold(true)->setColor(new Color('64748B'));
        $sheet->getStyle("D{$startRow}")->getFont()->setSize(8)->setBold(true)->setColor(new Color('15803D'));
        $sheet->getStyle("G{$startRow}")->getFont()->setSize(8)->setBold(true)->setColor(new Color('B91C1C'));
        $sheet->getStyle("I{$startRow}")->getFont()->setSize(8)->setBold(true)->setColor(new Color('C2410C'));

        // Value font styling
        $valRow = $startRow + 1;
        $sheet->getStyle("A{$valRow}")->getFont()->setSize(12)->setBold(true)->setColor(new Color('0F172A'));
        $sheet->getStyle("D{$valRow}")->getFont()->setSize(12)->setBold(true)->setColor(new Color('15803D'));
        $sheet->getStyle("G{$valRow}")->getFont()->setSize(12)->setBold(true)->setColor(new Color('B91C1C'));
        $sheet->getStyle("I{$valRow}")->getFont()->setSize(12)->setBold(true)->setColor(new Color('C2410C'));

        $sheet->getRowDimension($startRow)->setRowHeight(16);
        $sheet->getRowDimension($valRow)->setRowHeight(22);
    }
}
