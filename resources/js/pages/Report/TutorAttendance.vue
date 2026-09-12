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
    Users,
    GraduationCap,
    TrendingUp,
    Filter,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    BookOpen,
    Eye,
    X,
    Calendar,
    Camera,
    Sparkles,
    CheckCircle2,
    Clock,
    Mail,
    Award,
    Layers
} from 'lucide-vue-next';

interface SessionHistoryItem {
    id: string;
    date: string;
    formatted_date: string;
    time: string;
    subject_name: string;
    study_group_name: string;
    education_level: string;
    topic_description?: string | null;
    photo_url?: string | null;
    present_count: number;
    absent_count: number;
    total_count: number;
    attendance_rate: number;
}

interface TentorRecap {
    id: string;
    name: string;
    full_name: string;
    phone?: string | null;
    email?: string | null;
    photo_url?: string | null;
    specialization?: string | null;
    total_sessions: number;
    total_students_taught: number;
    present_students_count: number;
    avg_class_attendance_rate: number;
    last_session_date?: string | null;
    last_session_date_formatted?: string | null;
    status_label: 'Sangat Aktif' | 'Aktif Mengajar' | 'Cukup Aktif' | 'Belum Ada Sesi';
    session_history: SessionHistoryItem[];
}

interface SessionLog {
    id: string;
    date: string;
    formatted_date: string;
    time: string;
    tentor_id: string;
    tentor_name: string;
    specialization: string;
    study_group_name: string;
    education_level: string;
    subject_name: string;
    topic_description?: string | null;
    photo_url?: string | null;
    present_count: number;
    absent_count: number;
    total_count: number;
    attendance_rate: number;
}

interface Props {
    filters: {
        period: 'daily' | 'weekly' | 'monthly' | 'all_time' | 'custom';
        education_level: string;
        subject_name: string;
        search: string;
        date: string;
        month: string;
        start_date?: string;
        end_date?: string;
    };
    kpi: {
        total_active_tentors: number;
        active_teaching_tentors: number;
        total_sessions: number;
        total_students_served: number;
        avg_sessions_per_tentor: number;
        overall_presence_rate: number;
    };
    tentors_recap: TentorRecap[];
    session_logs: SessionLog[];
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
const selectedSubject = ref(props.filters.subject_name || 'all');
const searchQuery = ref(props.filters.search || '');
const selectedDate = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const selectedMonth = ref(props.filters.month || new Date().toISOString().slice(0, 7));
const selectedStartDate = ref(props.filters.start_date || '');
const selectedEndDate = ref(props.filters.end_date || '');

// Tab Aktif: 'recap' (Rekap Guru) | 'sessions' (Log Sesi Mengajar)
const activeTab = ref<'recap' | 'sessions'>('recap');

// ==========================================
// PAGINASI TAB 1: REKAP GURU (10 DATA PER HALAMAN)
// ==========================================
const tentorPerPage = 10;
const tentorCurrentPage = ref(1);

const totalTentorPages = computed(() => {
    return Math.max(1, Math.ceil(props.tentors_recap.length / tentorPerPage));
});

const paginatedTentors = computed(() => {
    const start = (tentorCurrentPage.value - 1) * tentorPerPage;
    return props.tentors_recap.slice(start, start + tentorPerPage);
});

// ==========================================
// PAGINASI TAB 2: LOG SESI (10 DATA PER HALAMAN)
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

const visibleTentorPages = computed(() => {
    return getVisiblePages(tentorCurrentPage.value, totalTentorPages.value);
});

const visibleSessionPages = computed(() => {
    return getVisiblePages(sessionCurrentPage.value, totalSessionPages.value);
});

// ==========================================
// MODAL DETAIL GURU
// ==========================================
const selectedTentorForModal = ref<TentorRecap | null>(null);

const openTentorDetailModal = (tentor: TentorRecap) => {
    selectedTentorForModal.value = tentor;
};

const closeTentorDetailModal = () => {
    selectedTentorForModal.value = null;
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

// Opsi SearchableSelect untuk Mata Pelajaran / Spesialisasi
const subjectOptions = computed<SelectOption[]>(() => {
    return props.subjects.map(s => ({
        value: s,
        label: s,
    }));
});

// ==========================================
// DEBOUNCE SEARCH & APPLY FILTER
// ==========================================
let searchDebounceTimer: any = null;

const onSearchInput = () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        tentorCurrentPage.value = 1;
        sessionCurrentPage.value = 1;
        applyFilter();
    }, 400);
};

