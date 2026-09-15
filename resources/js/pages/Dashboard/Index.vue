<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Users,
    GraduationCap,
    CalendarCheck,
    Sparkles,
    TrendingUp,
    Clock,
    CheckCircle2,
    BookOpen,
    Building2,
    Calendar,
    ArrowRight,
    Camera,
    Plus,
    FileBarChart,
    ShieldCheck,
    Layers,
    UserCheck,
    AlertCircle,
    Trash2,
} from 'lucide-vue-next';
import { toast, confirmAction } from '@/composables/useNotification';

interface Props {
    stats: {
        total_students: number;
        total_tutors: number;
        total_classes: number;
        today_sessions: number;
        monthly_sessions: number;
        attendance_rate: number;
        academic_year_name: string;
    };
    recentSessions: Array<{
        id: string;
        date: string;
        formatted_date: string;
        tutor_name: string;
        class_name: string;
        education_level: string;
        subject: string;
        topic: string;
        photo_url: string | null;
        total_students: number;
        present_count: number;
        late_count: number;
        created_at_human: string;
    }>;
    recentStudentLogs: Array<{
        id: string;
        student_name: string;
        nis: string;
        class_name: string;
        subject_name: string;
        date: string;
        status: 'present' | 'late' | 'sick' | 'excused' | 'absent';
        notes: string | null;
        time_human: string;
    }>;
    tenant: {
        id: string;
        name: string;
        slug: string;
    };
}

const props = defineProps<Props>();

