<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import SearchableSelect, { type SelectOption } from '@/components/UI/SearchableSelect.vue';
import {
    FileBarChart,
    Download,
    Search,
    CalendarCheck,
    UserCheck,
    UserX,
    TrendingUp,
    Filter,
    RotateCcw,
    ChevronDown,
    ChevronUp,
    ChevronLeft,
    ChevronRight,
    Users,
    BookOpen,
    Eye,
    X,
    Calendar,
    Info,
    Camera,
    Sparkles,
    CheckCircle2,
    Clock
} from 'lucide-vue-next';

interface StudentAttendanceDetail {
    session_id: string;
    date: string;
    formatted_date: string;
    subject_name: string;
    topic: string;
    status: 'present' | 'absent';
    notes?: string | null;
}

interface StudentRecap {
    id: string;
    name: string;
    nis?: string;
    username: string;
    study_group_id: string;
    study_group_name: string;
    education_level: string;
    total_sessions: number;
    present_count: number;
    absent_count: number;
    attendance_rate: number;
    status_label: 'Sangat Disiplin' | 'Cukup Disiplin' | 'Perlu Evaluasi' | 'Belum Ada Sesi';
    attendance_details: StudentAttendanceDetail[];
}

interface SessionLog {
    id: string;
    date: string;
    formatted_date: string;
    study_group_name: string;
    education_level: string;
    subject_name: string;
    tentor_name: string;
    topic_description: string;
    photo_url?: string | null;
    present_count: number;
    absent_count: number;
    total_count: number;
    attendance_rate: number;
}

interface StudyGroupOption {
    id: string;
    name: string;
    education_level: string;
}

interface Props {
    filters: {
        period: 'daily' | 'weekly' | 'monthly' | 'all_time' | 'custom';
        education_level: string;
        study_group_id: string;
        subject_name: string;
        search: string;
        date: string;
        month: string;
        start_date?: string;
        end_date?: string;
    };
    kpi: {
        total_sessions: number;
        total_present: number;
        total_absent: number;
        overall_attendance_rate: number;
        total_students: number;
    };
    students_recap: StudentRecap[];
    session_logs: SessionLog[];
    study_groups: StudyGroupOption[];
    subjects: string[];
    education_levels: string[];
    active_academic_year?: string;
}

const props = defineProps<Props>();

// ==========================================
// STATE FILTER
// ==========================================
const selectedPeriod = ref(props.filters.period || 'monthly');
const selectedEducationLevel = ref(props.filters.education_level || 'all');
const selectedStudyGroupId = ref(props.filters.study_group_id || 'all');
const selectedSubject = ref(props.filters.subject_name || 'all');
const searchQuery = ref(props.filters.search || '');
const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const selectedMonth = ref(props.filters.month || new Date().toISOString().slice(0, 7));
const selectedStartDate = ref(props.filters.start_date || '');
const selectedEndDate = ref(props.filters.end_date || '');

// ==========================================
// TAB ACTIVE: DEFAULT KE 'sessions' (Pertama)
// ==========================================
const activeTab = ref<'sessions' | 'students'>('sessions');

// ==========================================
// PENCARIAN & PAGINASI SESI PERTEMUAN (10 DATA)
// ==========================================
const sessionPerPage = 10;
const sessionCurrentPage = ref(1);
const sessionSearchQuery = ref('');

const filteredSessions = computed(() => {
    if (!sessionSearchQuery.value.trim()) {
        return props.session_logs;
    }
    const q = sessionSearchQuery.value.toLowerCase();
    return props.session_logs.filter(s =>
        s.tentor_name.toLowerCase().includes(q) ||
        s.subject_name.toLowerCase().includes(q) ||
        s.study_group_name.toLowerCase().includes(q) ||
        s.formatted_date.toLowerCase().includes(q) ||
        s.education_level.toLowerCase().includes(q)
    );
});

watch(sessionSearchQuery, () => {
    sessionCurrentPage.value = 1;
});

const totalSessionPages = computed(() => {
    return Math.max(1, Math.ceil(filteredSessions.value.length / sessionPerPage));
});

const paginatedSessions = computed(() => {
    const start = (sessionCurrentPage.value - 1) * sessionPerPage;
    return filteredSessions.value.slice(start, start + sessionPerPage);
});

const studentPerPage = 10;
const studentCurrentPage = ref(1);

const totalStudentPages = computed(() => {
    return Math.max(1, Math.ceil(props.students_recap.length / studentPerPage));
});

const paginatedStudents = computed(() => {
    const start = (studentCurrentPage.value - 1) * studentPerPage;
    return props.students_recap.slice(start, start + studentPerPage);
});

// Batasi tampilan paginasi hanya 3 angka aktif di sekitar halaman saat ini
const getVisiblePages = (current: number, total: number): number[] => {
    if (total <= 3) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 2) {
        return [1, 2, 3];
    }
    if (current >= total - 1) {
        return [total - 2, total - 1, total];
    }
    return [current - 1, current, current + 1];
};

const visibleSessionPages = computed(() => {
    return getVisiblePages(sessionCurrentPage.value, totalSessionPages.value);
});

const visibleStudentPages = computed(() => {
    return getVisiblePages(studentCurrentPage.value, totalStudentPages.value);
});

// ==========================================
// MODAL RINCIAN PESERTA DIDIK
// ==========================================
const selectedStudentForModal = ref<StudentRecap | null>(null);

