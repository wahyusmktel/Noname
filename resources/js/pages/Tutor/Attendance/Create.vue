<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import SearchableSelect, { type SelectOption } from '@/components/UI/SearchableSelect.vue';
import {
    CalendarCheck,
    Calendar,
    User,
    BookOpen,
    Layers,
    FileText,
    Camera,
    UploadCloud,
    CheckCircle2,
    XCircle,
    Users,
    Sparkles,
    AlertCircle,
    Check,
    X,
    Loader2,
    Image as ImageIcon,
    History,
    CheckCheck,
    UserX,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface StudentItem {
    id: string;
    name: string;
    photo_url: string | null;
    student_phone: string | null;
    parent_phone: string | null;
}

interface StudyGroupItem {
    id: string;
    name: string;
    education_level: 'SD' | 'SMP' | 'SMA';
    students_count: number;
    students: StudentItem[];
}

interface RecentSessionItem {
    id: string;
    date: string;
    formatted_date: string;
    study_group_name: string;
    education_level: string;
    subject_name: string;
    total_students: number;
    present_students: number;
    photo_url: string | null;
}

interface SubjectItem {
    id: string;
    name: string;
}

interface Props {
    tentor: {
        id: string | null;
        name: string;
        specialization: string | null;
        phone: string | null;
        photo_url: string | null;
    };
    subjects?: SubjectItem[];
    study_groups: StudyGroupItem[];
    recent_sessions: RecentSessionItem[];
    active_academic_year: string | null;
    today_date: string;
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

// ==========================================
// STATE SPESIALISASI / MATA PELAJARAN
// ==========================================
const hasAssignedSubject = computed(() => {
    return !!(props.tentor.specialization && props.tentor.specialization.trim());
});

const selectedSubjectName = ref<string>(props.tentor.specialization || '');

// Opsi mata pelajaran untuk SearchableSelect (jika belum diatur)
const subjectOptions = computed<SelectOption[]>(() => {
    return (props.subjects || []).map(s => ({
        value: s.name,
        label: s.name,
    }));
});

// ==========================================
// STATE FILTER JENJANG & KELOMPOK
// ==========================================
const selectedEducationLevel = ref<'SD' | 'SMP' | 'SMA' | ''>('');
const selectedGroupId = ref<string>('');

// Kelompok terfilter berdasarkan jenjang yang dipilih
const filteredGroups = computed(() => {
    if (!selectedEducationLevel.value) return [];
    return props.study_groups.filter(g => g.education_level === selectedEducationLevel.value);
});

// Opsi kelompok untuk SearchableSelect
const groupOptions = computed<SelectOption[]>(() => {
    return filteredGroups.value.map(g => ({
        value: g.id,
        label: g.name,
        sublabel: `${g.students_count} Siswa terdaftar`,
    }));
});

// Reset kelompok yang dipilih jika jenjang berganti
watch(selectedEducationLevel, () => {
    selectedGroupId.value = '';
    form.study_group_id = '';
    studentsList.value = [];
});

// Kelompok aktif saat ini
const currentGroup = computed(() => {
    return props.study_groups.find(g => g.id === selectedGroupId.value) || null;
});

// ==========================================
// FORM STATE & ATTENDANCES
// ==========================================
interface AttendanceEntry {
    student_id: string;
    student_name: string;
    photo_url: string | null;
    status: 'present' | 'absent' | null;
}

const studentsList = ref<AttendanceEntry[]>([]);

const form = useForm({
    date: props.today_date,
    study_group_id: '',
    subject_name: props.tentor.specialization || '',
    topic_description: '',
    documentation_photo: null as File | null,
    attendances: [] as { student_id: string; status: 'present' | 'absent' }[],
});

watch(selectedSubjectName, (newSubject) => {
    form.subject_name = newSubject;
});

// Ketika kelompok dipilih, muat daftar siswa
watch(selectedGroupId, (newGroupId) => {
    form.study_group_id = newGroupId;
    if (!newGroupId) {
        studentsList.value = [];
        form.attendances = [];
        return;
    }

    const group = props.study_groups.find(g => g.id === newGroupId);
    if (group && group.students) {
        studentsList.value = group.students.map(s => ({
            student_id: s.id,
            student_name: s.name,
            photo_url: s.photo_url,
            status: null as ('present' | 'absent' | null), // Default null: guru menekan hadir/tidak hadir satu per satu
        }));
        syncAttendancesForm();
    } else {
        studentsList.value = [];
        form.attendances = [];
    }
});

const syncAttendancesForm = () => {
    form.attendances = studentsList.value
        .filter(s => s.status !== null)
        .map(s => ({
            student_id: s.student_id,
            status: s.status as 'present' | 'absent',
        }));
};

const setStudentStatus = (index: number, status: 'present' | 'absent') => {
    studentsList.value[index].status = status;
    syncAttendancesForm();
};

const markAll = (status: 'present' | 'absent') => {
    studentsList.value.forEach(s => {
        s.status = status;
    });
    syncAttendancesForm();
    toast(status === 'present' ? 'Semua siswa ditandai Hadir.' : 'Semua siswa ditandai Tidak Hadir.', 'info');
};

// Hitungan statistik kehadiran saat ini
const presentCount = computed(() => studentsList.value.filter(s => s.status === 'present').length);
const absentCount = computed(() => studentsList.value.filter(s => s.status === 'absent').length);
const unselectedCount = computed(() => studentsList.value.filter(s => !s.status).length);

// ==========================================
// CLIENT-SIDE AUTO-COMPRESS PHOTO UPLOAD
// ==========================================
const fileInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);
const isCompressing = ref(false);
const compressionStats = ref<{ originalSize: number; compressedSize: number } | null>(null);

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const handlePhotoSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    // Maksimal 10MB
    const maxSizeBytes = 10 * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        toast('Ukuran berkas foto melebihi batas 10MB.', 'error');
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    isCompressing.value = true;

    try {
        const compressedResult = await compressImageFile(file);
        form.documentation_photo = compressedResult.compressedFile;
        photoPreview.value = compressedResult.previewUrl;
        compressionStats.value = {
            originalSize: file.size,
            compressedSize: compressedResult.compressedFile.size,
        };
        toast('Foto dokumentasi berhasil dikompresi otomatis!', 'success');
    } catch (error) {
        // Fallback jika canvas kompresi gagal, gunakan file asli
        form.documentation_photo = file;
        photoPreview.value = URL.createObjectURL(file);
        compressionStats.value = null;
    } finally {
        isCompressing.value = false;
    }
};