// Tanggal hari ini dalam format Bahasa Indonesia
const todayFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date());
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'present':
            return { label: 'Hadir', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'late':
            return { label: 'Terlambat', class: 'bg-amber-50 text-amber-700 border-amber-200' };
        case 'sick':
            return { label: 'Sakit', class: 'bg-purple-50 text-purple-700 border-purple-200' };
        case 'excused':
            return { label: 'Izin', class: 'bg-blue-50 text-blue-700 border-blue-200' };
        case 'absent':
            return { label: 'Tidak Hadir', class: 'bg-rose-50 text-rose-700 border-rose-200' };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const deleteRecentSession = async (sess: any) => {
    const confirmed = await confirmAction({
        title: 'Hapus Sesi Presensi?',
        text: `Data sesi presensi kelas "${sess.class_name}" (${sess.subject}) tanggal ${sess.formatted_date} akan dihapus (soft delete). Catatan presensi siswa di sesi ini akan ikut terhapus.`,
        confirmButtonText: 'Ya, Hapus Sesi',
        confirmText: 'Ya, Hapus Sesi',
        icon: 'warning',
    });

    if (!confirmed) return;

    router.delete(`/attendance-sessions/${sess.id}`, {
        onSuccess: () => {
            toast('Data sesi presensi berhasil dihapus.', 'success');
        },
        onError: (err: any) => {
            toast(err.general || 'Gagal menghapus data sesi presensi.', 'error');
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Dashboard Utama">
        <Head title="Dashboard Manajemen Bimbel No Name" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- ========================================================================= -->
            <!-- 1. WELCOME BANNER (MODERN SOFT LIGHT THEME)                               -->
            <!-- ========================================================================= -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15">
                <!-- Background ambient glows -->
                <div class="absolute -right-10 -bottom-10 h-56 w-56 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-24 top-0 h-32 w-32 rounded-full bg-amber-300/20 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <img
                            :src="tenant?.logo_url || ($page.props as any).app_logo || '/images/logo_bnn.png'"
                            :alt="tenant?.name ?? 'Logo Lembaga'"
                            class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-white p-2 object-contain shadow-md border border-white/30 shrink-0 hidden sm:block"
                        />
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full bg-white/20 backdrop-blur-md px-3.5 py-1 text-xs font-semibold text-orange-50 border border-white/20 shadow-2xs">
                                <Sparkles class="h-3.5 w-3.5 text-amber-200" />
                                <span>{{ todayFormatted }} &bull; TP {{ stats.academic_year_name }}</span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-snug">
                                Selamat Datang di Panel Manajemen Bimbel! 👋
                            </h1>
                            <p class="text-xs sm:text-sm text-orange-100 max-w-2xl font-medium leading-relaxed">
                                Lembaga <strong class="text-white underline decoration-amber-300 underline-offset-2">{{ tenant?.name }}</strong> siap mengelola data siswa, jadwal bimbingan belajar, dan memantau presensi kehadiran secara transparan.
                            </p>
                        </div>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <Link
                            href="/reports/student-attendance"
                            class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-white text-orange-600 hover:bg-orange-50 active:scale-95 font-bold text-xs shadow-md shadow-black/5 transition-all"
                        >
                            <CalendarCheck class="h-4 w-4" />
                            <span>Kehadiran Peserta Didik</span>
                        </Link>
                        <Link
                            href="/students"
                            class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-orange-700/60 hover:bg-orange-700 text-white active:scale-95 font-semibold text-xs border border-white/20 transition-all backdrop-blur-xs"
                        >
                            <Users class="h-4 w-4" />
                            <span>Kelola Siswa</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 2. METRICS OVERVIEW (4 KARTU STATISTIK UTAMA)                              -->
            <!-- ========================================================================= -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Card 1: Total Peserta Didik -->
                <div class="rounded-3xl bg-white p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Peserta Didik Aktif</span>
                        <div class="h-11 w-11 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold">
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.total_students }}</div>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Siswa terdaftar aktif</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-orange-600">
                        <Link href="/students" class="hover:underline flex items-center gap-1">
                            <span>Buka Data Siswa</span>
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Card 2: Guru / Tentor Bimbel -->
                <div class="rounded-3xl bg-white p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Guru / Tentor</span>
                        <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <GraduationCap class="h-5 w-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.total_tutors }}</div>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Pengajar berkompeten</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-amber-600">
                        <Link href="/tentors" class="hover:underline flex items-center gap-1">
                            <span>Kelola Data Guru</span>
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Card 3: Kelompok Bimbel -->
                <div class="rounded-3xl bg-white p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kelompok Bimbel</span>
                        <div class="h-11 w-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <BookOpen class="h-5 w-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.total_classes }}</div>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">Kelas bimbingan aktif</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-blue-600">
                        <Link href="/study-groups" class="hover:underline flex items-center gap-1">
                            <span>Kelompok Belajar</span>
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Card 4: Tingkat Kehadiran Bulan Ini -->
                <div class="rounded-3xl bg-white p-5 sm:p-6 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-orange-200 transition-all space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tingkat Kehadiran</span>
                        <div class="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900">{{ stats.attendance_rate }}%</div>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ stats.monthly_sessions }} sesi bulan ini</p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-emerald-600">
                        <Link href="/reports/student-attendance" class="hover:underline flex items-center gap-1">
                            <span>Laporan Kehadiran</span>
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 3. SESI PRESENSI & DOKUMENTASI BELAJAR TERBARU                             -->
            <!-- ========================================================================= -->
            <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900">Sesi Presensi & Dokumentasi Kelas Terbaru</h3>
                        <p class="text-xs text-slate-500">Sesi bimbingan belajar yang tercatat lengkap dengan jurnal materi dan foto kegiatan</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/attendance/manual"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold text-xs border border-orange-200/80 transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Presensi Susulan</span>
                        </Link>
                        <Link
                            href="/reports/student-attendance"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 transition-colors"
                        >
                            <FileBarChart class="h-4 w-4" />
                            <span>Rekap Lengkap</span>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="recentSessions.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                    <CalendarCheck class="h-12 w-12 mx-auto text-slate-300 opacity-60" />
                    <p class="text-sm font-semibold text-slate-600">Belum Ada Sesi Presensi yang Dicatat</p>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Gunakan tombol "Input Presensi Manual" untuk memasukkan data presensi pertemuan yang telah berlangsung.
                    </p>
                </div>

                <!-- Grid Sesi Presensi Terbaru -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="sess in recentSessions"
                        :key="sess.id"
                        class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-5 space-y-4 hover:border-orange-200 hover:bg-white hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <!-- Photo or Fallback Badge -->
                            <div class="relative rounded-xl overflow-hidden bg-slate-200 h-36 w-full border border-slate-200/80">
                                <img
                                    v-if="sess.photo_url"
                                    :src="sess.photo_url"
                                    :alt="sess.class_name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-100 gap-1">
                                    <Camera class="h-6 w-6 opacity-40" />
                                    <span class="text-[11px] font-medium">Dokumentasi Terarsip</span>
                                </div>

                                <div class="absolute bottom-2 left-2 bg-white/95 backdrop-blur-md px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-800 shadow-xs border border-white/80">
                                    {{ sess.formatted_date }}
                                </div>

                                <div class="absolute top-2 right-2 bg-emerald-500 text-white px-2 py-0.5 rounded-lg text-[10px] font-bold shadow-xs">
                                    {{ sess.present_count }} / {{ sess.total_students }} Hadir
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400">
                                    <span>{{ sess.education_level }}</span>
                                    <span>{{ sess.created_at_human }}</span>
                                </div>
                                <h4 class="font-bold text-sm text-slate-900 mt-0.5">{{ sess.class_name }}</h4>
                                <p class="text-xs font-semibold text-orange-600 mt-0.5">{{ sess.subject }}</p>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed line-clamp-2">
                                {{ sess.topic }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs text-slate-500 font-medium">
                            <span class="truncate">Tutor: <strong class="text-slate-700">{{ sess.tutor_name }}</strong></span>
                            <button
                                v-if="$page.props.auth.user.role === 'admin_bimbel' || $page.props.auth.user.role === 'superadmin'"
                                type="button"
                                @click="deleteRecentSession(sess)"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2 py-1 rounded-lg transition active:scale-95 cursor-pointer shrink-0"
                                title="Hapus Sesi Presensi"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 4. AKTIVITAS KEHADIRAN SISWA TERBARU (STREAM LIST)                        -->
            <!-- ========================================================================= -->
            <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold">
                            <Clock class="h-4 w-4" />
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900">Aktivitas Presensi Siswa Terkini</h4>
                            <p class="text-[11px] text-slate-500">Rekam presensi kehadiran peserta didik yang baru tercatat</p>
                        </div>
                    </div>
                </div>

                <div v-if="recentStudentLogs.length === 0" class="py-8 text-center text-slate-400 text-xs">
                    Belum ada data aktivitas kehadiran siswa.
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="log in recentStudentLogs"
                        :key="log.id"
                        class="py-3 flex items-center justify-between gap-4 hover:bg-slate-50/60 px-2 rounded-xl transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ log.student_name.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-xs text-slate-900">{{ log.student_name }}</div>
                                <div class="text-[11px] text-slate-500">
                                    NIS: {{ log.nis }} &bull; {{ log.class_name }} ({{ log.subject_name }})
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span
                                class="px-2.5 py-1 rounded-full text-[11px] font-bold border"
                                :class="getStatusBadge(log.status).class"
                            >
                                {{ getStatusBadge(log.status).label }}
                            </span>
                            <span class="text-[10px] text-slate-400 hidden sm:inline-block whitespace-nowrap">
                                {{ log.time_human }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