const openStudentDetailModal = (student: StudentRecap) => {
    selectedStudentForModal.value = student;
};

const closeStudentDetailModal = () => {
    selectedStudentForModal.value = null;
};

// ==========================================
// MODAL PHOTO PREVIEW
// ==========================================
const previewPhotoUrl = ref<string | null>(null);
const previewPhotoTitle = ref<string>('');

const openPhotoModal = (url: string, title: string) => {
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title;
};

const closePhotoModal = () => {
    previewPhotoUrl.value = null;
    previewPhotoTitle.value = '';
};

// Kelompok bimbel yang disaring berdasarkan jenjang yang dipilih
const filteredStudyGroups = computed(() => {
    if (selectedEducationLevel.value === 'all') {
        return props.study_groups;
    }
    return props.study_groups.filter(g => g.education_level === selectedEducationLevel.value);
});

// Opsi SearchableSelect untuk Kelompok Bimbel
const studyGroupOptions = computed<SelectOption[]>(() => {
    return filteredStudyGroups.value.map(g => ({
        value: g.id,
        label: g.name,
        sublabel: `Jenjang ${g.education_level}`,
    }));
});

// Opsi SearchableSelect untuk Mata Pelajaran
const subjectOptions = computed<SelectOption[]>(() => {
    return props.subjects.map(s => ({
        value: s,
        label: s,
    }));
});

// Jika jenjang berubah dan kelompok terpilih tidak sesuai, reset kelompok ke 'all'
watch(selectedEducationLevel, (newLevel) => {
    if (newLevel !== 'all' && selectedStudyGroupId.value !== 'all') {
        const stillValid = props.study_groups.some(
            g => g.id === selectedStudyGroupId.value && g.education_level === newLevel
        );
        if (!stillValid) {
            selectedStudyGroupId.value = 'all';
        }
    }
    sessionCurrentPage.value = 1;
    studentCurrentPage.value = 1;
    applyFilter();
});

// ==========================================
// DEBOUNCE SEARCH & APPLY FILTER
// ==========================================
let searchDebounceTimer: any = null;

const onSearchInput = () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        sessionCurrentPage.value = 1;
        studentCurrentPage.value = 1;
        applyFilter();
    }, 400);
};

