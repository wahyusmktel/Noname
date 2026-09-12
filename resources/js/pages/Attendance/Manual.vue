<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    CalendarCheck,
    Calendar,
    Users,
    GraduationCap,
    BookOpen,
    FileText,
    Camera,
    Info,
    CheckCircle2,
    Clock,
    AlertCircle,
    XCircle,
    UploadCloud,
    Trash2,
    Eye,
    Search,
    ShieldAlert,
    Sparkles,
    UserX,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Student {
    id: string;
    name: string;
    nis: string;
    photo_url: string | null;
    student_phone?: string;
    parent_phone?: string;
}

interface StudyGroup {
    id: string;
    name: string;
    education_level: string;
    students_count: number;
    students: Student[];
}

interface Tentor {
    id: string;
    name: string;
    nip: string;
    title: string;
}

interface Subject {
    id: string;
    name: string;
}

interface RecentSession {
    id: string;
    date: string;
    formatted_date: string;
    tentor_name: string;
    study_group_name: string;
    education_level: string;
    subject_name: string;
    topic_description: string;
    photo_url: string | null;
    total_students: number;
    present_count: number;
    absent_count: number;
    created_at_human: string;
}

interface Props {
    studyGroups: StudyGroup[];
    tentors: Tentor[];
    subjects: Subject[];
    activeAcademicYear: any;
    recentSessions: RecentSession[];
}

const props = defineProps<Props>();
const { toast, confirmDialog } = useNotification();

// Form Input Presensi Manual
const form = useForm({
    study_group_id: '',
    tentor_id: '',
    date: new Date().toISOString().split('T')[0],
    subject_name: '',
    topic_description: '',
    documentation_photo: null as File | null,
    students: [] as Array<{
        student_id: string;
        name: string;
        nis: string;
        status: 'present' | 'absent';
        notes: string;
    }>,
});

const photoPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Selected Group Object
const selectedGroup = computed(() => {
    return props.studyGroups.find(g => g.id === form.study_group_id) || null;
});

// Watch study group change and populate student attendance list
watch(() => form.study_group_id, (newGroupId) => {
    const group = props.studyGroups.find(g => g.id === newGroupId);
    if (group && group.students) {
        form.students = group.students.map(s => ({
            student_id: s.id,
            name: s.name,
            nis: s.nis,
            status: 'present', // Default seluruh siswa Hadir
            notes: '',
        }));
    } else {
        form.students = [];
    }
});

// Quick set all students status
const setAllStatus = (status: 'present' | 'absent') => {
    form.students.forEach(st => {
        st.status = status;
    });
    toast(status === 'present' ? 'Semua siswa diatur ke status: HADIR' : 'Semua siswa diatur ke status: TIDAK HADIR', 'info');
};

// Summary Counters
const countPresent = computed(() => form.students.filter(s => s.status === 'present').length);
const countAbsent = computed(() => form.students.filter(s => s.status === 'absent').length);