const compressImageFile = (file: File): Promise<{ compressedFile: File; previewUrl: string }> => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => {
            const img = new Image();
            img.src = e.target?.result as string;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Max dimensi 1600px untuk resolusi tajam namun ukuran file sangat hemat
                const maxDim = 1600;
                if (width > height && width > maxDim) {
                    height = Math.round((height * maxDim) / width);
                    width = maxDim;
                } else if (height > maxDim) {
                    width = Math.round((width * maxDim) / height);
                    height = maxDim;
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    reject(new Error('Canvas context unavailable'));
                    return;
                }

                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Compression blob failed'));
                        return;
                    }
                    const compressedFile = new File(
                        [blob],
                        file.name.replace(/\.[^/.]+$/, "") + ".jpg",
                        { type: 'image/jpeg', lastModified: Date.now() }
                    );
                    const previewUrl = URL.createObjectURL(blob);
                    resolve({ compressedFile, previewUrl });
                }, 'image/jpeg', 0.78);
            };
            img.onerror = err => reject(err);
        };
        reader.onerror = err => reject(err);
    });
};

const removePhoto = () => {
    form.documentation_photo = null;
    photoPreview.value = null;
    compressionStats.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

// ==========================================
// SUBMIT FORM DENGAN SWEETALERT2
// ==========================================
const submitAttendance = async () => {
    if (!hasAssignedSubject.value && !form.subject_name) {
        toast('Silakan pilih mata pelajaran terlebih dahulu.', 'warning');
        return;
    }

    if (!form.study_group_id) {
        toast('Silakan pilih jenjang dan kelompok bimbel terlebih dahulu.', 'warning');
        return;
    }

    if (studentsList.value.length === 0) {
        toast('Kelompok yang dipilih belum memiliki siswa.', 'warning');
        return;
    }

    if (!form.topic_description.trim()) {
        toast('Deskripsi materi yang dipelajari wajib diisi.', 'warning');
        return;
    }

    if (!form.documentation_photo) {
        toast('Foto dokumentasi kelas wajib diunggah.', 'warning');
        return;
    }

    const unselected = studentsList.value.filter(s => !s.status);
    if (unselected.length > 0) {
        toast(`Masih ada ${unselected.length} peserta didik yang belum ditentukan status kehadirannya. Harap pilih Hadir atau Tidak Hadir untuk semua siswa.`, 'warning');
        return;
    }

    const confirmed = await confirmAction({
        title: 'Simpan Absensi Pertemuan?',
        text: `Anda akan menyimpan absensi untuk ${currentGroup.value?.name ?? 'kelompok ini'} pada tanggal ${form.date}. Data yang sudah tersimpan tidak dapat diinput ulang untuk tanggal yang sama.`,
        confirmText: 'Ya, Simpan Absensi',
        cancelText: 'Batal',
        icon: 'question',
    });

    if (!confirmed) return;

    syncAttendancesForm();

    form.transform((data) => ({
        ...data,
        documentation_photo: data.documentation_photo instanceof File ? data.documentation_photo : null,
    })).post('/tutor/attendance', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Data absensi pertemuan berhasil disimpan!', 'success');
            // Reset form setelah sukses
            form.topic_description = '';
            removePhoto();
            selectedGroupId.value = '';
            selectedEducationLevel.value = '';
            if (!hasAssignedSubject.value) {
                selectedSubjectName.value = '';
                form.subject_name = '';
            }
        },
        onError: (errors: any) => {
            toast(errors.error || 'Gagal menyimpan absensi. Periksa kembali formulir.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Absensi Pertemuan Siswa - Dashboard Guru" />

    <AuthenticatedLayout>
        <div class="space-y-6 pb-12">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-orange-50 text-orange-600 border border-orange-200/80 shadow-xs">
                            <CalendarCheck class="w-6 h-6" />
                        </div>
                        Absensi Pertemuan & Jurnal Kelas
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Catat kehadiran peserta didik dan dokumentasikan materi pembelajaran bimbingan belajar hari ini.
                    </p>
                </div>

                <div v-if="active_academic_year" class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Tahun Ajaran: {{ active_academic_year }}
                    </span>
                </div>
            </div>

            <!-- Error Banner jika ada -->
            <div
                v-if="form.errors.error"
                class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3 shadow-xs"
            >
                <AlertCircle class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
                <div>
                    <p class="font-bold text-sm">Peringatan Input Absensi</p>
                    <p class="mt-0.5 leading-relaxed">{{ form.errors.error }}</p>
                </div>
            </div>

            <!-- FORM GRID: 2 KOLOM (KIRI: SESI & JURNAL, KANAN: DAFTAR SISWA) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- ======================================================== -->
                <!-- KOLOM KIRI (5 KOLOM LG): DETAIL PERTEMUAN & JURNAL       -->
                <!-- ======================================================== -->
                <div class="lg:col-span-5 space-y-5">
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <h2 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <FileText class="w-4 h-4 text-orange-500" />
                                Informasi Pertemuan Kelas
                            </h2>
                            <span class="text-[11px] font-semibold text-slate-400">Langkah 1</span>
                        </div>

                        <!-- Profil Guru Pengampu -->
                        <div class="flex items-center gap-3 p-3.5 bg-gradient-to-r from-orange-50/80 to-amber-50/50 rounded-2xl border border-orange-200/60 shadow-2xs">
                            <div class="h-11 w-11 rounded-full ring-2 ring-orange-500/30 bg-orange-100 flex items-center justify-center overflow-hidden shrink-0 shadow-xs">
                                <img
                                    v-if="props.tentor.photo_url"
                                    :src="props.tentor.photo_url"
                                    :alt="props.tentor.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else class="text-orange-700 font-black text-sm">{{ props.tentor.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <h3 class="text-xs font-bold text-slate-900 truncate">{{ props.tentor.name }}</h3>
                                    <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-[10px] font-bold shrink-0">Tentor</span>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ props.tentor.specialization || 'Guru / Tutor Bimbel' }}</p>
                            </div>
                        </div>

                        <!-- 1. Tanggal Pertemuan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                1. Tanggal Pertemuan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <Calendar class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    v-model="form.date"
                                    type="date"
                                    required
                                    class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 focus:bg-white border rounded-xl outline-none focus:ring-2 focus:ring-orange-500/20 font-semibold text-slate-800 transition-all"
                                    :class="form.errors.date ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-orange-500'"
                                />
                            </div>
                            <p v-if="form.errors.date" class="text-xs text-rose-500 mt-1">{{ form.errors.date }}</p>
                        </div>

                        <!-- 2. Nama Guru (Read Only) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                2. Nama Guru Pengajar <span class="text-slate-400 font-normal">(Otomatis Sesuai Akun)</span>
                            </label>
                            <div class="relative">
                                <User class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    :value="tentor.name"
                                    type="text"
                                    readonly
                                    class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-700 cursor-not-allowed select-none"
                                />
                            </div>
                        </div>

                        <!-- 3. Mata Pelajaran (Read Only jika sudah diatur, atau SearchableSelect jika belum diatur) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    3. Mata Pelajaran / Keahlian 
                                    <span v-if="hasAssignedSubject" class="text-slate-400 font-normal">(Otomatis Sesuai Akun)</span>
                                    <span v-else class="text-rose-500">*</span>
                                </label>
                                <span v-if="!hasAssignedSubject" class="text-[10px] text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                                    Pilih Mata Pelajaran
                                </span>
                            </div>

                            <!-- Readonly jika sudah terisi di profil guru -->
                            <div v-if="hasAssignedSubject" class="relative">
                                <BookOpen class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    :value="tentor.specialization"
                                    type="text"
                                    readonly
                                    class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-100/80 border border-slate-200 rounded-xl font-bold text-slate-700 cursor-not-allowed select-none"
                                />
                            </div>

                            <!-- SearchableSelect dengan form pencarian jika belum diatur di profil guru -->
                            <div v-else>
                                <SearchableSelect
                                    v-model="selectedSubjectName"
                                    :options="subjectOptions"
                                    :all-option="false"
                                    placeholder="-- Cari & Pilih Mata Pelajaran --"
                                    search-placeholder="Ketik nama mata pelajaran..."
                                    :icon="BookOpen"
                                />
                                <p v-if="form.errors.subject_name" class="text-xs text-rose-500 mt-1">{{ form.errors.subject_name }}</p>
                            </div>
                        </div>

                        <!-- 4. Pilih Jenjang -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                4. Pilih Jenjang Pendidikan <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="level in (['SD', 'SMP', 'SMA'] as const)"
                                    :key="level"
                                    type="button"
                                    @click="selectedEducationLevel = level"
                                    class="py-2.5 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer text-center"
                                    :class="selectedEducationLevel === level
                                        ? 'bg-orange-500 text-white border-orange-500 shadow-sm shadow-orange-500/25 ring-2 ring-orange-500/20'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                >
                                    {{ level }}
                                </button>
                            </div>
                        </div>

                        <!-- 5. Pilih Kelompok (Muncul setelah Jenjang dipilih) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    5. Pilih Kelompok Bimbel <span class="text-rose-500">*</span>
                                </label>
                                <span v-if="selectedEducationLevel && filteredGroups.length > 0" class="text-[10px] text-slate-400 font-semibold">
                                    {{ filteredGroups.length }} Kelompok Tersedia
                                </span>
                            </div>
                            <SearchableSelect
                                v-model="selectedGroupId"
                                :options="groupOptions"
                                :disabled="!selectedEducationLevel"
                                :all-option="false"
                                :placeholder="!selectedEducationLevel ? '-- Pilih Jenjang Terlebih Dahulu --' : '-- Cari & Pilih Kelompok Bimbel --'"
                                search-placeholder="Ketik nama kelompok bimbel..."
                                :icon="Layers"
                            />
                            <p v-if="form.errors.study_group_id" class="text-xs text-rose-500 mt-1">{{ form.errors.study_group_id }}</p>
                            <p v-else-if="selectedEducationLevel && filteredGroups.length === 0" class="text-[11px] text-amber-600 mt-1">
                                Belum ada data kelompok untuk jenjang {{ selectedEducationLevel }}.
                            </p>
                        </div>

                        <!-- 6. Textarea Deskripsi Materi yang Dipelajari -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                6. Materi yang Dipelajari (Jurnal Kelas) <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.topic_description"
                                rows="3"
                                required
                                placeholder="Contoh: Membahas Bab 3 Persamaan Kuadrat, latihan soal UTBK nomor 1-15, dan pembahasan pekerjaan rumah..."
                                class="w-full p-3 text-xs bg-slate-50 focus:bg-white border rounded-xl outline-none focus:ring-2 focus:ring-orange-500/20 text-slate-800 transition-all leading-relaxed"
                                :class="form.errors.topic_description ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-orange-500'"
                            ></textarea>
                            <p v-if="form.errors.topic_description" class="text-xs text-rose-500 mt-1">{{ form.errors.topic_description }}</p>
                        </div>

                        <!-- 7. Upload Foto Dokumentasi (Auto Compress s.d 10MB) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    7. Foto Dokumentasi Kelas <span class="text-rose-500">* (Wajib)</span>
                                </label>
                                <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                    Auto Compress Aktif
                                </span>
                            </div>

                            <!-- Input File Hidden -->
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handlePhotoSelect"
                            />

                            <!-- Upload Box / Preview -->
                            <div v-if="!photoPreview" class="relative">
                                <button
                                    type="button"
                                    @click="fileInput?.click()"
                                    :disabled="isCompressing"
                                    class="w-full border-2 border-dashed border-slate-200 hover:border-orange-400 rounded-2xl p-4 text-center transition-all bg-slate-50/60 hover:bg-orange-50/30 flex flex-col items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-orange-100/80 text-orange-600 flex items-center justify-center">
                                        <Camera v-if="!isCompressing" class="w-5 h-5" />
                                        <Loader2 v-else class="w-5 h-5 animate-spin" />
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">
                                        {{ isCompressing ? 'Sedang Mengompres Foto...' : 'Ambil Foto atau Pilih Gambar' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400">
                                        Maks. 10MB. Foto langsung di-kompres otomatis menjadi ringan sebelum diunggah.
                                    </p>
                                </button>
                            </div>

                            <!-- Photo Preview Box -->
                            <div v-else class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 group">
                                <img
                                    :src="photoPreview"
                                    alt="Dokumentasi Kelas"
                                    class="w-full h-44 object-cover object-center"
                                />

                                <!-- Floating info badge -->
                                <div class="absolute bottom-2 left-2 right-2 bg-slate-900/85 backdrop-blur-xs text-white p-2 rounded-xl text-[11px] flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                        <span v-if="compressionStats" class="font-mono">
                                            {{ formatBytes(compressionStats.originalSize) }} &rarr; <strong class="text-emerald-300">{{ formatBytes(compressionStats.compressedSize) }}</strong>
                                        </span>
                                        <span v-else class="text-slate-300 truncate">Foto siap diunggah</span>
                                    </div>

                                    <button
                                        @click="removePhoto"
                                        type="button"
                                        class="p-1 rounded-lg hover:bg-rose-500/80 text-white transition-colors cursor-pointer shrink-0"
                                        title="Hapus Foto"
                                    >
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <p v-if="form.errors.documentation_photo" class="text-xs text-rose-500 mt-1">{{ form.errors.documentation_photo }}</p>
                        </div>
                    </div>

                    <!-- 5 Sesi Pertemuan Terakhir -->
                    <div v-if="recent_sessions.length > 0" class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <History class="w-4 h-4 text-slate-400" />
                                Riwayat Absensi Terakhir Anda
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ recent_sessions.length }} Sesi</span>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="item in recent_sessions"
                                :key="item.id"
                                class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <p class="font-bold text-slate-800">{{ item.study_group_name }} ({{ item.education_level }})</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ item.formatted_date }} &bull; {{ item.subject_name }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        {{ item.present_students }}/{{ item.total_students }} Hadir
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- KOLOM KANAN (7 KOLOM LG): DAFTAR SISWA & PRESENSI        -->
                <!-- ======================================================== -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                        <!-- Header Daftar Siswa -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h2 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                    <Users class="w-4 h-4 text-orange-500" />
                                    Daftar Peserta Didik & Presensi
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ currentGroup ? `Kelompok: ${currentGroup.name} (${currentGroup.education_level})` : 'Pilih kelompok bimbel di kolom kiri untuk memuat siswa' }}
                                </p>
                            </div>

                            <!-- Quick Action: Tandai Semua Hadir / Tidak Hadir -->
                            <div v-if="studentsList.length > 0" class="flex items-center gap-1.5">
                                <button
                                    @click="markAll('present')"
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60 transition-colors cursor-pointer"
                                    title="Tandai semua siswa hadir"
                                >
                                    <CheckCheck class="w-3.5 h-3.5" />
                                    <span>Semua Hadir</span>
                                </button>
                                <button
                                    @click="markAll('absent')"
                                    type="button"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60 transition-colors cursor-pointer"
                                    title="Tandai semua siswa tidak hadir"
                                >
                                    <UserX class="w-3.5 h-3.5" />
                                    <span>Semua Alpa</span>
                                </button>
                            </div>
                        </div>

                        <!-- Summary Pills -->
                        <div v-if="studentsList.length > 0" class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                                Total: {{ studentsList.length }} Siswa
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-bold border border-emerald-200/60">
                                Hadir: {{ presentCount }}
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-rose-50 text-rose-700 font-bold border border-rose-200/60">
                                Tidak Hadir: {{ absentCount }}
                            </span>
                            <span v-if="unselectedCount > 0" class="px-3 py-1 rounded-xl bg-amber-50 text-amber-700 font-bold border border-amber-200/60 flex items-center gap-1.5 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Belum Dipilih: {{ unselectedCount }}
                            </span>
                        </div>

                        <!-- Empty State jika belum pilih kelompok -->
                        <div
                            v-if="!selectedGroupId"
                            class="py-16 text-center border-2 border-dashed border-slate-200 rounded-2xl p-6 bg-slate-50/50"
                        >
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-orange-100/70 text-orange-500 flex items-center justify-center mb-3">
                                <Users class="w-7 h-7" />
                            </div>
                            <h3 class="font-bold text-slate-800 text-sm">Pilih Kelompok Bimbel</h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed">
                                Pilih jenjang dan kelompok bimbel pada kolom sebelah kiri untuk memunculkan daftar kehadiran peserta didik secara otomatis.
                            </p>
                        </div>

                        <!-- Empty State jika kelompok tidak memiliki siswa -->
                        <div
                            v-else-if="studentsList.length === 0"
                            class="py-12 text-center border-2 border-dashed border-amber-200 rounded-2xl p-6 bg-amber-50/30"
                        >
                            <AlertCircle class="w-10 h-10 mx-auto text-amber-500 mb-2" />
                            <h3 class="font-bold text-slate-800 text-sm">Kelompok Belum Memiliki Anggota</h3>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                Belum ada peserta didik yang dipetakan ke dalam kelompok ini. Harap hubungi admin bimbel untuk pemetaan siswa.
                            </p>
                        </div>

                        <!-- Tabel Peserta Didik & Tombol Kehadiran -->
                        <div v-else class="border border-slate-200/80 rounded-2xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-600 text-xs uppercase font-semibold tracking-wider">
                                            <th class="py-3 px-3.5 w-12 text-center">No</th>
                                            <th class="py-3 px-3.5">Nama Peserta Didik</th>
                                            <th class="py-3 px-3.5 text-center w-56">Kehadiran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <tr
                                            v-for="(student, idx) in studentsList"
                                            :key="student.student_id"
                                            class="hover:bg-slate-50/80 transition-colors"
                                            :class="student.status === 'absent' ? 'bg-rose-50/25' : ''"
                                        >
                                            <td class="py-3 px-3.5 text-center text-slate-400 font-mono">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="py-3 px-3.5">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-orange-400 to-amber-500 text-white font-bold flex items-center justify-center text-[11px] shrink-0 shadow-2xs">
                                                        {{ student.student_name.slice(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-slate-900 leading-snug">{{ student.student_name }}</p>
                                                        <span
                                                            class="text-[10px] font-semibold"
                                                            :class="{
                                                                'text-emerald-600': student.status === 'present',
                                                                'text-rose-600': student.status === 'absent',
                                                                'text-amber-600': !student.status,
                                                            }"
                                                        >
                                                            &bull; {{ student.status === 'present' ? 'Hadir' : (student.status === 'absent' ? 'Tidak Hadir' : 'Belum Ditentukan') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3.5 text-center">
                                                <!-- Button Group: Hadir & Tidak Hadir -->
                                                <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200/80 gap-1">
                                                    <button
                                                        type="button"
                                                        @click="setStudentStatus(idx, 'present')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                        :class="student.status === 'present'
                                                            ? 'bg-emerald-600 text-white shadow-xs'
                                                            : 'text-slate-600 hover:text-emerald-700 hover:bg-white'"
                                                    >
                                                        <Check class="w-3 h-3" />
                                                        <span>Hadir</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="setStudentStatus(idx, 'absent')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                        :class="student.status === 'absent'
                                                            ? 'bg-rose-600 text-white shadow-xs'
                                                            : 'text-slate-600 hover:text-rose-700 hover:bg-white'"
                                                    >
                                                        <X class="w-3 h-3" />
                                                        <span>Tidak Hadir</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tombol Submit Simpan Data Absensi -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                            <button
                                type="button"
                                @click="submitAttendance"
                                :disabled="form.processing || studentsList.length === 0"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-98 text-white font-bold text-sm shadow-md shadow-orange-500/25 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                <CheckCircle2 v-else class="w-4 h-4" />
                                <span>{{ form.processing ? 'Menyimpan Absensi...' : 'Simpan Data Absensi' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