const applyFilter = () => {
    tentorCurrentPage.value = 1;
    sessionCurrentPage.value = 1;
    router.get(
        '/reports/tutor-attendance',
        {
            period: selectedPeriod.value,
            education_level: selectedEducationLevel.value,
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
    selectedSubject.value = 'all';
    searchQuery.value = '';
    selectedDate.value = new Date().toISOString().split('T')[0];
    selectedMonth.value = new Date().toISOString().slice(0, 7);
    selectedStartDate.value = '';
    selectedEndDate.value = '';
    tentorCurrentPage.value = 1;
    sessionCurrentPage.value = 1;

    router.get(
        '/reports/tutor-attendance',
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
        subject_name: selectedSubject.value,
        search: searchQuery.value,
        date: selectedDate.value,
        month: selectedMonth.value,
        start_date: selectedStartDate.value || '',
        end_date: selectedEndDate.value || '',
    });
    return `/reports/tutor-attendance/export?${params.toString()}`;
});

// ==========================================
// DATA CHART ANALITIK (SOFT & MENARIK)
// ==========================================

// 1. Tren Aktivitas Mengajar Guru Waktu ke Waktu (Line / Area SVG)
const trendChronologicalSessions = computed(() => {
    return [...props.session_logs]
        .reverse()
        .slice(-10); // Ambil 10 sesi terakhir secara kronologis
});

const trendMaxPresent = computed(() => {
    if (!trendChronologicalSessions.value.length) return 10;
    const max = Math.max(...trendChronologicalSessions.value.map(s => s.total_count));
    return max > 0 ? max : 10;
});

// Path Area SVG Halus
const trendAreaPath = computed(() => {
    const pts = trendChronologicalSessions.value;
    if (pts.length < 2) return '';
    const width = 500;
    const height = 110;
    const step = width / (pts.length - 1);

    let path = `M 0,${height} `;
    pts.forEach((p, idx) => {
        const x = idx * step;
        const normalized = p.total_count / trendMaxPresent.value;
        const y = height - (normalized * (height - 20)) - 10;
        path += `L ${x.toFixed(1)},${y.toFixed(1)} `;
    });
    path += `L ${width},${height} Z`;
    return path;
});

const trendLinePath = computed(() => {
    const pts = trendChronologicalSessions.value;
    if (pts.length < 2) return '';
    const width = 500;
    const height = 110;
    const step = width / (pts.length - 1);

    let path = '';
    pts.forEach((p, idx) => {
        const x = idx * step;
        const normalized = p.total_count / trendMaxPresent.value;
        const y = height - (normalized * (height - 20)) - 10;
        path += (idx === 0 ? 'M ' : 'L ') + `${x.toFixed(1)},${y.toFixed(1)} `;
    });
    return path;
});

// 2. Top Guru Paling Aktif Mengajar
const topActiveTutors = computed(() => {
    return [...props.tentors_recap]
        .filter(t => t.total_sessions > 0)
        .slice(0, 5);
});

const maxTutorSessions = computed(() => {
    if (!topActiveTutors.value.length) return 1;
    return Math.max(...topActiveTutors.value.map(t => t.total_sessions));
});
</script>

