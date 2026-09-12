<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Tentor;
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

class TutorAttendanceReportController extends Controller
{
    /**
     * Tampilkan Halaman Rekapitulasi Kehadiran Guru (Tutor)
     * Kehadiran guru otomatis terhitung saat guru melakukan absensi siswa pada suatu sesi pertemuan.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id;
        $filters = $this->resolveFilters($request);

        $reportData = $this->gatherReportData($tenantId, $filters);

        // Opsi mata pelajaran & spesialisasi untuk filter dropdown
        $registeredSubjects = Subject::query()
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $sessionSubjects = AttendanceSession::query()
            ->distinct()
            ->pluck('subject_name')
            ->filter()
            ->toArray();

        $tentorSpecializations = Tentor::query()
            ->whereNotNull('specialization')
            ->distinct()
            ->pluck('specialization')
            ->filter()
            ->toArray();

        $subjects = array_values(array_unique(array_merge($registeredSubjects, $sessionSubjects, $tentorSpecializations)));
        sort($subjects);

        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        return Inertia::render('Report/TutorAttendance', [
            'filters'              => $filters,
            'kpi'                  => $reportData['kpi'],
            'tentors_recap'        => $reportData['tentors_recap'],
            'session_logs'         => $reportData['session_logs'],
            'subjects'             => $subjects,
            'education_levels'     => ['SD', 'SMP', 'SMA'],
            'active_academic_year' => $activeAcademicYear?->name,
        ]);
    }

    /**
     * Ekspor Laporan Rekapitulasi Kehadiran Guru ke Format Excel (.xlsx)
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
        $tentors = $reportData['tentors_recap'];
        $sessions = $reportData['session_logs'];

        $spreadsheet = new Spreadsheet();

        // -------------------------------------------------------------
        // SHEET 1: REKAPITULASI KEHADIRAN GURU
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekap Kehadiran Guru');

        // Header Dokumen Bimbel
        $sheet1->mergeCells('A1:H1');
        $sheet1->setCellValue('A1', strtoupper($tenant ? $tenant->name : 'BIMBEL MULTI-TENANT'));
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('EA580C'));
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet1->mergeCells('A2:H2');
        $sheet1->setCellValue('A2', 'LAPORAN REKAPITULASI KEHADIRAN GURU / TENTOR');
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Keterangan Filter Terpilih
        $periodeText = $this->getPeriodLabel($filters);
        $sheet1->mergeCells('A3:H3');
        $sheet1->setCellValue('A3', "Periode: {$periodeText} | Jenjang: " . ($filters['education_level'] === 'all' ? 'Semua Jenjang' : $filters['education_level']) . " | Mapel: " . ($filters['subject_name'] === 'all' ? 'Semua Mapel' : $filters['subject_name']) . " | Dicetak: " . date('d/m/Y H:i'));
        $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));

        // Box KPI Summary di dalam Excel (Baris 5-6)
        $this->renderKpiSummaryInExcel($sheet1, 5, $kpi);

        // Table Header Guru (Baris 8)
        $headers = [
            'No',
            'Nama Lengkap Guru / Tentor',
            'Bidang / Spesialisasi',
            'Total Hadir Mengajar (Sesi)',
            'Total Siswa Diajar',
            'Rata-rata Kehadiran Kelas (%)',
            'Terakhir Mengajar',
            'Status Keaktifan'
        ];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        $headerRow = 8;
        $sheet1->getRowDimension($headerRow)->setRowHeight(26);
        foreach ($headers as $idx => $header) {
            $col = $cols[$idx];
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
                    'startColor' => ['rgb' => 'EA580C'], // Brand Orange
                ],
                'alignment' => [
                    'horizontal' => in_array($col, ['A', 'D', 'E', 'F', 'G', 'H']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $row = 9;
        $no = 1;
        foreach ($tentors as $t) {
            $sheet1->getRowDimension($row)->setRowHeight(22);
            $isEven = ($no % 2 === 0);
            $rowBg = $isEven ? 'FFF7ED' : 'FFFFFF';

            $sheet1->setCellValue('A' . $row, $no++);
            $sheet1->setCellValue('B' . $row, $t['full_name']);
            $sheet1->setCellValue('C' . $row, $t['specialization'] ?: '-');
            $sheet1->setCellValue('D' . $row, $t['total_sessions']);
            $sheet1->setCellValue('E' . $row, $t['total_students_taught']);
            $sheet1->setCellValue('F' . $row, ($t['avg_class_attendance_rate'] / 100));
            $sheet1->setCellValue('G' . $row, $t['last_session_date_formatted'] ?: 'Belum Ada');
            $sheet1->setCellValue('H' . $row, $t['status_label']);

            $sheet1->getStyle("A{$row}:H{$row}")->applyFromArray([
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

            $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("D{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("F{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

            $row++;
        }

        if (empty($tentors)) {
            $sheet1->mergeCells("A9:H9");
            $sheet1->setCellValue("A9", "Tidak ada data guru/tentor pada filter ini.");
            $sheet1->getStyle("A9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("A9")->getFont()->setItalic(true)->setColor(new Color('94A3B8'));
            $sheet1->getRowDimension(9)->setRowHeight(24);
        }

        foreach ($cols as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->freezePane('A9');

        // -------------------------------------------------------------
        // SHEET 2: LOG RIWAYAT SESI MENGAJAR GURU
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Log Sesi Mengajar');

        // Header Sheet 2
        $sheet2->mergeCells('A1:J1');
        $sheet2->setCellValue('A1', 'LOG RIWAYAT SESI MENGAJAR & PRESENSI KELAS OLEH GURU');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));

        $sheet2->mergeCells('A2:J2');
        $sheet2->setCellValue('A2', "Periode: {$periodeText} | Filter: Jenjang ({$filters['education_level']}), Mapel ({$filters['subject_name']})");
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));

        $sessionHeaders = [
            'No',
            'Tanggal Sesi',
            'Jam',
            'Nama Guru / Tentor',
            'Mata Pelajaran',
            'Kelompok Bimbel',
            'Jenjang',
            'Topik / Materi Pembelajaran',
            'Jumlah Siswa Hadir',
            'Jumlah Siswa Alpa',
            'Total Siswa',
            'Kehadiran Siswa (%)'
        ];
        $sCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

        $sHeaderRow = 4;
        $sheet2->getRowDimension($sHeaderRow)->setRowHeight(26);
        foreach ($sessionHeaders as $idx => $header) {
            $col = $sCols[$idx];
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
                    'startColor' => ['rgb' => 'EA580C'],
                ],
                'alignment' => [
                    'horizontal' => in_array($col, ['A', 'B', 'C', 'G', 'I', 'J', 'K', 'L']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
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
            $sheet2->setCellValue('C' . $sRow, $sess['time'] ?: '-');
            $sheet2->setCellValue('D' . $sRow, $sess['tentor_name']);
            $sheet2->setCellValue('E' . $sRow, $sess['subject_name']);
            $sheet2->setCellValue('F' . $sRow, $sess['study_group_name']);
            $sheet2->setCellValue('G' . $sRow, $sess['education_level']);
            $sheet2->setCellValue('H' . $sRow, $sess['topic_description'] ?: '-');
            $sheet2->setCellValue('I' . $sRow, $sess['present_count']);
            $sheet2->setCellValue('J' . $sRow, $sess['absent_count']);
            $sheet2->setCellValue('K' . $sRow, $totalAtt);
            $sheet2->setCellValue('L' . $sRow, $rate);

            $sheet2->getStyle("A{$sRow}:L{$sRow}")->applyFromArray([
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

            $sheet2->getStyle("A{$sRow}:C{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("G{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("I{$sRow}:L{$sRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("L{$sRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

            $sRow++;
        }

        if (empty($sessions)) {
            $sheet2->mergeCells("A5:L5");
            $sheet2->setCellValue("A5", "Belum ada riwayat sesi mengajar pada filter ini.");
            $sheet2->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("A5")->getFont()->setItalic(true)->setColor(new Color('94A3B8'));
            $sheet2->getRowDimension(5)->setRowHeight(24);
        }

        foreach ($sCols as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet2->freezePane('A5');

        // Set active kembali ke Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Kehadiran_Guru_' . $filters['period'] . '_' . date('Ymd_His') . '.xlsx';

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
        $subjectName = $request->input('subject_name', 'all');
        $search = trim($request->input('search', ''));

        $today = Carbon::today();
        $date = $request->input('date', $today->format('Y-m-d'));
        $month = $request->input('month', $today->format('Y-m'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Hitung batas rentang tanggal efektif
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
     * Kumpulkan data agregasi rekapitulasi kehadiran guru
     */
    private function gatherReportData(?string $tenantId, array $filters): array
    {
        // 1. Query Sesi Pertemuan Kelas yang diajar
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
            $sessionQuery->where(function ($q) use ($filters) {
                $q->where('subject_name', $filters['subject_name'])
                  ->orWhereHas('tentor', function ($tq) use ($filters) {
                      $tq->where('specialization', $filters['subject_name']);
                  });
            });
        }

