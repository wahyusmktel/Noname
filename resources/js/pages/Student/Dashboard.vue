<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Heart,
    GraduationCap,
    CalendarCheck,
    CheckCircle2,
    XCircle,
    TrendingUp,
    Sparkles,
    BookOpen,
    Camera,
    Clock,
    User,
    Calendar,
    Award,
    Lightbulb,
    Search,
    Filter,
    X,
    Eye,
    ChevronRight,
    Flame,
    Share2,
    ShieldCheck,
    HelpCircle
} from 'lucide-vue-next';

interface StudentProfile {
    id: string;
    name: string;
    username: string;
    nis?: string | null;
    photo_url?: string | null;
    study_group_name: string;
    education_level: string;
    academic_year_name: string;
    parent_phone?: string | null;
}

interface BadgeInfo {
    title: string;
    grade: string;
    color: string;
    description: string;
}

interface KPIInfo {
    total_sessions: number;
    present_count: number;
    absent_count: number;
    attendance_rate: number;
    consecutive_streak: number;
    badge: BadgeInfo;
    latest_session?: {
        date: string;
        subject: string;
        tentor: string;
        status: string;
    } | null;
}

interface AttendanceHistoryItem {
    id: string;
    session_id: string;
    date: string;
    formatted_date: string;
    time: string;
    subject_name: string;
    tentor_name: string;
    study_group_name: string;
    education_level: string;
    topic_description: string;
    status: 'present' | 'absent';
    status_label: string;
    notes?: string | null;
    photo_url?: string | null;
}

interface GalleryItem {
    id: string;
    photo_url: string;
    title: string;
    date: string;
    topic: string;
    tentor: string;
    student_status: string;
}

interface SubjectAnalysisItem {
    subject_name: string;
    total_sessions: number;
    present_count: number;
    absent_count: number;
    attendance_rate: number;
}

interface TenantInfo {
    id: string;
    name: string;
    brand_color?: string | null;
    address?: string | null;
    phone?: string | null;
}

interface Props {
    student: StudentProfile | null;
    kpi: KPIInfo | null;
    attendance_history: AttendanceHistoryItem[];
    gallery: GalleryItem[];
    subjects_analysis: SubjectAnalysisItem[];
    subjects_list: string[];
    tenant?: TenantInfo | null;
}

const props = defineProps<Props>();

// ==========================================
// STATE TAB MONITORING ORANG TUA
// ==========================================
// 'overview' (Ringkasan & Analisa) | 'journal' (Buku Jurnal Presensi) | 'gallery' (Galeri Momen Belajar)
const activeTab = ref<'overview' | 'journal' | 'gallery'>('overview');

// ==========================================
// FILTER JURNAL PRESENSI
// ==========================================
const selectedSubjectFilter = ref<string>('all');
const selectedStatusFilter = ref<string>('all'); // 'all', 'present', 'absent'
const searchJournalQuery = ref<string>('');

const filteredJournal = computed(() => {
    return props.attendance_history.filter((item) => {
        // Filter Mapel
        if (selectedSubjectFilter.value !== 'all' && item.subject_name !== selectedSubjectFilter.value) {
            return false;
        }
        // Filter Status
        if (selectedStatusFilter.value !== 'all' && item.status !== selectedStatusFilter.value) {
            return false;
        }
        // Filter Pencarian
        if (searchJournalQuery.value.trim()) {
            const q = searchJournalQuery.value.toLowerCase();
            const match =
                item.subject_name.toLowerCase().includes(q) ||
                item.topic_description.toLowerCase().includes(q) ||
                item.tentor_name.toLowerCase().includes(q) ||
                item.formatted_date.toLowerCase().includes(q);
            if (!match) return false;
        }
        return true;
    });
});

// ==========================================
// MODAL PREVIEW FOTO DOKUMENTASI
// ==========================================
const previewPhoto = ref<{
    url: string;
    title: string;
    date: string;
    topic: string;
    tentor: string;
} | null>(null);