// Handle Photo Selection
const handlePhotoSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 10 * 1024 * 1024) {
            toast('Ukuran foto dokumentasi maksimal 10MB.', 'error');
            return;
        }
        form.documentation_photo = file;
        const reader = new FileReader();
        reader.onload = (re) => {
            photoPreview.value = re.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    form.documentation_photo = null;
    photoPreview.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// Submit Manual Attendance with SweetAlert2 Confirmation
const submitManualAttendance = async () => {
    if (!form.study_group_id) {
        toast('Silakan pilih kelompok belajar / kelas terlebih dahulu.', 'warning');
        return;
    }
    if (!form.tentor_id) {
        toast('Silakan pilih guru / tentor yang mengajar.', 'warning');
        return;
    }
    if (!form.subject_name.trim()) {
        toast('Mata pelajaran wajib diisi.', 'warning');
        return;
    }
    if (!form.topic_description.trim()) {
        toast('Materi/jurnal pembelajaran wajib diisi.', 'warning');
        return;
    }
    if (form.students.length === 0) {
        toast('Kelompok belajar ini belum memiliki peserta didik aktif.', 'warning');
        return;
    }

    const confirmed = await confirmDialog({
        title: 'Simpan Presensi Manual?',
        text: `Presensi untuk kelas "${selectedGroup.value?.name}" tanggal ${form.date} akan disimpan dan disinkronkan ke portal orang tua.`,
        icon: 'question',
        confirmButtonText: 'Ya, Simpan Presensi',
    });

    if (!confirmed) return;

    form.post('/attendance/manual', {
        forceFormData: true,
        onSuccess: () => {
            toast('Data presensi manual berhasil disimpan ke sistem!', 'success');
            // Reset form
            form.reset('subject_name', 'topic_description');
            removePhoto();
        },
        onError: (err) => {
            const firstErr = Object.values(err)[0];
            toast(firstErr || 'Gagal menyimpan presensi manual.', 'error');
        },
    });
};

// Delete manual session
const deleteSession = async (session: RecentSession) => {
    const confirmed = await confirmDialog({
        title: 'Hapus Sesi Presensi?',
        text: `Data sesi presensi kelas "${session.study_group_name}" tanggal ${session.formatted_date} akan dihapus. Data masuk ke arsip (soft delete).`,
        icon: 'warning',
        confirmButtonText: 'Ya, Hapus Data',
    });

    if (!confirmed) return;

    router.delete(`/attendance/manual/${session.id}`, {
        onSuccess: () => {
            toast('Sesi presensi berhasil dihapus.', 'success');
        },
        onError: () => {
            toast('Gagal menghapus sesi presensi.', 'error');
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Input Presensi Manual">
        <Head title="Input Presensi Manual - Bimbel No Name" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- ========================================================================= -->
            <!-- 1. KOTAK TEKS PANDUAN SINGKAT HALAMAN PRESENSI MANUAL                     -->
            <!-- ========================================================================= -->
            <div class="rounded-3xl bg-amber-50/80 border border-amber-200/90 p-5 sm:p-6 shadow-xs">
                <div class="flex items-start gap-4">
                    <div class="h-11 w-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                        <Info class="h-6 w-6" />
                    </div>
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-black text-amber-950">
                                Panduan Input Presensi Manual (Solusi Darurat & Absensi Susulan)
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/70 text-amber-900 hidden sm:inline-block">
                                Administrasi Bimbel
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-amber-900/90 leading-relaxed">
                            Halaman ini dirancang khusus untuk mengakomodasi sesi bimbingan belajar yang <strong>belum sempat diabsen oleh guru/tentor saat kelas berlangsung</strong> (misalnya karena gawai kehabisan daya/baterai, kendala sinyal internet, atau faktor kelupaan).
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 text-xs font-semibold text-amber-900">
                            <div class="flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                <span>Bisa input tanggal pertemuan lampau</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                <span>Pilih guru pengampu yang mengajar</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                <span>Tersinkron otomatis ke portal orang tua</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 2. FORM UTAMA INPUT PRESENSI MANUAL                                       -->
            <!-- ========================================================================= -->
            <form @submit.prevent="submitManualAttendance" class="space-y-6">
                <!-- Card 1: Informasi Sesi Pertemuan -->
                <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                        <div class="h-9 w-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold">
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base">Informasi Pertemuan & Guru Pengampu</h3>
                            <p class="text-xs text-slate-500">Tentukan tanggal kelas berlangsung, guru yang mengajar, dan kelompok belajar</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- 1. Tanggal Pertemuan -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Tanggal Sesi Pertemuan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Calendar class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.date"
                                    type="date"
                                    required
                                    class="w-full h-11 pl-10 pr-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-xs font-medium text-slate-800 transition-all focus:outline-none"
                                />
                            </div>
                            <p class="text-[11px] text-slate-400">Dapat memilih tanggal kemarin atau hari ini</p>
                        </div>

                        <!-- 2. Guru / Tentor Pengampu -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Guru / Tentor Pengampu <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <GraduationCap class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <select
                                    v-model="form.tentor_id"
                                    required
                                    class="w-full h-11 pl-10 pr-8 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-xs font-medium text-slate-800 transition-all focus:outline-none appearance-none"
                                >
                                    <option value="" disabled>-- Pilih Guru Pengampu --</option>
                                    <option v-for="t in tentors" :key="t.id" :value="t.id">
                                        {{ t.name }} ({{ t.title }})
                                    </option>
                                </select>
                            </div>
                            <p class="text-[11px] text-slate-400">Guru yang bertugas mengajar di sesi ini</p>
                        </div>

                        <!-- 3. Kelompok Belajar / Kelas -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Kelompok Belajar / Kelas <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Users class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <select
                                    v-model="form.study_group_id"
                                    required
                                    class="w-full h-11 pl-10 pr-8 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-xs font-medium text-slate-800 transition-all focus:outline-none appearance-none"
                                >
                                    <option value="" disabled>-- Pilih Kelompok Bimbel --</option>
                                    <option v-for="g in studyGroups" :key="g.id" :value="g.id">
                                        {{ g.name }} ({{ g.education_level }}) - {{ g.students_count }} Siswa
                                    </option>
                                </select>
                            </div>
                            <p class="text-[11px] text-slate-400">Daftar siswa otomatis dimuat saat kelas dipilih</p>
                        </div>
                    </div>

                    <!-- Row 2: Mata Pelajaran & Jurnal Materi -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
                        <!-- Mata Pelajaran -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Mata Pelajaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <BookOpen class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.subject_name"
                                    type="text"
                                    list="subject-suggestions"
                                    required
                                    placeholder="Contoh: Matematika Saintek"
                                    class="w-full h-11 pl-10 pr-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-xs font-medium text-slate-800 transition-all focus:outline-none"
                                />
                                <datalist id="subject-suggestions">
                                    <option v-for="s in subjects" :key="s.id" :value="s.name" />
                                </datalist>
                            </div>
                        </div>

                        <!-- Jurnal Materi Belajar -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Jurnal / Ringkasan Materi Pembelajaran <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.topic_description"
                                rows="2"
                                required
                                placeholder="Tuliskan topik bahasan, sub-bab yang dipelajari, latihan soal, atau PR..."
                                class="w-full p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-xs font-medium text-slate-800 transition-all focus:outline-none resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Row 3: Foto Dokumentasi (Opsional di Input Manual) -->
                    <div class="pt-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Foto Dokumentasi Kelas <span class="text-slate-400 font-normal">(Opsional jika gawai guru mengalami kendala teknis)</span>
                        </label>
                        <div class="flex flex-col sm:flex-row items-start gap-4">
                            <div
                                @click="fileInputRef?.click()"
                                class="w-full sm:w-80 h-32 border-2 border-dashed border-slate-200 rounded-2xl hover:border-orange-400 hover:bg-orange-50/40 flex flex-col items-center justify-center gap-1.5 cursor-pointer transition-all text-slate-500"
                            >
                                <Camera class="h-6 w-6 text-orange-500" />
                                <span class="text-xs font-bold text-slate-700">Pilih Foto Dokumentasi</span>
                                <span class="text-[10px] text-slate-400">JPG, PNG, WEBP (Maks 10MB)</span>
                            </div>
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handlePhotoSelect"
                            />

                            <!-- Preview -->
                            <div v-if="photoPreview" class="relative rounded-2xl overflow-hidden border border-slate-200 h-32 w-48 shadow-xs">
                                <img :src="photoPreview" alt="Preview Foto" class="w-full h-full object-cover" />
                                <button
                                    type="button"
                                    @click="removePhoto"
                                    class="absolute top-2 right-2 h-7 w-7 rounded-lg bg-rose-600 text-white flex items-center justify-center hover:bg-rose-700 shadow-sm"
                                    title="Hapus Foto"
                                >
                                    <XCircle class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Daftar Kehadiran Siswa -->
                <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold">
                                <Users class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Daftar Kehadiran Siswa</h3>
                                <p class="text-xs text-slate-500">
                                    {{ selectedGroup ? `Kelompok: ${selectedGroup.name} (${selectedGroup.students_count} Siswa)` : 'Pilih kelompok belajar di atas untuk menampilkan siswa' }}
                                </p>
                            </div>
                        </div>

                        <!-- Quick Action & Counters -->
                        <div v-if="form.students.length > 0" class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="setAllStatus('present')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs border border-emerald-200/80 transition-colors cursor-pointer"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                <span>Semua Hadir ({{ countPresent }})</span>
                            </button>
                            <button
                                type="button"
                                @click="setAllStatus('absent')"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200/80 transition-colors cursor-pointer"
                            >
                                <UserX class="w-3.5 h-3.5" />
                                <span>Semua Tidak Hadir ({{ countAbsent }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Placeholder if no group selected -->
                    <div v-if="form.students.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                        <Users class="h-12 w-12 mx-auto text-slate-300 opacity-60" />
                        <p class="text-sm font-semibold text-slate-600">Belum Ada Kelompok Belajar yang Dipilih</p>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Silakan pilih kelompok belajar di bagian formulir atas untuk memuat daftar siswa.</p>
                    </div>

                    <!-- Student List Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-3 px-3 w-10 text-center">No</th>
                                    <th class="py-3 px-3">Nama Siswa</th>
                                    <th class="py-3 px-3 text-center w-60">Status Kehadiran</th>
                                    <th class="py-3 px-3">Keterangan / Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(st, idx) in form.students"
                                    :key="st.student_id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                    :class="st.status === 'absent' ? 'bg-rose-50/20' : ''"
                                >
                                    <td class="py-3 px-3 font-semibold text-slate-400 w-10 text-center">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-orange-400 to-amber-500 text-white font-bold flex items-center justify-center text-[11px] shrink-0 shadow-2xs">
                                                {{ st.name.slice(0, 2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs sm:text-sm leading-snug">{{ st.name }}</div>
                                                <div class="flex items-center gap-2 text-[11px]">
                                                    <span class="text-slate-400 font-mono">NIS: {{ st.nis }}</span>
                                                    <span
                                                        class="font-semibold"
                                                        :class="st.status === 'present' ? 'text-emerald-600' : 'text-rose-600'"
                                                    >
                                                        &bull; {{ st.status === 'present' ? 'Hadir' : 'Tidak Hadir' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <!-- Button Group: Hadir & Tidak Hadir (Sama dengan Absensi Guru) -->
                                        <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200/80 gap-1">
                                            <button
                                                type="button"
                                                @click="st.status = 'present'"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                :class="st.status === 'present'
                                                    ? 'bg-emerald-600 text-white shadow-xs'
                                                    : 'text-slate-600 hover:text-emerald-700 hover:bg-white'"
                                            >
                                                <CheckCircle2 class="w-3.5 h-3.5" />
                                                <span>Hadir</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="st.status = 'absent'"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                :class="st.status === 'absent'
                                                    ? 'bg-rose-600 text-white shadow-xs'
                                                    : 'text-slate-600 hover:text-rose-700 hover:bg-white'"
                                            >
                                                <UserX class="w-3.5 h-3.5" />
                                                <span>Tidak Hadir</span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <input
                                            v-model="st.notes"
                                            type="text"
                                            placeholder="Catatan tambahan (opsional)"
                                            class="w-full h-9 px-3 rounded-lg bg-slate-50 border border-slate-200 text-xs focus:bg-white focus:border-orange-500 focus:outline-none"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Submit Button -->
                    <div v-if="form.students.length > 0" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 font-medium">
                            Total: <strong>{{ form.students.length }} Siswa</strong> &bull;
                            <span class="text-emerald-600 font-bold">{{ countPresent }} Hadir</span>,
                            <span class="text-rose-600 font-bold">{{ countAbsent }} Tidak Hadir</span>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-xs shadow-md shadow-orange-500/25 hover:opacity-95 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
                        >
                            <CalendarCheck class="h-4 w-4" />
                            <span>{{ form.processing ? 'Menyimpan Presensi...' : 'Simpan Presensi Manual' }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- ========================================================================= -->
            <!-- 3. TABEL RIWAYAT 10 SESI PRESENSI TERBARU                                 -->
            <!-- ========================================================================= -->
            <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold">
                            <Clock class="h-4 w-4" />
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-slate-900">Riwayat Sesi Presensi Terbaru</h4>
                            <p class="text-[11px] text-slate-500">10 sesi bimbingan belajar terakhir yang dicatat ke sistem</p>
                        </div>
                    </div>
                </div>

                <div v-if="recentSessions.length === 0" class="py-8 text-center text-slate-400 text-xs">
                    Belum ada riwayat sesi presensi yang tersimpan.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3">Kelas / Kelompok</th>
                                <th class="py-2.5 px-3">Guru Pengampu</th>
                                <th class="py-2.5 px-3">Mata Pelajaran & Materi</th>
                                <th class="py-2.5 px-3 text-center">Kehadiran Siswa</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="sess in recentSessions" :key="sess.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ sess.formatted_date }}</div>
                                    <div class="text-[10px] text-slate-400">{{ sess.created_at_human }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800">{{ sess.study_group_name }}</div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 mt-0.5">
                                        {{ sess.education_level }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-medium text-slate-700 whitespace-nowrap">
                                    {{ sess.tentor_name }}
                                </td>
                                <td class="py-3 px-3 max-w-xs">
                                    <div class="font-bold text-slate-800">{{ sess.subject_name }}</div>
                                    <p class="text-[11px] text-slate-500 truncate">{{ sess.topic_description }}</p>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        {{ sess.present_count }} / {{ sess.total_students }} Hadir
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    <button
                                        @click="deleteSession(sess)"
                                        class="h-8 w-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer inline-flex items-center justify-center"
                                        title="Hapus Sesi Presensi"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