        if ($filters['education_level'] !== 'all') {
            $sessionQuery->whereHas('studyGroup', function ($q) use ($filters) {
                $q->where('education_level', $filters['education_level']);
            });
        }

        $sessions = $sessionQuery->get();

        // 2. Format Sesi Logs untuk Tab Riwayat
        $sessionLogs = $sessions->map(function ($s) {
            $presentCount = $s->attendances->where('status', 'present')->count();
            $absentCount = $s->attendances->where('status', 'absent')->count();
            $totalCount = $presentCount + $absentCount;

            return [
                'id'                => $s->id,
                'date'              => $s->date->format('Y-m-d'),
                'formatted_date'    => $s->date->translatedFormat('d M Y'),
                'time'              => $s->created_at ? $s->created_at->format('H:i') : '-',
                'tentor_id'         => $s->tentor_id,
                'tentor_name'       => $s->tentor?->full_name ?? ($s->tentor?->name ?? 'Tentor Bimbel'),
                'specialization'    => $s->tentor?->specialization ?? '-',
                'study_group_name'  => $s->studyGroup?->name ?? '-',
                'education_level'   => $s->studyGroup?->education_level ?? '-',
                'subject_name'      => $s->subject_name ?: '-',
                'topic_description' => $s->topic_description ?: '-',
                'photo_url'         => $s->photo_url,
                'present_count'     => $presentCount,
                'absent_count'      => $absentCount,
                'total_count'       => $totalCount,
                'attendance_rate'   => $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 0,
            ];
        })->values()->all();