const openPhotoPreview = (item: { photo_url?: string | null; title?: string; formatted_date?: string; date?: string; topic_description?: string; topic?: string; tentor_name?: string; tentor?: string; subject_name?: string }) => {
    if (!item.photo_url) return;
    previewPhoto.value = {
        url: item.photo_url,
        title: item.title || item.subject_name || 'Dokumentasi Kelas',
        date: item.formatted_date || item.date || '',
        topic: item.topic_description || item.topic || '',
        tentor: item.tentor_name || item.tentor || 'Tentor Bimbel',
    };
};

const closePhotoPreview = () => {
    previewPhoto.value = null;
};

// Pindah tab dengan scroll halus (terutama jika diakses di smartphone)
const switchTab = (tab: 'overview' | 'journal' | 'gallery') => {
    activeTab.value = tab;
    const el = document.getElementById('parent-monitoring-tabs');
    if (el) {
        const yOffset = -75;
        const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
};
</script>

<template>
    <AuthenticatedLayout title="Ruang Pantau Belajar Ananda">
        <Head title="Ruang Pantau Belajar Ananda - Monitoring Kehadiran" />

        <div class="space-y-6 pb-24 sm:pb-12">
            <!-- ========================================== -->
            <!-- STATE JIKA DATA SISWA BELUM TERSEDIA -->
            <!-- ========================================== -->
            <div v-if="!student" class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm text-center max-w-xl mx-auto space-y-4">
                <div class="h-16 w-16 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mx-auto">
                    <HelpCircle class="h-8 w-8" />
                </div>
                <h2 class="text-lg font-bold text-slate-800">Akun Siswa Sedang Dalam Proses Penyiapan</h2>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Data peserta didik untuk akun ini belum dipetakan ke profil siswa aktif. Silakan hubungi admin lembaga bimbingan belajar untuk konfirmasi data ananda.
                </p>
            </div>

            <!-- ========================================== -->
            <!-- HEADER UTAMA HANGAT & KARTU IDENTITAS ANANDA -->
            <!-- ========================================== -->
            <div v-else class="space-y-6">
                <!-- Banner Sambutan untuk Orang Tua -->
                <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15">
                    <!-- Background Pattern Hiasan -->
                    <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute right-24 top-4 w-32 h-32 bg-amber-300/20 rounded-full blur-xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <!-- Sapaan Hangat -->
                        <div class="space-y-2 max-w-xl">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-[11px] font-semibold text-white">
                                <Heart class="h-3.5 w-3.5 fill-white/80 text-white" />
                                <span>Portal Pantau Kasih Orang Tua</span>
                            </div>
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight leading-snug">
                                Halo, Ayah & Bunda dari Ananda {{ student.name }}! 👋
                            </h1>
                            <p class="text-xs sm:text-sm text-orange-50/90 leading-relaxed">
                                Pantau terus kedisiplinan dan semangat belajar ananda di <span class="font-bold underline decoration-white/40">{{ tenant?.name ?? 'Bimbel' }}</span>. Setiap langkah kecil belajarnya hari ini adalah pintu masa depan terbaiknya.
                            </p>
                        </div>

                        <!-- Kartu Identitas Ringkas Ananda -->
                        <div class="bg-white/15 backdrop-blur-md border border-white/25 p-4 sm:p-5 rounded-2xl flex items-center gap-4 shrink-0">
                            <!-- Avatar Foto / Inisial -->
                            <div class="relative h-14 w-14 rounded-2xl bg-white text-orange-600 flex items-center justify-center font-black text-xl shadow-md overflow-hidden ring-4 ring-white/30">
                                <img
                                    v-if="student.photo_url"
                                    :src="student.photo_url"
                                    :alt="student.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ student.name.charAt(0).toUpperCase() }}</span>
                            </div>

                            <!-- Detail Siswa -->
                            <div class="text-left space-y-0.5">
                                <span class="text-[10px] font-bold tracking-wider uppercase text-orange-200">Peserta Didik</span>
                                <h3 class="font-black text-sm sm:text-base text-white truncate max-w-[200px]">{{ student.name }}</h3>
                                <p class="text-[11px] text-orange-100 font-medium">
                                    {{ student.study_group_name }} &bull; Jenjang {{ student.education_level }}
                                </p>
                                <span class="inline-block text-[10px] bg-white/20 px-2 py-0.5 rounded-md font-semibold text-white mt-1">
                                    NIS: {{ student.username }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- KARTU PRESTASI & 4 METRIK KEHADIRAN -->
                <!-- ========================================== -->
                <div v-if="kpi" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Kartu Bintang Apresiasi & Motivasi (1 Kolom Besar) -->
                    <div
                        class="p-6 rounded-3xl border shadow-sm flex flex-col justify-between transition-all"
                        :class="{
                            'bg-gradient-to-br from-amber-50 to-orange-50/50 border-amber-200/80': kpi.badge.color === 'amber',
                            'bg-gradient-to-br from-emerald-50 to-teal-50/50 border-emerald-200/80': kpi.badge.color === 'emerald',
                            'bg-gradient-to-br from-blue-50 to-indigo-50/50 border-blue-200/80': kpi.badge.color === 'blue',
                            'bg-gradient-to-br from-rose-50 to-orange-50/50 border-rose-200/80': kpi.badge.color === 'rose',
                            'bg-gradient-to-br from-slate-50 to-slate-100/50 border-slate-200': kpi.badge.color === 'slate',
                        }"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full"
                                    :class="{
                                        'bg-amber-500 text-white': kpi.badge.color === 'amber',
                                        'bg-emerald-600 text-white': kpi.badge.color === 'emerald',
                                        'bg-blue-600 text-white': kpi.badge.color === 'blue',
                                        'bg-rose-600 text-white': kpi.badge.color === 'rose',
                                        'bg-slate-600 text-white': kpi.badge.color === 'slate',
                                    }"
                                >
                                    {{ kpi.badge.grade }}
                                </span>

                                <!-- Streak Indikator Api -->
                                <div
                                    v-if="kpi.consecutive_streak > 0"
                                    class="flex items-center gap-1 text-[11px] font-bold text-orange-600 bg-orange-100/80 px-2 py-0.5 rounded-full"
                                    title="Streak Kehadiran Terkini"
                                >
                                    <Flame class="h-3.5 w-3.5 fill-orange-500 text-orange-500 animate-bounce" />
                                    <span>{{ kpi.consecutive_streak }}x Berturut-turut</span>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-black text-slate-800 leading-snug">{{ kpi.badge.title }}</h3>
                                <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                                    {{ kpi.badge.description }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-200/60 mt-4 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Tingkat Disiplin Belajar</span>
                            <span class="font-black text-base text-slate-800">{{ kpi.attendance_rate }}%</span>
                        </div>
                    </div>

                    <!-- 4 Kotak Ringkasan Cepat Kehadiran (2 Kolom) -->
                    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                        <!-- Total Sesi -->
                        <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <CalendarCheck class="h-5 w-5" />
                            </div>
                            <div class="mt-3">
                                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Sesi</p>
                                <h4 class="text-xl font-black text-slate-800 mt-0.5">{{ kpi.total_sessions }} <span class="text-xs font-medium text-slate-400">Pertemuan</span></h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Sesi kelas tercatat</p>
                            </div>
                        </div>

                        <!-- Hadir -->
                        <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                            <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <div class="mt-3">
                                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Hadir di Kelas</p>
                                <h4 class="text-xl font-black text-emerald-600 mt-0.5">{{ kpi.present_count }} <span class="text-xs font-medium text-slate-400">Kali</span></h4>
                                <p class="text-[10px] text-emerald-700/80 font-semibold mt-0.5">Semangat belajar!</p>
                            </div>
                        </div>

                        <!-- Tidak Hadir -->
                        <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                            <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                                <XCircle class="h-5 w-5" />
                            </div>
                            <div class="mt-3">
                                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tidak Hadir</p>
                                <h4 class="text-xl font-black text-rose-600 mt-0.5">{{ kpi.absent_count }} <span class="text-xs font-medium text-slate-400">Kali</span></h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ kpi.absent_count === 0 ? 'Nol absensi!' : 'Perlu diperhatikan' }}</p>
                            </div>
                        </div>

                        <!-- % Kehadiran -->
                        <div class="bg-white p-4.5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                            <div class="h-10 w-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                <TrendingUp class="h-5 w-5" />
                            </div>
                            <div class="mt-3">
                                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Persentase</p>
                                <h4 class="text-xl font-black text-orange-600 mt-0.5">{{ kpi.attendance_rate }}%</h4>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full mt-1.5 overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-orange-500 to-amber-500 rounded-full transition-all duration-500"
                                        :style="{ width: `${Math.min(100, kpi.attendance_rate)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- NAVIGASI 3 MENU UTAMA ORANG TUA (ANTI-GESER / BEBAS OVERFLOW) -->
                <!-- ========================================== -->
                <div id="parent-monitoring-tabs" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden scroll-mt-20">
                    <!-- Segmented Control Bar: 3 Kolom Penuh (Bebas Geser di HP) -->
                    <div class="p-2 sm:p-3 bg-slate-50/80 border-b border-slate-100">
                        <div class="grid grid-cols-3 gap-1.5 sm:gap-2.5 p-1 sm:p-1.5 bg-slate-200/60 rounded-2xl">
                            <!-- Tab 1: Ringkasan & Analisa -->
                            <button
                                type="button"
                                @click="switchTab('overview')"
                                class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2.5 sm:py-3 px-1.5 sm:px-4 rounded-xl text-xs transition-all duration-200 relative text-center"
                                :class="activeTab === 'overview' 
                                    ? 'bg-white text-orange-600 shadow-sm font-black ring-1 ring-slate-900/5' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/50 font-bold'"
                            >
                                <Sparkles class="h-4 w-4 shrink-0" :class="activeTab === 'overview' ? 'text-orange-600' : 'text-slate-400'" />
                                <span class="truncate">
                                    <span class="sm:hidden">Analisis</span>
                                    <span class="hidden sm:inline">Ringkasan & Analisa</span>
                                </span>
                            </button>

                            <!-- Tab 2: Buku Jurnal Presensi -->
                            <button
                                type="button"
                                @click="switchTab('journal')"
                                class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2.5 sm:py-3 px-1.5 sm:px-4 rounded-xl text-xs transition-all duration-200 relative text-center"
                                :class="activeTab === 'journal' 
                                    ? 'bg-white text-orange-600 shadow-sm font-black ring-1 ring-slate-900/5' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/50 font-bold'"
                            >
                                <BookOpen class="h-4 w-4 shrink-0" :class="activeTab === 'journal' ? 'text-orange-600' : 'text-slate-400'" />
                                <span class="truncate">
                                    <span class="sm:hidden">Jurnal</span>
                                    <span class="hidden sm:inline">Buku Jurnal</span>
                                </span>
                                <span
                                    class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                                    :class="activeTab === 'journal' ? 'bg-orange-100 text-orange-700' : 'bg-slate-200 text-slate-600'"
                                >
                                    {{ attendance_history.length }}
                                </span>
                            </button>

                            <!-- Tab 3: Galeri Momen Belajar -->
                            <button
                                type="button"
                                @click="switchTab('gallery')"
                                class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2.5 sm:py-3 px-1.5 sm:px-4 rounded-xl text-xs transition-all duration-200 relative text-center"
                                :class="activeTab === 'gallery' 
                                    ? 'bg-white text-orange-600 shadow-sm font-black ring-1 ring-slate-900/5' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/50 font-bold'"
                            >
                                <Camera class="h-4 w-4 shrink-0" :class="activeTab === 'gallery' ? 'text-orange-600' : 'text-slate-400'" />
                                <span class="truncate">
                                    <span class="sm:hidden">Galeri Foto</span>
                                    <span class="hidden sm:inline">Galeri Foto</span>
                                </span>
                                <span
                                    class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                                    :class="activeTab === 'gallery' ? 'bg-orange-500 text-white' : 'bg-orange-100 text-orange-700'"
                                >
                                    {{ gallery.length }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- KONTEN TAB 1: RINGKASAN & ANALISA -->
                    <!-- ========================================== -->
                    <div v-if="activeTab === 'overview'" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Analisis Disiplin Kehadiran per Mata Pelajaran -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                            <BookOpen class="h-4 w-4 text-orange-600" />
                                            <span>Kehadiran per Mata Pelajaran</span>
                                        </h3>
                                        <p class="text-xs text-slate-400 mt-0.5">Tingkat kehadiran ananda di setiap mata pelajaran</p>
                                    </div>
                                    <span class="text-[10px] font-bold bg-orange-50 text-orange-600 px-2 py-0.5 rounded-full">
                                        {{ subjects_analysis.length }} Mata Pelajaran
                                    </span>
                                </div>

                                <div class="space-y-3 pt-1">
                                    <div
                                        v-for="subj in subjects_analysis"
                                        :key="subj.subject_name"
                                        class="p-3.5 bg-slate-50/80 hover:bg-slate-50 rounded-2xl border border-slate-100 transition-colors space-y-2"
                                    >
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-800">{{ subj.subject_name }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] text-slate-500 font-medium">
                                                    {{ subj.present_count }}/{{ subj.total_sessions }} Sesi
                                                </span>
                                                <span
                                                    class="font-extrabold text-xs px-2 py-0.5 rounded-md"
                                                    :class="subj.attendance_rate >= 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                                >
                                                    {{ subj.attendance_rate }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div class="w-full h-2 bg-slate-200/70 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="subj.attendance_rate >= 80 ? 'bg-emerald-500' : 'bg-amber-500'"
                                                :style="{ width: `${subj.attendance_rate}%` }"
                                            ></div>
                                        </div>
                                    </div>

                                    <div v-if="!subjects_analysis.length" class="py-8 text-center text-slate-400 text-xs">
                                        Belum ada data mata pelajaran yang tercatat.
                                    </div>
                                </div>
                            </div>

                            <!-- Tips Pendampingan Belajar untuk Orang Tua -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                        <Lightbulb class="h-4 w-4 text-amber-500" />
                                        <span>Tips Pendampingan Kasih Orang Tua</span>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Saran praktis mendukung kebiasaan belajar ananda</p>
                                </div>

                                <div class="space-y-3">
                                    <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-100 flex gap-3.5 items-start">
                                        <div class="h-8 w-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-black text-xs">
                                            1
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-800">Tanyakan Hal Menarik Hari Ini</h4>
                                            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                                Cukup luangkan waktu 5 menit saat ananda pulang bimbel: <em>"Hari ini di kelas ada materi seru apa, Nak?"</em>. Pertanyaan ini memicu ananda mengingat kembali materi yang dipelajari.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 flex gap-3.5 items-start">
                                        <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-black text-xs">
                                            2
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-800">Apresiasi Usaha, Bukan Hanya Nilai</h4>
                                            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                                Puji ketekunannya ketika hadir tepat waktu dan antusias belajar. Rasa dihargai membangun motivasi internal yang tahan lama.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 flex gap-3.5 items-start">
                                        <div class="h-8 w-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 font-black text-xs">
                                            3
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-800">Komunikasi Terbuka dengan Bimbel</h4>
                                            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                                Jika ananda merasa kesulitan di suatu bab atau berhalangan hadir karena sakit, segera koordinasikan dengan tentor atau admin bimbel agar mendapat materi susulan.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- KARTU PANGGILAN CEPAT KE GALERI & JURNAL (AGAR ORANG TUA MUDAH MEMBUKA) -->
                        <!-- ========================================== -->
                        <div class="pt-4 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Card Quick Jump: Galeri Foto Kelas -->
                            <div
                                @click="switchTab('gallery')"
                                class="group p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-white border border-orange-200/70 hover:border-orange-300 hover:shadow-md transition-all cursor-pointer flex flex-col justify-between"
                            >
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-orange-700 font-extrabold text-xs uppercase tracking-wider">
                                            <Camera class="h-4 w-4" />
                                            <span>Momen Belajar Ananda</span>
                                        </div>
                                        <span class="bg-orange-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-xs">
                                            {{ gallery.length }} Foto Tersedia
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-800">
                                            Buka Galeri Foto Dokumentasi Kelas
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                            Lihat langsung keceriaan dan fokus ananda saat belajar tatap muka bersama tentor di kelas bimbel.
                                        </p>
                                    </div>

                                    <!-- Mini Thumbnails Foto (Jika Tersedia) -->
                                    <div v-if="gallery.length > 0" class="flex items-center gap-2 pt-1">
                                        <div
                                            v-for="g in gallery.slice(0, 3)"
                                            :key="g.id"
                                            class="h-12 w-12 rounded-xl overflow-hidden border-2 border-white shadow-xs bg-slate-100 shrink-0"
                                        >
                                            <img :src="g.photo_url" :alt="g.title" class="w-full h-full object-cover" />
                                        </div>
                                        <div
                                            v-if="gallery.length > 3"
                                            class="h-12 w-12 rounded-xl bg-orange-100 text-orange-700 font-black text-xs flex items-center justify-center shrink-0 border-2 border-white shadow-xs"
                                        >
                                            +{{ gallery.length - 3 }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between pt-3 border-t border-orange-100/80 text-xs font-bold text-orange-600 group-hover:text-orange-700">
                                    <span>Buka Galeri Foto Lengkap</span>
                                    <ChevronRight class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>

                            <!-- Card Quick Jump: Buku Jurnal Presensi -->
                            <div
                                @click="switchTab('journal')"
                                class="group p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 via-indigo-500/5 to-white border border-blue-200/70 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer flex flex-col justify-between"
                            >
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-blue-700 font-extrabold text-xs uppercase tracking-wider">
                                            <BookOpen class="h-4 w-4" />
                                            <span>Buku Jurnal Presensi</span>
                                        </div>
                                        <span class="bg-blue-600 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-xs">
                                            {{ attendance_history.length }} Sesi Terjadwal
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-800">
                                            Buka Riwayat Pertemuan & Catatan Tentor
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                            Ketahui rincian materi yang telah dipelajari, status kehadiran tiap sesi, dan catatan khusus dari pengajar.
                                        </p>
                                    </div>

                                    <!-- Cuplikan Sesi Terakhir -->
                                    <div v-if="kpi?.latest_session" class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-500">Sesi Terakhir: {{ kpi.latest_session.date }}</span>
                                            <span class="font-bold text-blue-700 truncate max-w-[140px]">{{ kpi.latest_session.subject }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between pt-3 border-t border-blue-100/80 text-xs font-bold text-blue-600 group-hover:text-blue-700">
                                    <span>Buka Riwayat Jurnal Lengkap</span>
                                    <ChevronRight class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- KONTEN TAB 2: BUKU JURNAL PRESENSI -->
                    <!-- ========================================== -->
                    <div v-else-if="activeTab === 'journal'" class="p-6 space-y-4">
                        <!-- Filter Bar Jurnal -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-100">
                            <!-- Search -->
                            <div class="relative flex-1 max-w-sm">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                <input
                                    type="text"
                                    v-model="searchJournalQuery"
                                    placeholder="Cari materi, pelajaran, tentor..."
                                    class="w-full h-9 pl-9 pr-3 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/10 font-medium"
                                />
                            </div>

                            <!-- Filter Controls -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Filter Mapel -->
                                <select
                                    v-model="selectedSubjectFilter"
                                    class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:outline-none font-medium"
                                >
                                    <option value="all">Semua Mata Pelajaran</option>
                                    <option v-for="subj in subjects_list" :key="subj" :value="subj">
                                        {{ subj }}
                                    </option>
                                </select>

                                <!-- Filter Status Hadir / Tidak -->
                                <select
                                    v-model="selectedStatusFilter"
                                    class="h-9 px-3 text-xs bg-white border border-slate-200 rounded-xl focus:border-orange-500 focus:outline-none font-medium"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="present">Hanya Hadir</option>
                                    <option value="absent">Hanya Tidak Hadir</option>
                                </select>
                            </div>
                        </div>

                        <!-- Daftar Kartu Riwayat Pertemuan Kelas -->
                        <div class="space-y-3.5">
                            <div
                                v-for="item in filteredJournal"
                                :key="item.id"
                                class="p-4 sm:p-5 bg-white hover:bg-slate-50/70 rounded-2xl border transition-all space-y-3"
                                :class="item.status === 'present' ? 'border-slate-100 shadow-xs' : 'border-rose-200 bg-rose-50/20'"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100/80 pb-3">
                                    <!-- Tanggal & Jam -->
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0"
                                            :class="item.status === 'present' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                        >
                                            <CalendarCheck v-if="item.status === 'present'" class="h-5 w-5" />
                                            <XCircle v-else class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-800">{{ item.formatted_date }}</h4>
                                            <p class="text-[10px] text-slate-400 flex items-center gap-1">
                                                <Clock class="h-3 w-3" />
                                                <span>{{ item.time }} &bull; Kelompok {{ item.study_group_name }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Status Hadir & Mapel Badge -->
                                    <div class="flex items-center gap-2 self-start sm:self-auto">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-50 text-orange-700 border border-orange-100">
                                            {{ item.subject_name }}
                                        </span>
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1"
                                            :class="item.status === 'present' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'"
                                        >
                                            <CheckCircle2 v-if="item.status === 'present'" class="h-3 w-3" />
                                            <XCircle v-else class="h-3 w-3" />
                                            <span>{{ item.status_label }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Materi yang Dipelajari & Tentor Pengampu -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 text-xs">
                                    <div class="md:col-span-2 space-y-1.5">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Topik / Jurnal Materi Belajar:</span>
                                        <p class="text-xs font-medium text-slate-700 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100/70">
                                            "{{ item.topic_description }}"
                                        </p>
                                        <p v-if="item.notes" class="text-[11px] text-slate-500 italic mt-1">
                                            Catatan Guru: {{ item.notes }}
                                        </p>
                                    </div>

                                    <!-- Info Guru & Dokumentasi Foto -->
                                    <div class="space-y-2 flex flex-col justify-between border-t md:border-t-0 md:border-l border-slate-100 pt-2 md:pt-0 md:pl-4">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tentor Pengampu:</span>
                                            <p class="font-bold text-slate-800 text-xs mt-0.5 flex items-center gap-1.5">
                                                <User class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ item.tentor_name }}</span>
                                            </p>
                                        </div>

                                        <!-- Tombol Foto Dokumentasi -->
                                        <div v-if="item.photo_url" class="pt-2">
                                            <button
                                                type="button"
                                                @click="openPhotoPreview(item)"
                                                class="w-full py-1.5 px-3 bg-slate-100 hover:bg-orange-50 text-slate-700 hover:text-orange-600 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors group"
                                            >
                                                <Camera class="h-3.5 w-3.5 text-slate-400 group-hover:text-orange-600" />
                                                <span>Lihat Dokumentasi Foto</span>
                                            </button>
                                        </div>
                                        <div v-else class="text-[10px] text-slate-400 italic">
                                            Foto dokumentasi tidak diunggah
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!filteredJournal.length" class="py-12 text-center text-slate-400 text-xs bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                <BookOpen class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                <p class="font-medium text-slate-600 text-sm">Tidak ada catatan presensi pada filter ini.</p>
                                <p class="text-slate-400 text-xs mt-1">Coba ubah kata kunci pencarian atau opsi filter di atas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- KONTEN TAB 3: GALERI MOMEN BELAJAR -->
                    <!-- ========================================== -->
                    <div v-else-if="activeTab === 'gallery'" class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <Camera class="h-4 w-4 text-orange-600" />
                                    <span>Galeri Momen Suasana Kelas</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Dokumentasi foto kegiatan belajar ananda yang diabadikan oleh tentor</p>
                            </div>
                            <span class="text-xs font-bold text-slate-500">{{ gallery.length }} Foto Tersimpan</span>
                        </div>

                        <!-- Grid Kartu Foto Dokumentasi -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                            <div
                                v-for="pic in gallery"
                                :key="pic.id"
                                @click="openPhotoPreview(pic)"
                                class="group bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-xs hover:shadow-md transition-all cursor-pointer flex flex-col"
                            >
                                <!-- Foto Thumbnail -->
                                <div class="relative h-48 w-full bg-slate-950 overflow-hidden">
                                    <img
                                        :src="pic.photo_url"
                                        :alt="pic.title"
                                        class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3 text-white text-xs font-semibold">
                                        <span class="inline-flex items-center gap-1">
                                            <Eye class="h-3.5 w-3.5" />
                                            <span>Klik untuk Perbesar</span>
                                        </span>
                                    </div>
                                    <span class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ pic.date }}
                                    </span>
                                </div>

                                <!-- Detail Info Foto -->
                                <div class="p-4 flex-1 flex flex-col justify-between space-y-2">
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-800 leading-snug group-hover:text-orange-600 transition-colors line-clamp-1">
                                            {{ pic.title }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-1 italic">
                                            "{{ pic.topic }}"
                                        </p>
                                    </div>
                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                        <span>Tentor: {{ pic.tentor }}</span>
                                        <span class="text-orange-600 font-bold flex items-center gap-0.5">
                                            <span>Lihat</span>
                                            <ChevronRight class="h-3 w-3" />
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!gallery.length" class="py-16 text-center text-slate-400 text-xs bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <Camera class="h-10 w-10 mx-auto text-slate-300 mb-2" />
                            <p class="font-bold text-slate-700 text-sm">Belum Ada Dokumentasi Foto Kelas</p>
                            <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto">
                                Foto aktivitas kelas akan otomatis muncul di sini begitu tentor mengunggah dokumentasi saat sesi absensi berlangsung.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- FLOATING MOBILE QUICK BAR (NAVIGASI MELAYANG KHUSUS SMARTPHONE) -->
        <!-- ========================================== -->
        <div v-if="student" class="sm:hidden fixed bottom-4 inset-x-3 z-30 max-w-sm mx-auto">
            <div class="bg-slate-900/95 backdrop-blur-md text-white p-1.5 rounded-2xl shadow-2xl border border-white/10 grid grid-cols-3 gap-1">
                <button
                    type="button"
                    @click="switchTab('overview')"
                    class="flex flex-col items-center justify-center gap-0.5 py-1.5 px-2 rounded-xl text-[10px] font-bold transition-all text-center"
                    :class="activeTab === 'overview' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-300 hover:text-white'"
                >
                    <Sparkles class="h-4 w-4 shrink-0" />
                    <span>Analisis</span>
                </button>

                <button
                    type="button"
                    @click="switchTab('journal')"
                    class="flex flex-col items-center justify-center gap-0.5 py-1.5 px-2 rounded-xl text-[10px] font-bold transition-all text-center relative"
                    :class="activeTab === 'journal' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-300 hover:text-white'"
                >
                    <div class="relative">
                        <BookOpen class="h-4 w-4 shrink-0" />
                        <span
                            v-if="attendance_history.length > 0"
                            class="absolute -top-1 -right-3 text-[8px] font-extrabold px-1 rounded-full bg-orange-400 text-slate-950"
                        >
                            {{ attendance_history.length }}
                        </span>
                    </div>
                    <span>Jurnal</span>
                </button>

                <button
                    type="button"
                    @click="switchTab('gallery')"
                    class="flex flex-col items-center justify-center gap-0.5 py-1.5 px-2 rounded-xl text-[10px] font-bold transition-all text-center relative"
                    :class="activeTab === 'gallery' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-300 hover:text-white'"
                >
                    <div class="relative">
                        <Camera class="h-4 w-4 shrink-0" />
                        <span
                            v-if="gallery.length > 0"
                            class="absolute -top-1 -right-3 text-[8px] font-extrabold px-1 rounded-full bg-amber-400 text-slate-950"
                        >
                            {{ gallery.length }}
                        </span>
                    </div>
                    <span>Galeri Foto</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL PREVIEW FOTO DOKUMENTASI (LIGHTBOX) -->
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
                v-if="previewPhoto"
                class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="closePhotoPreview"
            >
                <div class="bg-white rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-xs text-slate-800 truncate block">{{ previewPhoto.title }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ previewPhoto.date }} &bull; Tentor: {{ previewPhoto.tentor }}</span>
                        </div>
                        <button
                            type="button"
                            @click="closePhotoPreview"
                            class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Gambar Foto -->
                    <div class="p-2 bg-slate-950 flex items-center justify-center">
                        <img
                            :src="previewPhoto.url"
                            :alt="previewPhoto.title"
                            class="max-h-[65vh] w-auto object-contain rounded-xl"
                        />
                    </div>

                    <!-- Caption Materi -->
                    <div v-if="previewPhoto.topic" class="p-4 bg-slate-50 border-t border-slate-100 text-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Topik Pembelajaran:</span>
                        <p class="text-xs text-slate-700 mt-0.5 leading-relaxed">
                            "{{ previewPhoto.topic }}"
                        </p>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

