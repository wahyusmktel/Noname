<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Users,
    GraduationCap,
    CalendarCheck,
    QrCode,
    Sparkles,
    ArrowUpRight,
    TrendingUp,
    Clock,
    CheckCircle2,
    AlertCircle,
    UserCheck,
    Layers,
    BookOpen,
    Play,
    Building2,
    Calendar,
    ChevronRight,
    Filter,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Props {
    stats: {
        total_students: number;
        students_present: number;
        attendance_rate: number;
        active_sessions: number;
        total_tutors: number;
        tutors_active_today: number;
        monthly_target: number;
    };
    todaySessions: Array<{
        id: string;
        class_name: string;
        tutor_name: string;
        subject: string;
        room: string;
        time_start: string;
        time_end: string;
        total_students: number;
        present_count: number;
        status: 'completed' | 'ongoing' | 'upcoming';
    }>;
    recentLogs: Array<{
        id: string;
        student_name: string;
        nis: string;
        class: string;
        time: string;
        method: string;
        status: string;
        status_type: 'success' | 'warning' | 'info';
    }>;
    tenant: {
        id: string;
        name: string;
        slug: string;
        package_type: string;
    };
}

const props = defineProps<Props>();
const { toast } = useNotification();

// Quick action simulation
const handleQuickAction = (actionName: string) => {
    toast(`Fitur "${actionName}" akan dibuka pada modul berikutnya.`, 'info');
};

// Current date formatted in Indonesian
const todayFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
});
</script>