        // 3. Query Data Guru Aktif
        $tentorQuery = Tentor::query()
            ->where('status', 'active')
            ->with(['user'])
            ->orderBy('name', 'asc');

        if ($filters['subject_name'] !== 'all') {
            $tentorQuery->where(function ($q) use ($filters) {
                $q->where('specialization', $filters['subject_name'])
                  ->orWhereHas('attendanceSessions', function ($sq) use ($filters) {
                      $sq->where('subject_name', $filters['subject_name']);
                  });
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $tentorQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $tentors = $tentorQuery->get();

        // Kelompokkan sesi berdasarkan tentor_id
        $sessionsByTentor = $sessions->groupBy('tentor_id');

        $tentorsRecap = [];
        $totalSessionsConducted = 0;
        $totalStudentsServed = 0;
        $activeTeachingTentorsCount = 0;

        foreach ($tentors as $tentor) {
            $tentorSessions = $sessionsByTentor->get($tentor->id, collect());
            $sessionsCount = $tentorSessions->count();

            $studentsTaughtInSessions = 0;
            $presentStudentsInSessions = 0;
            $latestDate = null;
            $latestFormatted = null;

            $sessionHistory = [];

            foreach ($tentorSessions as $ts) {
                $pCount = $ts->attendances->where('status', 'present')->count();
                $aCount = $ts->attendances->where('status', 'absent')->count();
                $tCount = $pCount + $aCount;

                $studentsTaughtInSessions += $tCount;
                $presentStudentsInSessions += $pCount;

                if (!$latestDate || $ts->date->gt($latestDate)) {
                    $latestDate = $ts->date;
                    $latestFormatted = $ts->date->translatedFormat('d M Y');
                }

                $sessionHistory[] = [
                    'id'                => $ts->id,
                    'date'              => $ts->date->format('Y-m-d'),
                    'formatted_date'    => $ts->date->translatedFormat('d M Y'),
                    'time'              => $ts->created_at ? $ts->created_at->format('H:i') : '-',
                    'subject_name'      => $ts->subject_name,
                    'study_group_name'  => $ts->studyGroup?->name ?? '-',
                    'education_level'   => $ts->studyGroup?->education_level ?? '-',
                    'topic_description' => $ts->topic_description,
                    'photo_url'         => $ts->photo_url,
                    'present_count'     => $pCount,
                    'absent_count'      => $aCount,
                    'total_count'       => $tCount,
                    'attendance_rate'   => $tCount > 0 ? round(($pCount / $tCount) * 100, 1) : 0,
                ];
            }

            if ($sessionsCount > 0) {
                $activeTeachingTentorsCount++;
            }

            $totalSessionsConducted += $sessionsCount;
            $totalStudentsServed += $studentsTaughtInSessions;

            $avgRate = $studentsTaughtInSessions > 0
                ? round(($presentStudentsInSessions / $studentsTaughtInSessions) * 100, 1)
                : 0;

            if ($sessionsCount >= 8) {
                $statusLabel = 'Sangat Aktif';
            } elseif ($sessionsCount >= 4) {
                $statusLabel = 'Aktif Mengajar';
            } elseif ($sessionsCount > 0) {
                $statusLabel = 'Cukup Aktif';
            } else {
                $statusLabel = 'Belum Ada Sesi';
            }

            $tentorsRecap[] = [
                'id'                         => $tentor->id,
                'name'                       => $tentor->name,
                'full_name'                  => $tentor->full_name,
                'phone'                      => $tentor->phone,
                'email'                      => $tentor->email,
                'photo_url'                  => $tentor->photo_url,
                'specialization'             => $tentor->specialization,
                'total_sessions'             => $sessionsCount, // Otomatis terhitung hadir tiap melakukan absensi siswa
                'total_students_taught'      => $studentsTaughtInSessions,
                'present_students_count'     => $presentStudentsInSessions,
                'avg_class_attendance_rate'  => $avgRate,
                'last_session_date'          => $latestDate ? $latestDate->format('Y-m-d') : null,
                'last_session_date_formatted'=> $latestFormatted,
                'status_label'               => $statusLabel,
                'session_history'            => $sessionHistory,
            ];
        }

        // Urutkan rekap: yang sesi mengajarnya paling banyak di atas
        usort($tentorsRecap, function ($a, $b) {
            return $b['total_sessions'] <=> $a['total_sessions'];
        });

        // 4. Hitung Metrik KPI Keseluruhan
        $totalActiveTentors = $tentors->count();
        $overallPresenceRate = $totalActiveTentors > 0
            ? round(($activeTeachingTentorsCount / $totalActiveTentors) * 100, 1)
            : 0;

        $kpi = [
            'total_active_tentors'       => $totalActiveTentors,
            'active_teaching_tentors'    => $activeTeachingTentorsCount,
            'total_sessions'             => $totalSessionsConducted,
            'total_students_served'      => $totalStudentsServed,
            'avg_sessions_per_tentor'    => $activeTeachingTentorsCount > 0 ? round($totalSessionsConducted / $activeTeachingTentorsCount, 1) : 0,
            'overall_presence_rate'      => $overallPresenceRate,
        ];

        return [
            'kpi'           => $kpi,
            'tentors_recap' => $tentorsRecap,
            'session_logs'  => $sessionLogs,
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
        $kpiItems = [
            ['title' => 'TOTAL GURU AKTIF', 'value' => $kpi['total_active_tentors'] . ' Guru', 'col1' => 'A', 'col2' => 'B'],
            ['title' => 'GURU MENGAJAR', 'value' => $kpi['active_teaching_tentors'] . ' Guru Aktif', 'col1' => 'C', 'col2' => 'D'],
            ['title' => 'TOTAL SESI PERTEMUAN', 'value' => $kpi['total_sessions'] . ' Pertemuan', 'col1' => 'E', 'col2' => 'F'],
            ['title' => 'TOTAL SISWA DIAJAR', 'value' => $kpi['total_students_served'] . ' Siswa', 'col1' => 'G', 'col2' => 'H'],
        ];

        foreach ($kpiItems as $card) {
            $c1 = $card['col1'];
            $c2 = $card['col2'];
            $r1 = $startRow;
            $r2 = $startRow + 1;

            $sheet->mergeCells("{$c1}{$r1}:{$c2}{$r1}");
            $sheet->mergeCells("{$c1}{$r2}:{$c2}{$r2}");

            $sheet->setCellValue("{$c1}{$r1}", $card['title']);
            $sheet->setCellValue("{$c1}{$r2}", $card['value']);

            $sheet->getStyle("{$c1}{$r1}")->applyFromArray([
                'font' => ['size' => 8, 'bold' => true, 'color' => ['rgb' => '7C2D12']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEDD5']],
            ]);

            $sheet->getStyle("{$c1}{$r2}")->applyFromArray([
                'font' => ['size' => 12, 'bold' => true, 'color' => ['rgb' => 'C2410C']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF7ED']],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'EA580C']],
                ],
            ]);
        }
    }
}