<template>
    <AuthenticatedLayout title="Rekapitulasi Kehadiran Guru">
        <Head title="Rekapitulasi Kehadiran Guru" />

        <div class="space-y-6 pb-12">
            <!-- ========================================== -->
            <!-- HEADER UTAMA & TOMBOL EXPORT EXCEL -->
            <!-- ========================================== -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/20">
                            <CalendarCheck class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Rekapitulasi Kehadiran Guru</h1>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kehadiran guru otomatis terhitung saat guru melakukan absensi pada sesi pertemuan kelas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Ekspor Laporan Excel diposisikan di paling kanan -->
                <div class="flex items-center gap-3 self-end md:self-center">
                    <a
                        :href="exportExcelUrl"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow-md transition-all active:scale-95"
                        target="_blank"
                    >
                        <Download class="h-4 w-4" />
                        <span>Ekspor Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- FILTER PERIODE, JENJANG, & MAPEL -->
            <!-- ========================================== -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                <!-- Baris 1: Filter Periode Cepat -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4 text-orange-600" />
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Periode Presensi</span>
                    </div>

                    <!-- Pills Selector Periode -->
                    <div class="flex flex-wrap items-center gap-1.5 bg-slate-100/80 p-1 rounded-xl">
                        <button
                            type="button"
                            @click="selectedPeriod = 'daily'; applyFilter()"
                            :class="selectedPeriod === 'daily' ? 'bg-white text-orange-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all"
                        >
                            Harian
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'weekly'; applyFilter()"
                            :class="selectedPeriod === 'weekly' ? 'bg-white text-orange-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all"
                        >
                            Mingguan
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'monthly'; applyFilter()"
                            :class="selectedPeriod === 'monthly' ? 'bg-white text-orange-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all"
                        >
                            Bulanan
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'all_time'; applyFilter()"
                            :class="selectedPeriod === 'all_time' ? 'bg-white text-orange-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all"
                        >
                            Sepanjang Waktu
                        </button>
                        <button
                            type="button"
                            @click="selectedPeriod = 'custom'; applyFilter()"
                            :class="selectedPeriod === 'custom' ? 'bg-white text-orange-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-all"
                        >
                            Kustom
                        </button>
                    </div>
                </div>

                <!-- Baris 2: Detail Tanggal & Dropdown Filter -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
                    <!-- Kondisional Input Tanggal Berdasarkan Periode -->
                    <div v-if="selectedPeriod === 'daily'" class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600">Pilih Tanggal</label>
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="applyFilter"
                            class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                        />
                    </div>

                    <div v-else-if="selectedPeriod === 'monthly'" class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600">Pilih Bulan</label>
                        <input
                            type="month"
                            v-model="selectedMonth"
                            @change="applyFilter"
                            class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                        />
                    </div>

                    <div v-else-if="selectedPeriod === 'custom'" class="sm:col-span-2 grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-slate-600">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="selectedStartDate"
                                @change="applyFilter"
                                class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-slate-600">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="selectedEndDate"
                                @change="applyFilter"
                                class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                            />
                        </div>
                    </div>

                    <div v-else class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600">Rentang Waktu</label>
                        <div class="h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl flex items-center text-slate-500 font-medium">
                            <Calendar class="h-3.5 w-3.5 text-slate-400 mr-2" />
                            <span>{{ selectedPeriod === 'weekly' ? '7 Hari Terakhir' : 'Seluruh Rekaman Historis' }}</span>
                        </div>
                    </div>

                    <!-- Filter Jenjang -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600">Jenjang Belajar</label>
                        <select
                            v-model="selectedEducationLevel"
                            @change="applyFilter"
                            class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                        >
                            <option value="all">Semua Jenjang</option>
                            <option v-for="lvl in props.education_levels" :key="lvl" :value="lvl">
                                Jenjang {{ lvl }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Mata Pelajaran / Spesialisasi dengan SearchableSelect -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600">Mata Pelajaran / Bidang</label>
                        <SearchableSelect
                            v-model="selectedSubject"
                            :options="subjectOptions"
                            placeholder="Semua Mata Pelajaran"
                            all-label="Semua Mata Pelajaran"
                            @change="applyFilter"
                        />
                    </div>

                    <!-- Tombol Reset Filter -->
                    <div class="space-y-1 flex flex-col justify-end">
                        <button
                            type="button"
                            @click="resetFilter"
                            class="h-9 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all flex items-center justify-center gap-2"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-500" />
                            <span>Reset Filter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4 KARTU METRIK KPI RINGKASAN -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- KPI 1: Total Guru Terdaftar -->
                <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 shrink-0">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Guru Aktif</p>
                        <h3 class="text-xl font-bold text-slate-800 mt-0.5">{{ props.kpi.total_active_tentors }} <span class="text-xs font-medium text-slate-400">Guru</span></h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Terdaftar di lembaga bimbel</p>
                    </div>
                </div>

                <!-- KPI 2: Guru Mengajar Periode Ini -->
                <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                        <UserCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Guru Hadir Mengajar</p>
                        <h3 class="text-xl font-bold text-slate-800 mt-0.5">{{ props.kpi.active_teaching_tentors }} <span class="text-xs font-medium text-slate-400">Guru</span></h3>
                        <p class="text-[10px] text-emerald-600 font-semibold mt-0.5">{{ props.kpi.overall_presence_rate }}% keaktifan periode ini</p>
                    </div>
                </div>

                <!-- KPI 3: Total Sesi Pertemuan Hadir -->
                <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                        <CalendarCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Sesi Pertemuan</p>
                        <h3 class="text-xl font-bold text-slate-800 mt-0.5">{{ props.kpi.total_sessions }} <span class="text-xs font-medium text-slate-400">Sesi</span></h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Rata-rata {{ props.kpi.avg_sessions_per_tentor }} sesi / guru aktif</p>
                    </div>
                </div>

                <!-- KPI 4: Total Siswa Diajar -->
                <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Siswa Diajar</p>
                        <h3 class="text-xl font-bold text-slate-800 mt-0.5">{{ props.kpi.total_students_served }} <span class="text-xs font-medium text-slate-400">Siswa</span></h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Presensi kehadiran tercatat</p>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- VISUAL SOFT ANALYTICS CHART SECTION -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Chart 1: Tren Aktivitas Mengajar Waktu ke Waktu (2 Kolom) -->
                <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <TrendingUp class="w-4 h-4 text-orange-600" />
                                <span>Tren Sesi Pertemuan Kelas Mengajar</span>
                            </h3>
                            <p class="text-[11px] text-slate-400">Volume aktivitas sesi mengajar guru terkini</p>
                        </div>
                        <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 px-2.5 py-1 rounded-full">
                            Kronologis Terkini
                        </span>
                    </div>

                    <!-- SVG Chart -->
                    <div v-if="trendChronologicalSessions.length >= 2" class="relative pt-2">
                        <svg class="w-full h-36 overflow-visible" viewBox="0 0 500 110" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="tutorOrangeGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#EA580C" stop-opacity="0.25" />
                                    <stop offset="100%" stop-color="#EA580C" stop-opacity="0.01" />
                                </linearGradient>
                            </defs>
                            <!-- Grid lines horizontal -->
                            <line x1="0" y1="20" x2="500" y2="20" stroke="#F1F5F9" stroke-dasharray="3 3" />
                            <line x1="0" y1="60" x2="500" y2="60" stroke="#F1F5F9" stroke-dasharray="3 3" />
                            <line x1="0" y1="100" x2="500" y2="100" stroke="#F1F5F9" stroke-dasharray="3 3" />

                            <!-- Area Path -->
                            <path :d="trendAreaPath" fill="url(#tutorOrangeGrad)" />

                            <!-- Line Path -->
                            <path :d="trendLinePath" fill="none" stroke="#EA580C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <!-- Labels axis -->
                        <div class="flex justify-between items-center text-[10px] text-slate-400 mt-2 px-1">
                            <span>{{ trendChronologicalSessions[0]?.formatted_date }}</span>
                            <span class="text-orange-600 font-semibold">Total {{ trendChronologicalSessions.length }} Pertemuan Terakhir</span>
                            <span>{{ trendChronologicalSessions[trendChronologicalSessions.length - 1]?.formatted_date }}</span>
                        </div>
                    </div>

                    <div v-else class="h-36 flex flex-col items-center justify-center text-slate-400 text-xs">
                        <Calendar class="w-8 h-8 text-slate-300 mb-2" />
                        <span>Data sesi pertemuan belum cukup untuk menampilkan grafik tren.</span>
                    </div>
                </div>

                <!-- Chart 2: Guru Paling Aktif Mengajar (1 Kolom) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <Award class="w-4 h-4 text-amber-500" />
                                <span>Guru Paling Aktif</span>
                            </h3>
                            <span class="text-[10px] font-semibold bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full">
                                Top Hadir
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-4">Jumlah pertemuan mengajar periode ini</p>

                        <div class="space-y-3">
                            <div v-for="tut in topActiveTutors" :key="tut.id" class="space-y-1">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-semibold text-slate-700 truncate max-w-[170px]" :title="tut.full_name">{{ tut.full_name }}</span>
                                    <span class="text-[11px] font-bold text-orange-600">{{ tut.total_sessions }} Sesi</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-orange-500 to-amber-500 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.round((tut.total_sessions / maxTutorSessions) * 100)}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div v-if="!topActiveTutors.length" class="py-8 text-center text-xs text-slate-400">
                                Belum ada guru yang mengajar pada filter ini.
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 mt-4 flex items-center justify-between text-[11px] text-slate-500">
                        <span>Total Guru Mengajar</span>
                        <span class="font-bold text-slate-800">{{ props.kpi.active_teaching_tentors }} Guru</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- NAVIGASI TAB UTAMA (REKAP GURU & LOG SESI) -->
            <!-- ========================================== -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex border-b border-slate-100 px-6 pt-3 bg-slate-50/50">
                    <button
                        type="button"
                        @click="activeTab = 'recap'"
                        class="pb-3 px-4 text-xs font-bold transition-all relative border-b-2 flex items-center gap-2"
                        :class="activeTab === 'recap' ? 'border-orange-600 text-orange-600 bg-white rounded-t-xl -mb-[1px] shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        <Users class="h-4 w-4" />
                        <span>Rekapitulasi Kehadiran Guru ({{ props.tentors_recap.length }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'sessions'"
                        class="pb-3 px-4 text-xs font-bold transition-all relative border-b-2 flex items-center gap-2"
                        :class="activeTab === 'sessions' ? 'border-orange-600 text-orange-600 bg-white rounded-t-xl -mb-[1px] shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        <CalendarCheck class="h-4 w-4" />
                        <span>Log Sesi Pertemuan Kelas oleh Guru ({{ props.session_logs.length }})</span>
                    </button>
                </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 1: REKAPITULASI GURU -->
                <!-- ========================================== -->
                <div v-if="activeTab === 'recap'" class="p-6 space-y-4">
                    <!-- Baris Pencarian Khusus Nama Guru -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/70 p-3 rounded-xl border border-slate-100">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                type="text"
                                v-model="searchQuery"
                                @input="onSearchInput"
                                placeholder="Cari nama guru atau bidang spesialisasi..."
                                class="w-full h-9 pl-9 pr-4 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 placeholder:text-slate-400 font-medium"
                            />
                        </div>
                        <span class="text-xs text-slate-500 font-medium self-end sm:self-center">
                            Menampilkan {{ paginatedTentors.length }} dari total {{ props.tentors_recap.length }} guru
                        </span>
                    </div>

                    <!-- Tabel Data Rekap Guru -->
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 text-slate-600 font-semibold border-b border-slate-100">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Nama Lengkap Guru / Tentor</th>
                                    <th class="py-3 px-4">Spesialisasi / Mapel</th>
                                    <th class="py-3 px-4 text-center">Kehadiran Mengajar</th>
                                    <th class="py-3 px-4 text-center">Siswa Diajar</th>
                                    <th class="py-3 px-4 text-center">Rata-rata Presensi Kelas</th>
                                    <th class="py-3 px-4 text-center">Terakhir Mengajar</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(tentor, index) in paginatedTentors"
                                    :key="tentor.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="py-3 px-4 text-center font-medium text-slate-400">
                                        {{ (tentorCurrentPage - 1) * tentorPerPage + index + 1 }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                <img
                                                    v-if="tentor.photo_url"
                                                    :src="tentor.photo_url"
                                                    alt="Photo"
                                                    class="h-8 w-8 rounded-full object-cover"
                                                />
                                                <span v-else>{{ tentor.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800">{{ tentor.full_name }}</p>
                                                <p class="text-[10px] text-slate-400">{{ tentor.email || '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-700">
                                            <BookOpen class="h-3 w-3 text-slate-400" />
                                            <span>{{ tentor.specialization || 'Umum' }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="tentor.total_sessions > 0 ? 'bg-orange-50 text-orange-600 ring-1 ring-orange-200' : 'bg-slate-100 text-slate-400'"
                                        >
                                            <CheckCircle2 v-if="tentor.total_sessions > 0" class="h-3 w-3" />
                                            {{ tentor.total_sessions }} Sesi Hadir
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-semibold text-slate-700">
                                        {{ tentor.total_students_taught }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div v-if="tentor.total_sessions > 0" class="inline-flex flex-col items-center">
                                            <span class="font-bold text-xs" :class="tentor.avg_class_attendance_rate >= 80 ? 'text-emerald-600' : 'text-amber-600'">
                                                {{ tentor.avg_class_attendance_rate }}%
                                            </span>
                                            <div class="w-16 h-1.5 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full"
                                                    :class="tentor.avg_class_attendance_rate >= 80 ? 'bg-emerald-500' : 'bg-amber-500'"
                                                    :style="{ width: `${Math.min(100, tentor.avg_class_attendance_rate)}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-300">-</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-medium text-slate-500 text-[11px]">
                                        {{ tentor.last_session_date_formatted || '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold inline-block"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200': tentor.status_label === 'Sangat Aktif',
                                                'bg-blue-50 text-blue-700 ring-1 ring-blue-200': tentor.status_label === 'Aktif Mengajar',
                                                'bg-amber-50 text-amber-700 ring-1 ring-amber-200': tentor.status_label === 'Cukup Aktif',
                                                'bg-slate-100 text-slate-400': tentor.status_label === 'Belum Ada Sesi',
                                            }"
                                        >
                                            {{ tentor.status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button
                                            type="button"
                                            @click="openTentorDetailModal(tentor)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-white hover:bg-orange-50 text-orange-600 border border-orange-200 hover:border-orange-300 rounded-lg text-xs font-semibold shadow-2xs transition-all active:scale-95"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span>Rincian</span>
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="!paginatedTentors.length">
                                    <td colspan="9" class="py-12 text-center text-slate-400">
                                        <Users class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                        <p class="text-sm font-medium">Tidak ada data guru yang cocok dengan filter yang dipilih.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginasi Compact 3-Angka untuk Rekap Guru -->
                    <div v-if="totalTentorPages > 1" class="flex items-center justify-between pt-2">
                        <p class="text-xs text-slate-500">
                            Halaman <span class="font-bold text-slate-800">{{ tentorCurrentPage }}</span> dari {{ totalTentorPages }}
                        </p>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="tentorCurrentPage <= 1"
                                @click="tentorCurrentPage--"
                                class="p-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <button
                                v-for="page in visibleTentorPages"
                                :key="page"
                                type="button"
                                @click="tentorCurrentPage = page"
                                :class="tentorCurrentPage === page ? 'bg-orange-600 text-white font-bold border-orange-600' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'"
                                class="h-8 w-8 rounded-lg border text-xs transition-all flex items-center justify-center font-medium"
                            >
                                {{ page }}
                            </button>

                            <button
                                type="button"
                                :disabled="tentorCurrentPage >= totalTentorPages"
                                @click="tentorCurrentPage++"
                                class="p-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 2: LOG SESI PERTEMUAN GURU -->
                <!-- ========================================== -->
                <div v-else class="p-6 space-y-4">
                    <!-- Baris Pencarian Log Sesi -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/70 p-3 rounded-xl border border-slate-100">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                type="text"
                                v-model="sessionSearchQuery"
                                placeholder="Cari nama guru, mata pelajaran, kelompok bimbel..."
                                class="w-full h-9 pl-9 pr-4 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 placeholder:text-slate-400 font-medium"
                            />
                        </div>
                        <span class="text-xs text-slate-500 font-medium self-end sm:self-center">
                            Menampilkan {{ paginatedSessions.length }} dari total {{ filteredSessions.length }} sesi
                        </span>
                    </div>

                    <!-- Tabel Log Sesi -->
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 text-slate-600 font-semibold border-b border-slate-100">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Tanggal & Jam</th>
                                    <th class="py-3 px-4">Guru / Tentor Pengampu</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4">Kelompok Bimbel</th>
                                    <th class="py-3 px-4 text-center">Jenjang</th>
                                    <th class="py-3 px-4 text-center">Presensi Siswa</th>
                                    <th class="py-3 px-4 text-center">Dokumentasi</th>
                                    <th class="py-3 px-4 text-center">Status Sesi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(session, index) in paginatedSessions"
                                    :key="session.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="py-3 px-4 text-center font-medium text-slate-400">
                                        {{ (sessionCurrentPage - 1) * sessionPerPage + index + 1 }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-3.5 w-3.5 text-orange-600 shrink-0" />
                                            <div>
                                                <p class="font-bold text-slate-800">{{ session.formatted_date }}</p>
                                                <p class="text-[10px] text-slate-400 flex items-center gap-1">
                                                    <Clock class="h-2.5 w-2.5" />
                                                    <span>{{ session.time }} WIB</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-slate-800">{{ session.tentor_name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ session.specialization }}</p>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-700">
                                        {{ session.subject_name }}
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-700">
                                        {{ session.study_group_name }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ session.education_level }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="inline-flex items-center gap-1.5 text-xs font-medium">
                                            <span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-md">
                                                {{ session.present_count }} Hadir
                                            </span>
                                            <span v-if="session.absent_count > 0" class="text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-md">
                                                {{ session.absent_count }} Alpa
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button
                                            v-if="session.photo_url"
                                            type="button"
                                            @click="openPhotoModal(session.photo_url, `${session.subject_name} - ${session.study_group_name}`)"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-600 rounded-lg text-[11px] font-medium transition-colors"
                                        >
                                            <Camera class="h-3 w-3" />
                                            <span>Foto</span>
                                        </button>
                                        <span v-else class="text-slate-300 text-[11px]">-</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                            <CheckCircle2 class="h-3 w-3" />
                                            Terlaksana & Hadir
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="!paginatedSessions.length">
                                    <td colspan="9" class="py-12 text-center text-slate-400">
                                        <CalendarCheck class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                        <p class="text-sm font-medium">Tidak ada log sesi pertemuan yang cocok dengan filter yang dipilih.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginasi Compact 3-Angka untuk Log Sesi -->
                    <div v-if="totalSessionPages > 1" class="flex items-center justify-between pt-2">
                        <p class="text-xs text-slate-500">
                            Halaman <span class="font-bold text-slate-800">{{ sessionCurrentPage }}</span> dari {{ totalSessionPages }}
                        </p>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="sessionCurrentPage <= 1"
                                @click="sessionCurrentPage--"
                                class="p-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <button
                                v-for="page in visibleSessionPages"
                                :key="page"
                                type="button"
                                @click="sessionCurrentPage = page"
                                :class="sessionCurrentPage === page ? 'bg-orange-600 text-white font-bold border-orange-600' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200'"
                                class="h-8 w-8 rounded-lg border text-xs transition-all flex items-center justify-center font-medium"
                            >
                                {{ page }}
                            </button>

                            <button
                                type="button"
                                :disabled="sessionCurrentPage >= totalSessionPages"
                                @click="sessionCurrentPage++"
                                class="p-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL DETAIL RINCIAN GURU -->
        <!-- ========================================== -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="selectedTentorForModal"
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4"
                @click.self="closeTentorDetailModal"
            >
                <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-sm">
                                <img
                                    v-if="selectedTentorForModal.photo_url"
                                    :src="selectedTentorForModal.photo_url"
                                    alt="Photo"
                                    class="h-10 w-10 rounded-xl object-cover"
                                />
                                <span v-else>{{ selectedTentorForModal.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 leading-snug">{{ selectedTentorForModal.full_name }}</h3>
                                <p class="text-xs text-slate-500">Spesialisasi: {{ selectedTentorForModal.specialization || 'Umum' }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeTentorDetailModal"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-4">
                        <!-- Stat Mini Guru -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-orange-50/60 rounded-xl border border-orange-100 text-center">
                                <p class="text-[10px] font-semibold text-orange-700 uppercase">Total Sesi Hadir</p>
                                <p class="text-lg font-bold text-orange-700 mt-0.5">{{ selectedTentorForModal.total_sessions }} Sesi</p>
                            </div>
                            <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100 text-center">
                                <p class="text-[10px] font-semibold text-emerald-700 uppercase">Total Siswa Diajar</p>
                                <p class="text-lg font-bold text-emerald-700 mt-0.5">{{ selectedTentorForModal.total_students_taught }} Siswa</p>
                            </div>
                            <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 text-center">
                                <p class="text-[10px] font-semibold text-blue-700 uppercase">Rata-rata Presensi</p>
                                <p class="text-lg font-bold text-blue-700 mt-0.5">{{ selectedTentorForModal.avg_class_attendance_rate }}%</p>
                            </div>
                        </div>

                        <!-- Riwayat Sesi Pertemuan Guru -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Riwayat Pertemuan Mengajar Kelas</h4>
                            <div class="border border-slate-100 rounded-xl overflow-hidden divide-y divide-slate-100">
                                <div
                                    v-for="item in selectedTentorForModal.session_history"
                                    :key="item.id"
                                    class="p-3.5 hover:bg-slate-50/80 transition-colors flex items-start justify-between gap-3 text-xs"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800">{{ item.formatted_date }}</span>
                                            <span class="text-[11px] text-slate-400">({{ item.time }} WIB)</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                                {{ item.education_level }}
                                            </span>
                                        </div>
                                        <p class="font-medium text-slate-700">{{ item.subject_name }} &bull; Kelompok {{ item.study_group_name }}</p>
                                        <p v-if="item.topic_description" class="text-[11px] text-slate-500 italic">
                                            "{{ item.topic_description }}"
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full block">
                                            {{ item.present_count }} Siswa Hadir
                                        </span>
                                        <button
                                            v-if="item.photo_url"
                                            type="button"
                                            @click="openPhotoModal(item.photo_url, `${item.subject_name} - ${item.study_group_name}`)"
                                            class="text-[10px] text-orange-600 font-semibold hover:underline mt-1 inline-flex items-center gap-1"
                                        >
                                            <Camera class="h-3 w-3" />
                                            <span>Foto</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-if="!selectedTentorForModal.session_history.length" class="p-6 text-center text-slate-400 text-xs">
                                    Belum ada catatan sesi pertemuan yang dilakukan oleh guru ini pada rentang waktu yang dipilih.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 border-t border-slate-100 flex justify-end bg-slate-50/50">
                        <button
                            type="button"
                            @click="closeTentorDetailModal"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- MODAL PHOTO PREVIEW DOKUMENTASI -->
        <!-- ========================================== -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="previewPhotoUrl"
                class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4"
                @click.self="closePhotoModal"
            >
                <div class="bg-white rounded-2xl max-w-xl w-full overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-800 truncate">{{ previewPhotoTitle }}</span>
                        <button
                            type="button"
                            @click="closePhotoModal"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="p-2 bg-slate-950 flex items-center justify-center">
                        <img
                            :src="previewPhotoUrl"
                            alt="Dokumentasi Pertemuan"
                            class="max-h-[70vh] w-auto object-contain rounded-lg"
                        />
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