<template>
    <AuthenticatedLayout title="Dashboard Utama">
        <Head title="Dashboard Presensi Bimbel" />

        <div class="space-y-6">
            <!-- WELCOME BANNER (Soft Modern Orange Gradient) -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15">
                <!-- Background decorative elements -->
                <div class="absolute -right-10 -bottom-10 h-56 w-56 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-24 top-0 h-32 w-32 rounded-full bg-amber-300/20 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/20 backdrop-blur-md px-3 py-1 text-xs font-semibold text-orange-50 border border-white/20">
                            <Sparkles class="h-3.5 w-3.5 text-amber-200" />
                            <span>{{ todayFormatted }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            Selamat Datang di Portal Presensi! 👋
                        </h1>
                        <p class="text-sm text-orange-100 max-w-2xl font-medium leading-relaxed">
                            Lembaga <strong class="text-white underline decoration-amber-300 underline-offset-2">{{ tenant?.name }}</strong> siap memantau kehadiran siswa secara cepat, aman, dan terintegrasi real-time.
                        </p>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            @click="handleQuickAction('Buka Sesi Presensi QR')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-orange-600 hover:bg-orange-50 active:scale-95 font-bold text-xs shadow-sm transition-all"
                        >
                            <QrCode class="h-4 w-4" />
                            <span>Buka Sesi QR Sekarang</span>
                        </button>
                        <button
                            @click="handleQuickAction('Tambah Siswa')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-700/60 hover:bg-orange-700 text-white active:scale-95 font-semibold text-xs border border-white/20 transition-all backdrop-blur-xs"
                        >
                            <Users class="h-4 w-4" />
                            <span>+ Input Siswa Baru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 METRICS OVERVIEW CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Card 1: Total Siswa -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:border-orange-200 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Siswa Aktif</span>
                        <div class="h-10 w-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition-colors">
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.total_students }}</span>
                        <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                        <TrendingUp class="h-3.5 w-3.5" />
                        <span>+12 siswa bulan ini</span>
                    </div>
                </div>

                <!-- Card 2: Tingkat Kehadiran Hari Ini -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:border-orange-200 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kehadiran Hari Ini</span>
                        <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.attendance_rate }}%</span>
                        <span class="text-xs font-semibold text-slate-500">({{ stats.students_present }}/{{ stats.total_students }})</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-amber-500 to-orange-500 h-2 rounded-full transition-all duration-500"
                            :style="{ width: `${stats.attendance_rate}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Card 3: Sesi Kelas Hari Ini -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:border-orange-200 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sesi Terjadwal</span>
                        <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                            <BookOpen class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.active_sessions }}</span>
                        <span class="text-xs font-semibold text-slate-500">Sesi Kelas</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-indigo-600 font-semibold">
                        <Clock class="h-3.5 w-3.5" />
                        <span>1 Sesi sedang berjalan</span>
                    </div>
                </div>

                <!-- Card 4: Tutor Pengajar Hadir -->
                <div class="group relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:border-orange-200 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tutor Mengajar</span>
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            <UserCheck class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.tutors_active_today }}</span>
                        <span class="text-xs font-semibold text-slate-500">dari {{ stats.total_tutors }} Pengajar</span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>100% Sesi memiliki pengajar</span>
                    </div>
                </div>
            </div>

            <!-- TWO-COLUMN WORKSPACE -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LEFT COLUMN (2/3): JADWAL KELAS HARI INI -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-3xl bg-white border border-slate-200/80 p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Jadwal Sesi & Presensi Hari Ini</h2>
                                <p class="text-xs text-slate-500">Pantau kehadiran setiap sesi kelas secara langsung.</p>
                            </div>
                            <button
                                @click="handleQuickAction('Filter Jadwal')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:border-orange-300 text-xs font-semibold text-slate-600 hover:text-orange-600 bg-slate-50 transition-colors"
                            >
                                <Filter class="h-3.5 w-3.5" />
                                <span>Filter</span>
                            </button>
                        </div>

                        <!-- Sessions List -->
                        <div class="space-y-3.5">
                            <div
                                v-for="session in todaySessions"
                                :key="session.id"
                                class="rounded-2xl border border-slate-100 p-4 hover:border-orange-200 hover:bg-orange-50/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div class="flex items-start gap-3.5">
                                    <div
                                        class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': session.status === 'completed',
                                            'bg-orange-100 text-orange-700 animate-pulse': session.status === 'ongoing',
                                            'bg-slate-100 text-slate-600': session.status === 'upcoming',
                                        }"
                                    >
                                        <Clock class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-sm text-slate-800">{{ session.class_name }}</h3>
                                            <span
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': session.status === 'completed',
                                                    'bg-orange-100 text-orange-800': session.status === 'ongoing',
                                                    'bg-slate-100 text-slate-700': session.status === 'upcoming',
                                                }"
                                            >
                                                {{ session.status === 'completed' ? 'Selesai' : session.status === 'ongoing' ? 'Berlangsung' : 'Mendatang' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                                            {{ session.subject }} &bull; Pengajar: <span class="text-slate-700 font-semibold">{{ session.tutor_name }}</span>
                                        </p>
                                        <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-1.5 font-medium">
                                            <span>Ruang: {{ session.room }}</span>
                                            <span>&bull;</span>
                                            <span>{{ session.time_start }} - {{ session.time_end }} WIB</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Progress / Action -->
                                <div class="flex items-center sm:flex-col sm:items-end justify-between gap-2 shrink-0">
                                    <div class="text-right">
                                        <span class="text-xs font-bold text-slate-800">{{ session.present_count }} / {{ session.total_students }} Siswa</span>
                                        <p class="text-[10px] text-slate-400">
                                            {{ session.total_students > 0 ? Math.round((session.present_count / session.total_students) * 100) : 0 }}% Hadir
                                        </p>
                                    </div>
                                    <button
                                        @click="handleQuickAction('Detail Sesi ' + session.class_name)"
                                        class="px-3 py-1.5 rounded-lg bg-orange-50 hover:bg-orange-500 hover:text-white text-orange-600 font-semibold text-xs transition-colors"
                                    >
                                        Kelola Presensi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN (1/3): QUICK QR & REAL-TIME LOGS -->
                <div class="space-y-6">
                    <!-- Quick QR Scanner Launch Widget -->
                    <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-white shadow-md relative overflow-hidden">
                        <div class="absolute -right-8 -bottom-8 h-32 w-32 rounded-full bg-orange-500/20 blur-xl"></div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-10 w-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold">
                                <QrCode class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-sm">Mode Scanner Mandiri</h3>
                                <p class="text-[11px] text-slate-300">Gunakan tablet/kamera depan bimbel</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed mb-4">
                            Aktifkan layar pemindai QR Code statis di meja resepsionis atau kelas agar siswa dapat melakukan tap check-in langsung.
                        </p>
                        <button
                            @click="handleQuickAction('Layar Scanner Mandiri Resepsionis')"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs transition-colors shadow-sm shadow-orange-500/30"
                        >
                            <span>Buka Terminal Presensi</span>
                            <ArrowUpRight class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Real-Time Activity Log Feed -->
                    <div class="rounded-3xl bg-white border border-slate-200/80 p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-slate-900">Arus Presensi Real-Time</h2>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                Live Stream
                            </span>
                        </div>

                        <div class="space-y-3 divide-y divide-slate-100">
                            <div
                                v-for="log in recentLogs"
                                :key="log.id"
                                class="pt-3 first:pt-0 flex items-start justify-between gap-3"
                            >
                                <div class="space-y-0.5">
                                    <p class="text-xs font-bold text-slate-800">{{ log.student_name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ log.class }} &bull; <span class="text-slate-400 font-mono text-[10px]">{{ log.nis }}</span></p>
                                    <span class="text-[10px] text-slate-400 font-medium">Metode: {{ log.method }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span
                                        class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': log.status_type === 'success',
                                            'bg-amber-100 text-amber-800': log.status_type === 'warning',
                                            'bg-sky-100 text-sky-800': log.status_type === 'info',
                                        }"
                                    >
                                        {{ log.status }}
                                    </span>
                                    <p class="text-[10px] text-slate-400 font-mono mt-1">{{ log.time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