const applyFilter = () => {
    sessionCurrentPage.value = 1;
    studentCurrentPage.value = 1;
    router.get(
        '/reports/student-attendance',
        {
            period: selectedPeriod.value,
            education_level: selectedEducationLevel.value,
            study_group_id: selectedStudyGroupId.value,
            subject_name: selectedSubject.value,
            search: searchQuery.value,
            date: selectedDate.value,
            month: selectedMonth.value,
            start_date: selectedStartDate.value,
            end_date: selectedEndDate.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilter = () => {
    selectedPeriod.value = 'monthly';
    selectedEducationLevel.value = 'all';
    selectedStudyGroupId.value = 'all';
    selectedSubject.value = 'all';
    searchQuery.value = '';
    selectedDate.value = new Date().toISOString().split('T')[0];
    selectedMonth.value = new Date().toISOString().slice(0, 7);
    selectedStartDate.value = '';
    selectedEndDate.value = '';
    sessionCurrentPage.value = 1;
    studentCurrentPage.value = 1;

    router.get(
        '/reports/student-attendance',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

// URL Ekspor Excel
const exportExcelUrl = computed(() => {
    const params = new URLSearchParams({
        period: selectedPeriod.value,
        education_level: selectedEducationLevel.value,
        study_group_id: selectedStudyGroupId.value,
        subject_name: selectedSubject.value,
        search: searchQuery.value,
        date: selectedDate.value,
        month: selectedMonth.value,
        start_date: selectedStartDate.value || '',
        end_date: selectedEndDate.value || '',
    });
    return `/reports/student-attendance/export?${params.toString()}`;
});

// ==========================================
// DATA CHART ANALITIK (SOFT & MENARIK)
// ==========================================

// 1. Tren Kehadiran Waktu ke Waktu (Line / Area SVG)
const trendChronologicalSessions = computed(() => {
    return [...props.session_logs]
        .reverse()
        .slice(-12); // Ambil maksimal 12 sesi terakhir secara kronologis
});

// Titik SVG untuk Area & Line Chart
const chartWidth = 600;
const chartHeight = 160;
const paddingX = 40;
const paddingY = 25;

const chartPoints = computed(() => {
    const list = trendChronologicalSessions.value;
    if (list.length === 0) return [];
    if (list.length === 1) {
        const y = chartHeight - paddingY - (list[0].attendance_rate / 100) * (chartHeight - paddingY * 2);
        return [{ x: chartWidth / 2, y, item: list[0] }];
    }

    const usableWidth = chartWidth - paddingX * 2;
    const usableHeight = chartHeight - paddingY * 2;
    const step = usableWidth / (list.length - 1);

    return list.map((item, i) => {
        const x = paddingX + i * step;
        const rate = Math.min(100, Math.max(0, item.attendance_rate));
        const y = chartHeight - paddingY - (rate / 100) * usableHeight;
        return { x, y, item };
    });
});

// Path kurva halus SVG
const linePath = computed(() => {
    const points = chartPoints.value;
    if (points.length < 2) return '';

    return points.reduce((acc, pt, i, arr) => {
        if (i === 0) return `M ${pt.x} ${pt.y}`;
        const prev = arr[i - 1];
        const cp1x = prev.x + (pt.x - prev.x) / 2;
        const cp1y = prev.y;
        const cp2x = prev.x + (pt.x - prev.x) / 2;
        const cp2y = pt.y;
        return `${acc} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${pt.x} ${pt.y}`;
    }, '');
});

const areaPath = computed(() => {
    const lp = linePath.value;
    if (!lp || chartPoints.value.length < 2) return '';
    const points = chartPoints.value;
    const first = points[0];
    const last = points[points.length - 1];
    const bottomY = chartHeight - paddingY;
    return `${lp} L ${last.x} ${bottomY} L ${first.x} ${bottomY} Z`;
});

// Tooltip interaktif chart (flicker-free tracking pada SVG)
const hoveredPoint = ref<{ x: number; y: number; item: SessionLog } | null>(null);

const handleChartMouseMove = (event: MouseEvent) => {
    const svg = event.currentTarget as SVGSVGElement;
    const rect = svg.getBoundingClientRect();
    if (!rect.width || chartPoints.value.length === 0) return;

    // Normalisasi posisi kursor mouse ke koordinat internal SVG
    const mouseX = ((event.clientX - rect.left) / rect.width) * chartWidth;

    // Cari titik data terdekat berdasarkan koordinat X
    let closest = chartPoints.value[0];
    let minDistance = Math.abs(mouseX - closest.x);

    for (let i = 1; i < chartPoints.value.length; i++) {
        const pt = chartPoints.value[i];
        const dist = Math.abs(mouseX - pt.x);
        if (dist < minDistance) {
            minDistance = dist;
            closest = pt;
        }
    }

    // Tampilkan tooltip jika kursor berada dalam jangkauan wajar
    const threshold = chartPoints.value.length > 1
        ? ((chartWidth - paddingX * 2) / (chartPoints.value.length - 1)) * 0.8
        : 80;

    if (minDistance <= threshold) {
        hoveredPoint.value = closest;
    } else {
        hoveredPoint.value = null;
    }
};

const handleChartMouseLeave = () => {
    hoveredPoint.value = null;
};

// 2. Distribusi Evaluasi Siswa (Donut Breakdown)
const disciplineDistribution = computed(() => {
    const total = props.students_recap.length || 1;
    const disiplin = props.students_recap.filter(s => s.status_label === 'Sangat Disiplin').length;
    const cukup = props.students_recap.filter(s => s.status_label === 'Cukup Disiplin').length;
    const evaluasi = props.students_recap.filter(s => s.status_label === 'Perlu Evaluasi').length;
    const belum = props.students_recap.filter(s => s.status_label === 'Belum Ada Sesi').length;

    return [
        { label: 'Sangat Disiplin (≥90%)', count: disiplin, percent: Math.round((disiplin / total) * 100), color: '#10B981', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        { label: 'Cukup Disiplin (75-89%)', count: cukup, percent: Math.round((cukup / total) * 100), color: '#F59E0B', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
        { label: 'Perlu Evaluasi (<75%)', count: evaluasi, percent: Math.round((evaluasi / total) * 100), color: '#F43F5E', bg: 'bg-rose-50 text-rose-700 border-rose-200' },
        { label: 'Belum Ada Sesi', count: belum, percent: Math.round((belum / total) * 100), color: '#94A3B8', bg: 'bg-slate-50 text-slate-600 border-slate-200' },
    ];
});

// SVG Donut Slices
const donutSegments = computed(() => {
    const radius = 54;
    const circumference = 2 * Math.PI * radius;
    let accumulatedPercent = 0;

    return disciplineDistribution.value.map(item => {
        const strokeDasharray = `${(item.percent / 100) * circumference} ${circumference}`;
        const strokeDashoffset = -((accumulatedPercent / 100) * circumference);
        accumulatedPercent += item.percent;
        return {
            ...item,
            strokeDasharray,
            strokeDashoffset,
        };
    });
});
</script>

<template>
    <Head title="Rekapitulasi Kehadiran Peserta Didik" />

    <AuthenticatedLayout>
        <div class="space-y-6 pb-12">
            <!-- ======================================================== -->
            <!-- HEADER HALAMAN: TOMBOL EXPORT DI POSISI PALING KANAN     -->
            <!-- ======================================================== -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-orange-50 text-orange-600 border border-orange-200/80 shadow-xs">
                            <FileBarChart class="w-6 h-6" />
                        </div>
                        Rekapitulasi Kehadiran Peserta Didik
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Analisis lengkap kehadiran siswa berdasarkan mata pelajaran, kelompok, jenjang, dan periode waktu.
                    </p>
                </div>

                <!-- Tombol Ekspor Excel: Selalu di posisi paling kanan -->
                <div class="flex items-center justify-end sm:ml-auto">
                    <a
                        :href="exportExcelUrl"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs hover:shadow transition-all duration-150 cursor-pointer"
                    >
                        <Download class="w-4 h-4" />
                        <span>Ekspor Laporan Excel</span>
                    </a>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- STATISTIK KPI CARDS                                      -->
            <!-- ======================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Sesi Pertemuan -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Sesi Kelas</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ kpi.total_sessions }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Pertemuan terlaksana</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                        <CalendarCheck class="w-6 h-6" />
                    </div>
                </div>

                <!-- Total Kehadiran (Hadir) -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kehadiran</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ kpi.total_present }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Siswa hadir tepat waktu</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center">
                        <UserCheck class="w-6 h-6" />
                    </div>
                </div>

                <!-- Total Ketidakhadiran (Alpa) -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Ketidakhadiran</p>
                        <p class="text-2xl font-bold text-rose-600 mt-1">{{ kpi.total_absent }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Alpa / tanpa keterangan</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center">
                        <UserX class="w-6 h-6" />
                    </div>
                </div>

                <!-- Rata-rata Persentase Kehadiran -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Kehadiran</p>
                            <p class="text-2xl font-bold text-orange-600 mt-1">{{ kpi.overall_attendance_rate }}%</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 border border-orange-100 flex items-center justify-center">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="mt-3 w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="kpi.overall_attendance_rate >= 85 ? 'bg-emerald-500' : (kpi.overall_attendance_rate >= 70 ? 'bg-amber-500' : 'bg-rose-500')"
                            :style="{ width: `${Math.min(100, kpi.overall_attendance_rate)}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CHART VISUAL ANALISIS KEHADIRAN (SOFT & MENARIK)         -->
            <!-- ======================================================== -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <Sparkles class="w-4 h-4 text-orange-500" />
                        <h2 class="font-bold text-slate-900 text-sm">Visualisasi & Tren Kehadiran Siswa</h2>
                    </div>
                    <span class="text-[11px] font-medium text-slate-400">
                        Berdasarkan parameter filter yang aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <!-- Sisi Kiri (7 Kolom): Tren Kehadiran Area Chart -->
                    <div class="lg:col-span-7 bg-slate-50/70 p-4 rounded-2xl border border-slate-100 relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <TrendingUp class="w-3.5 h-3.5 text-orange-500" />
                                Tren Persentase Kehadiran per Sesi
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ trendChronologicalSessions.length }} sesi terakhir
                            </span>
                        </div>

                        <!-- Empty State Chart jika belum ada sesi -->
                        <div v-if="trendChronologicalSessions.length === 0" class="h-40 flex items-center justify-center text-xs text-slate-400 italic">
                            Belum ada riwayat sesi pertemuan untuk membentuk grafik tren.
                        </div>

                        <div v-else class="relative overflow-visible">
                            <!-- SVG Area & Line Chart dengan Tracking Kursor Halus (Flicker-Free) -->
                            <svg
                                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                                class="w-full h-40 overflow-visible cursor-crosshair select-none"
                                @mousemove="handleChartMouseMove"
                                @mouseleave="handleChartMouseLeave"
                            >
                                <defs>
                                    <!-- Soft Orange to Transparent Gradient -->
                                    <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#FB923C" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#FB923C" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>

                                <!-- Garis Grid Horizontal -->
                                <line :x1="paddingX" :y1="chartHeight - paddingY" :x2="chartWidth - paddingX" :y2="chartHeight - paddingY" stroke="#E2E8F0" stroke-width="1" />
                                <line :x1="paddingX" :y1="chartHeight / 2" :x2="chartWidth - paddingX" :y2="chartHeight / 2" stroke="#F1F5F9" stroke-dasharray="3 3" stroke-width="1" />
                                <line :x1="paddingX" :y1="paddingY" :x2="chartWidth - paddingX" :y2="paddingY" stroke="#F1F5F9" stroke-dasharray="3 3" stroke-width="1" />

                                <!-- Label Sumbu Y -->
                                <text :x="paddingX - 6" :y="paddingY + 3" fill="#94A3B8" font-size="9" text-anchor="end" class="pointer-events-none select-none">100%</text>
                                <text :x="paddingX - 6" :y="chartHeight / 2 + 3" fill="#94A3B8" font-size="9" text-anchor="end" class="pointer-events-none select-none">50%</text>
                                <text :x="paddingX - 6" :y="chartHeight - paddingY + 3" fill="#94A3B8" font-size="9" text-anchor="end" class="pointer-events-none select-none">0%</text>

                                <!-- Area di Bawah Garis -->
                                <path :d="areaPath" fill="url(#trendGradient)" class="pointer-events-none" />

                                <!-- Garis Kurva Halus -->
                                <path :d="linePath" fill="none" stroke="#F97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none" />

                                <!-- Garis Pandu Vertikal saat Hover -->
                                <line
                                    v-if="hoveredPoint"
                                    :x1="hoveredPoint.x"
                                    :y1="paddingY"
                                    :x2="hoveredPoint.x"
                                    :y2="chartHeight - paddingY"
                                    stroke="#FB923C"
                                    stroke-width="1.5"
                                    stroke-dasharray="3 3"
                                    class="pointer-events-none"
                                />

                                <!-- Titik-titik Data (Pointer Events None untuk Mencegah Flickering) -->
                                <g v-for="(pt, idx) in chartPoints" :key="idx" class="pointer-events-none">
                                    <circle
                                        :cx="pt.x"
                                        :cy="pt.y"
                                        :r="hoveredPoint && hoveredPoint.item.id === pt.item.id ? 6 : 4"
                                        :fill="hoveredPoint && hoveredPoint.item.id === pt.item.id ? '#EA580C' : '#FFFFFF'"
                                        stroke="#F97316"
                                        :stroke-width="hoveredPoint && hoveredPoint.item.id === pt.item.id ? 2.5 : 2"
                                        class="transition-all duration-150"
                                    />
                                    <text
                                        :x="pt.x"
                                        :y="chartHeight - paddingY + 14"
                                        fill="#64748B"
                                        font-size="8"
                                        text-anchor="middle"
                                        class="font-mono select-none"
                                    >
                                        {{ pt.item.date.slice(5) }}
                                    </text>
                                </g>
                            </svg>

                            <!-- Tooltip Melayang saat Hover Titik (Stabil & Bebas Kedip) -->
                            <div
                                v-if="hoveredPoint"
                                class="absolute z-30 bg-slate-900/95 text-white text-[11px] p-2.5 rounded-xl shadow-xl pointer-events-none backdrop-blur-xs border border-slate-700/60 whitespace-nowrap transition-transform duration-75"
                                :style="{
                                    left: `${(hoveredPoint.x / chartWidth) * 100}%`,
                                    top: `${(hoveredPoint.y / chartHeight) * 100}%`,
                                    transform: hoveredPoint.y < 55
                                        ? 'translate(-50%, 14px)'
                                        : 'translate(-50%, calc(-100% - 14px))'
                                }"
                            >
                                <p class="font-bold text-orange-300">{{ hoveredPoint.item.subject_name }}</p>
                                <p class="text-[10px] text-slate-300">{{ hoveredPoint.item.study_group_name }} &bull; {{ hoveredPoint.item.formatted_date }}</p>
                                <p class="mt-1 font-semibold text-emerald-300">
                                    Kehadiran: {{ hoveredPoint.item.attendance_rate }}% ({{ hoveredPoint.item.present_count }}/{{ hoveredPoint.item.total_count }} siswa)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sisi Kanan (5 Kolom): Persebaran Kedisiplinan Siswa (Donut & Breakdown) -->
                    <div class="lg:col-span-5 bg-slate-50/70 p-4 rounded-2xl border border-slate-100 flex flex-col sm:flex-row items-center gap-5">
                        <!-- Donut SVG -->
                        <div class="relative w-32 h-32 shrink-0 flex items-center justify-center">
                            <svg viewBox="0 0 130 130" class="w-full h-full -rotate-90 transform">
                                <circle
                                    cx="65"
                                    cy="65"
                                    r="54"
                                    fill="transparent"
                                    stroke="#F1F5F9"
                                    stroke-width="12"
                                />
                                <circle
                                    v-for="(seg, idx) in donutSegments"
                                    :key="idx"
                                    cx="65"
                                    cy="65"
                                    r="54"
                                    fill="transparent"
                                    :stroke="seg.color"
                                    stroke-width="12"
                                    :stroke-dasharray="seg.strokeDasharray"
                                    :stroke-dashoffset="seg.strokeDashoffset"
                                    stroke-linecap="round"
                                    class="transition-all duration-700"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-xs font-bold text-slate-900 leading-tight">
                                    {{ kpi.overall_attendance_rate }}%
                                </span>
                                <span class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">
                                    Disiplin
                                </span>
                            </div>
                        </div>

                        <!-- Keterangan Status Kedisiplinan -->
                        <div class="space-y-1.5 w-full">
                            <div
                                v-for="(item, idx) in disciplineDistribution"
                                :key="idx"
                                class="flex items-center justify-between text-xs p-1.5 rounded-xl border"
                                :class="item.bg"
                            >
                                <div class="flex items-center gap-2 truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: item.color }"></span>
                                    <span class="truncate text-[11px] font-medium">{{ item.label }}</span>
                                </div>
                                <div class="font-bold text-[11px] shrink-0">
                                    {{ item.count }} ({{ item.percent }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- PANEL FILTER LENGKAP                                     -->
            <!-- ======================================================== -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-orange-500" />
                        <span class="font-bold text-slate-900 text-sm">Filter & Parameter Rekapitulasi</span>
                    </div>

                    <!-- Tombol Periode Cepat -->
                    <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-2xl">
                        <button
                            type="button"
                            @click="selectedPeriod = 'daily'; applyFilter()"
                            :class="selectedPeriod === 'daily' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer"
                        >
                            Harian
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'weekly'; applyFilter()"
                            :class="selectedPeriod === 'weekly' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer"
                        >
                            Mingguan
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'monthly'; applyFilter()"
                            :class="selectedPeriod === 'monthly' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer"
                        >
                            Bulanan
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'all_time'; applyFilter()"
                            :class="selectedPeriod === 'all_time' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer"
                        >
                            Selamanya
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'custom'; applyFilter()"
                            :class="selectedPeriod === 'custom' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer"
                        >
                            Kustom
                        </button>
                    </div>
                </div>

                <!-- Input Pilihan Filter -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Dynamic Date Picker sesuai periode -->
                    <div v-if="selectedPeriod === 'daily'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Pertemuan</label>
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20"
                        />
                    </div>

                    <div v-else-if="selectedPeriod === 'monthly'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bulan & Tahun</label>
                        <input
                            type="month"
                            v-model="selectedMonth"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20"
                        />
                    </div>

                    <div v-else-if="selectedPeriod === 'custom'" class="sm:col-span-2 grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mulai Tanggal</label>
                            <input
                                type="date"
                                v-model="selectedStartDate"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="selectedEndDate"
                                @change="applyFilter"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20"
                            />
                        </div>
                    </div>

                    <!-- Filter Jenjang -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenjang Pendidikan</label>
                        <select
                            v-model="selectedEducationLevel"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20"
                        >
                            <option value="all">Semua Jenjang</option>
                            <option v-for="lvl in education_levels" :key="lvl" :value="lvl">
                                Jenjang {{ lvl }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Kelompok Bimbel (Searchable) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kelompok Bimbel</label>
                        <SearchableSelect
                            v-model="selectedStudyGroupId"
                            :options="studyGroupOptions"
                            placeholder="Pilih Kelompok"
                            search-placeholder="Cari nama kelompok..."
                            all-label="Semua Kelompok"
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Filter Mata Pelajaran (Searchable) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mata Pelajaran</label>
                        <SearchableSelect
                            v-model="selectedSubject"
                            :options="subjectOptions"
                            placeholder="Pilih Mata Pelajaran"
                            search-placeholder="Cari mata pelajaran..."
                            all-label="Semua Mata Pelajaran"
                            @change="applyFilter"
                        />
                    </div>
                </div>

                <!-- Footer Filter Action -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 font-medium">
                            <Info class="w-3.5 h-3.5" />
                            Ditemukan {{ students_recap.length }} peserta didik & {{ session_logs.length }} sesi
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="resetFilter"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-orange-600 transition cursor-pointer"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TABS SWITCHER: LOG RIWAYAT SESI PERTAMA, REKAP KEDUA     -->
            <!-- ======================================================== -->
            <div class="flex items-center gap-3 border-b border-slate-200">
                <!-- TAB 1: LOG RIWAYAT SESI PERTEMUAN (UTAMA) -->
                <button
                    type="button"
                    @click="activeTab = 'sessions'"
                    :class="activeTab === 'sessions' ? 'border-orange-500 text-orange-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    class="pb-3 px-2 border-b-2 text-sm transition flex items-center gap-2 cursor-pointer"
                >
                    <Calendar class="w-4 h-4" />
                    <span>Log Riwayat Sesi Pertemuan</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-600 font-semibold">
                        {{ session_logs.length }}
                    </span>
                </button>

                <!-- TAB 2: REKAP PER PESERTA DIDIK -->
                <button
                    type="button"
                    @click="activeTab = 'students'"
                    :class="activeTab === 'students' ? 'border-orange-500 text-orange-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    class="pb-3 px-2 border-b-2 text-sm transition flex items-center gap-2 cursor-pointer"
                >
                    <Users class="w-4 h-4" />
                    <span>Rekap Per Peserta Didik</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] bg-slate-100 text-slate-600 font-semibold">
                        {{ students_recap.length }}
                    </span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: LOG RIWAYAT SESI PERTEMUAN KELAS (PAGINASI 10)    -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'sessions'" class="space-y-4">
                <!-- Search bar khusus log riwayat sesi (nama tentor, mapel, kelompok) -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            <Calendar class="w-4 h-4 text-orange-500" />
                            Log Sesi Pertemuan Kelas
                        </span>
                        <span>&bull;</span>
                        <span class="font-semibold text-slate-600">{{ filteredSessions.length }} sesi ditemukan</span>
                    </div>

                    <!-- Input Pencarian Tentor & Mata Pelajaran -->
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            type="text"
                            v-model="sessionSearchQuery"
                            placeholder="Cari nama tentor, mata pelajaran..."
                            class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                        />
                        <button
                            v-if="sessionSearchQuery"
                            type="button"
                            @click="sessionSearchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                            title="Bersihkan pencarian"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-3.5 px-4 w-12 text-center">No</th>
                                    <th class="py-3.5 px-4">Tanggal Sesi</th>
                                    <th class="py-3.5 px-4">Jenjang & Kelompok</th>
                                    <th class="py-3.5 px-4">Mata Pelajaran</th>
                                    <th class="py-3.5 px-4">Tentor</th>
                                    <th class="py-3.5 px-4 text-center">Hadir / Total</th>
                                    <th class="py-3.5 px-4 text-center">% Kehadiran</th>
                                    <th class="py-3.5 px-4 text-center">Dokumentasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(session, idx) in paginatedSessions"
                                    :key="session.id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="py-3 px-4 text-center font-medium text-slate-400">
                                        {{ (sessionCurrentPage - 1) * sessionPerPage + idx + 1 }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-slate-900">{{ session.formatted_date }}</p>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ session.date }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-0.5">
                                            <span class="font-semibold text-slate-800">{{ session.study_group_name }}</span>
                                            <span class="block text-[10px] text-slate-400 font-medium">Jenjang {{ session.education_level }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-100">
                                            {{ session.subject_name }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-700">
                                        {{ session.tentor_name }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="font-bold text-emerald-600">{{ session.present_count }}</span>
                                        <span class="text-slate-400 mx-1">/</span>
                                        <span class="font-bold text-slate-700">{{ session.total_count }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                                            :class="session.attendance_rate >= 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                        >
                                            {{ session.attendance_rate }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button
                                            v-if="session.photo_url"
                                            type="button"
                                            @click="openPhotoModal(session.photo_url, `${session.study_group_name} - ${session.formatted_date}`)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-orange-50 hover:text-orange-600 text-slate-600 text-[11px] font-semibold transition cursor-pointer"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                            <span>Foto</span>
                                        </button>
                                        <span v-else class="text-[11px] text-slate-400 italic">-</span>
                                    </td>
                                </tr>

                                <tr v-if="filteredSessions.length === 0">
                                    <td colspan="8" class="py-12 text-center text-slate-400 italic">
                                        Belum ada riwayat sesi pertemuan kelas yang dicatat pada filter ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINASI SESI (10 DATA PER HALAMAN) -->
                    <div
                        v-if="filteredSessions.length > 0"
                        class="p-4 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500"
                    >
                        <div>
                            Menampilkan
                            <strong class="text-slate-800">
                                {{ (sessionCurrentPage - 1) * sessionPerPage + 1 }}
                            </strong>
                            -
                            <strong class="text-slate-800">
                                {{ Math.min(sessionCurrentPage * sessionPerPage, filteredSessions.length) }}
                            </strong>
                            dari
                            <strong class="text-slate-800">{{ filteredSessions.length }}</strong>
                            sesi pertemuan
                        </div>

                        <div v-if="totalSessionPages > 1" class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="sessionCurrentPage = Math.max(1, sessionCurrentPage - 1)"
                                :disabled="sessionCurrentPage === 1"
                                class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <button
                                v-for="page in visibleSessionPages"
                                :key="page"
                                type="button"
                                @click="sessionCurrentPage = page"
                                class="w-8 h-8 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="sessionCurrentPage === page ? 'bg-orange-500 text-white shadow-xs' : 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-700'"
                            >
                                {{ page }}
                            </button>

                            <button
                                type="button"
                                @click="sessionCurrentPage = Math.min(totalSessionPages, sessionCurrentPage + 1)"
                                :disabled="sessionCurrentPage === totalSessionPages"
                                class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: REKAP PER PESERTA DIDIK (PAGINASI 10)             -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'students'" class="space-y-4">
                <!-- Search bar khusus peserta didik -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-3xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            <Users class="w-4 h-4 text-orange-500" />
                            Rekapitulasi Peserta Didik
                        </span>
                        <span>&bull;</span>
                        <span class="font-semibold text-slate-600">{{ students_recap.length }} siswa terdaftar</span>
                    </div>

                    <!-- Input Pencarian Peserta Didik -->
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            placeholder="Cari nama siswa, NIS, username..."
                            class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''; applyFilter()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                            title="Bersihkan pencarian"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-3.5 px-4 w-12 text-center">No</th>
                                    <th class="py-3.5 px-4">Peserta Didik</th>
                                    <th class="py-3.5 px-4">Jenjang & Kelompok</th>
                                    <th class="py-3.5 px-4 text-center">Total Sesi</th>
                                    <th class="py-3.5 px-4 text-center">Hadir</th>
                                    <th class="py-3.5 px-4 text-center">Alpa</th>
                                    <th class="py-3.5 px-4 text-center w-40">% Kehadiran</th>
                                    <th class="py-3.5 px-4 text-center">Evaluasi Status</th>
                                    <th class="py-3.5 px-4 text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(student, idx) in paginatedStudents"
                                    :key="student.id"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="py-3 px-4 text-center font-medium text-slate-400">
                                        {{ (studentCurrentPage - 1) * studentPerPage + idx + 1 }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xs shrink-0">
                                                {{ student.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900">{{ student.name }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="font-mono text-[11px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                                        {{ student.username }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="space-y-0.5">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ student.study_group_name }}
                                            </span>
                                            <p class="text-[11px] text-slate-400">Jenjang: {{ student.education_level }}</p>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-700">
                                        {{ student.total_sessions }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">
                                            {{ student.present_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold" :class="student.absent_count > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-400'">
                                            {{ student.absent_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2 justify-center">
                                            <div class="w-20 bg-slate-100 h-2 rounded-full overflow-hidden">
                                                <div
                                                    class="h-full rounded-full"
                                                    :class="student.attendance_rate >= 90 ? 'bg-emerald-500' : (student.attendance_rate >= 75 ? 'bg-amber-500' : 'bg-rose-500')"
                                                    :style="{ width: `${student.attendance_rate}%` }"
                                                ></div>
                                            </div>
                                            <span class="font-bold text-slate-800 text-xs w-10 text-right">
                                                {{ student.attendance_rate }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200': student.status_label === 'Sangat Disiplin',
                                                'bg-amber-50 text-amber-700 border border-amber-200': student.status_label === 'Cukup Disiplin',
                                                'bg-rose-50 text-rose-700 border border-rose-200': student.status_label === 'Perlu Evaluasi',
                                                'bg-slate-100 text-slate-500 border border-slate-200': student.status_label === 'Belum Ada Sesi',
                                            }"
                                        >
                                            {{ student.status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <!-- Tombol Buka Modal Rincian Siswa -->
                                        <button
                                            type="button"
                                            @click="openStudentDetailModal(student)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-semibold transition cursor-pointer"
                                            title="Lihat Histori & Rincian Kehadiran Siswa"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                            <span>Rincian</span>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="students_recap.length === 0">
                                    <td colspan="9" class="py-12 text-center text-slate-400 italic">
                                        Tidak ada data peserta didik yang cocok dengan kriteria filter saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINASI SISWA (10 DATA PER HALAMAN) -->
                    <div
                        v-if="students_recap.length > 0"
                        class="p-4 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500"
                    >
                        <div>
                            Menampilkan
                            <strong class="text-slate-800">
                                {{ (studentCurrentPage - 1) * studentPerPage + 1 }}
                            </strong>
                            -
                            <strong class="text-slate-800">
                                {{ Math.min(studentCurrentPage * studentPerPage, students_recap.length) }}
                            </strong>
                            dari
                            <strong class="text-slate-800">{{ students_recap.length }}</strong>
                            peserta didik
                        </div>

                        <div v-if="totalStudentPages > 1" class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="studentCurrentPage = Math.max(1, studentCurrentPage - 1)"
                                :disabled="studentCurrentPage === 1"
                                class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <button
                                v-for="page in visibleStudentPages"
                                :key="page"
                                type="button"
                                @click="studentCurrentPage = page"
                                class="w-8 h-8 rounded-lg text-xs font-semibold transition cursor-pointer"
                                :class="studentCurrentPage === page ? 'bg-orange-500 text-white shadow-xs' : 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-700'"
                            >
                                {{ page }}
                            </button>

                            <button
                                type="button"
                                @click="studentCurrentPage = Math.min(totalStudentPages, studentCurrentPage + 1)"
                                :disabled="studentCurrentPage === totalStudentPages"
                                class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL RINCIAN PER PESERTA DIDIK                          -->
        <!-- ======================================================== -->
        <div
            v-if="selectedStudentForModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="closeStudentDetailModal"
        >
            <div class="bg-white rounded-3xl overflow-hidden max-w-3xl w-full shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[88vh]">
                <!-- Modal Header -->
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-sm shadow-2xs">
                            {{ selectedStudentForModal.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                                {{ selectedStudentForModal.name }}
                                <span class="text-xs font-normal font-mono text-slate-500 bg-white px-2 py-0.5 rounded-lg border border-slate-200">
                                    {{ selectedStudentForModal.username }}
                                </span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ selectedStudentForModal.study_group_name }} &bull; Jenjang {{ selectedStudentForModal.education_level }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="closeStudentDetailModal"
                        class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body: Stat Summary & History Table -->
                <div class="p-5 overflow-y-auto space-y-5">
                    <!-- Quick Stat Pills -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Total Sesi</span>
                            <span class="font-bold text-slate-900 text-base mt-0.5 block">{{ selectedStudentForModal.total_sessions }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-100 text-center">
                            <span class="text-emerald-600 block text-[10px] uppercase font-semibold">Hadir</span>
                            <span class="font-bold text-emerald-700 text-base mt-0.5 block">{{ selectedStudentForModal.present_count }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-rose-50 border border-rose-100 text-center">
                            <span class="text-rose-600 block text-[10px] uppercase font-semibold">Alpa</span>
                            <span class="font-bold text-rose-700 text-base mt-0.5 block">{{ selectedStudentForModal.absent_count }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-orange-50 border border-orange-100 text-center">
                            <span class="text-orange-600 block text-[10px] uppercase font-semibold">Persentase</span>
                            <span class="font-bold text-orange-700 text-base mt-0.5 block">{{ selectedStudentForModal.attendance_rate }}%</span>
                        </div>
                    </div>

                    <!-- History Detail List -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                                <BookOpen class="w-3.5 h-3.5 text-orange-500" />
                                Histori Presensi Seluruh Sesi ({{ selectedStudentForModal.attendance_details.length }} tercatat)
                            </h4>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': selectedStudentForModal.status_label === 'Sangat Disiplin',
                                    'bg-amber-50 text-amber-700 border border-amber-200': selectedStudentForModal.status_label === 'Cukup Disiplin',
                                    'bg-rose-50 text-rose-700 border border-rose-200': selectedStudentForModal.status_label === 'Perlu Evaluasi',
                                    'bg-slate-100 text-slate-500 border border-slate-200': selectedStudentForModal.status_label === 'Belum Ada Sesi',
                                }"
                            >
                                {{ selectedStudentForModal.status_label }}
                            </span>
                        </div>

                        <div v-if="selectedStudentForModal.attendance_details.length === 0" class="py-8 text-center text-xs text-slate-400 italic border border-dashed rounded-2xl">
                            Belum ada catatan presensi detail untuk siswa ini pada periode filter saat ini.
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="detail in selectedStudentForModal.attendance_details"
                                :key="detail.session_id"
                                class="p-3.5 rounded-2xl border text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 transition-colors"
                                :class="detail.status === 'present' ? 'bg-emerald-50/40 border-emerald-200/70' : 'bg-rose-50/40 border-rose-200/70'"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900">{{ detail.subject_name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">&bull; {{ detail.formatted_date }}</span>
                                    </div>
                                    <p class="text-slate-600 italic text-[11px] line-clamp-2">
                                        "{{ detail.topic }}"
                                    </p>
                                    <p v-if="detail.notes" class="text-[10px] text-slate-500">
                                        Catatan: {{ detail.notes }}
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                        :class="detail.status === 'present' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                    >
                                        <CheckCircle2 v-if="detail.status === 'present'" class="w-3.5 h-3.5" />
                                        <UserX v-else class="w-3.5 h-3.5" />
                                        {{ detail.status === 'present' ? 'Hadir' : 'Tidak Hadir' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="closeStudentDetailModal"
                        class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL PREVIEW FOTO DOKUMENTASI                           -->
        <!-- ======================================================== -->
        <div
            v-if="previewPhotoUrl"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="closePhotoModal"
        >
            <div class="bg-white rounded-3xl overflow-hidden max-w-2xl w-full shadow-2xl border border-slate-100 animate-in fade-in duration-200">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                        <Camera class="w-4 h-4 text-orange-500" />
                        Dokumentasi: {{ previewPhotoTitle }}
                    </h3>
                    <button
                        type="button"
                        @click="closePhotoModal"
                        class="p-1 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-4 bg-slate-950 flex items-center justify-center max-h-[70vh] overflow-hidden">
                    <img :src="previewPhotoUrl" alt="Foto Dokumentasi Sesi" class="max-h-[65vh] w-auto object-contain rounded-xl" />
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="closePhotoModal"
                        class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
