<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue';
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
    SwitchCamera,
    Smartphone,
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
// CLIENT-SIDE AUTO-COMPRESS PHOTO & CAMERA
// ==========================================
const fileInput = ref<HTMLInputElement | null>(null);
const fallbackFileInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);
const isCompressing = ref(false);
const compressionStats = ref<{ originalSize: number; compressedSize: number } | null>(null);

// WebRTC Live Camera State (Webcam / Laptop)
const isCameraModalOpen = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const cameraStream = ref<MediaStream | null>(null);
const cameraFacingMode = ref<'user' | 'environment'>('environment');
const isCameraLoading = ref(false);
const cameraError = ref<string | null>(null);
const isShutterTriggered = ref(false);

const isMobileDevice = (): boolean => {
    if (typeof window === 'undefined') return false;
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        || (navigator.maxTouchPoints > 1 && /Macintosh/i.test(navigator.userAgent));
};

// Fungsi utama ketika area foto / tombol ambil foto diklik
const openCamera = () => {
    if (isMobileDevice()) {
        // Pada perangkat HP, capture="environment" langsung mengaktifkan kamera HP
        triggerNativeCamera();
    } else {
        // Pada Laptop / PC, buka modal live camera webcam
        openWebcamModal();
    }
};

const triggerNativeCamera = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const openWebcamModal = async () => {
    isCameraModalOpen.value = true;
    cameraError.value = null;
    await startCameraStream();
};

const startCameraStream = async () => {
    isCameraLoading.value = true;
    cameraError.value = null;

    stopCameraStream();

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Browser Anda tidak mendukung akses kamera WebRTC.');
        }

        const constraints: MediaStreamConstraints = {
            audio: false,
            video: {
                facingMode: { ideal: cameraFacingMode.value },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        cameraStream.value = stream;

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
        }
    } catch (err: any) {
        console.error('Camera error:', err);
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            cameraError.value = 'Izin akses kamera ditolak. Silakan izinkan akses kamera di ikon gembok browser Anda.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            cameraError.value = 'Perangkat kamera/webcam tidak terdeteksi pada laptop/komputer ini.';
        } else {
            cameraError.value = err.message || 'Gagal mengakses kamera laptop.';
        }
    } finally {
        isCameraLoading.value = false;
    }
};

const stopCameraStream = () => {
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach((track) => track.stop());
        cameraStream.value = null;
    }
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
};

const closeCameraModal = () => {
    stopCameraStream();
    isCameraModalOpen.value = false;
    cameraError.value = null;
};

const switchCamera = async () => {
    cameraFacingMode.value = cameraFacingMode.value === 'environment' ? 'user' : 'environment';
    await startCameraStream();
};

// Ambil foto dari live video feed webcam
const captureWebcamSnapshot = async () => {
    if (!videoRef.value || !cameraStream.value) return;

    isShutterTriggered.value = true;
    setTimeout(() => {
        isShutterTriggered.value = false;
    }, 200);

    try {
        const video = videoRef.value;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 1280;
        canvas.height = video.videoHeight || 720;

        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Gagal inisialisasi context canvas.');

        // Render frame video saat ini ke canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Convert canvas ke blob/File
        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', 0.92)
        );

        if (!blob) throw new Error('Gagal mengambil gambar dari kamera.');

        const fileName = `dokumentasi_${Date.now()}.jpg`;
        const capturedFile = new File([blob], fileName, {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });

        // Hentikan kamera dan tutup modal
        closeCameraModal();

        // Kompresi hasil foto
        await processPhotoFile(capturedFile);
    } catch (err: any) {
        toast('Gagal mengambil foto dari kamera: ' + (err.message || 'Error'), 'error');
    }
};

const processPhotoFile = async (file: File) => {
    // Maksimal 10MB
    const maxSizeBytes = 10 * 1024 * 1024;
    if (file.size > maxSizeBytes) {
        toast('Ukuran berkas foto melebihi batas 10MB.', 'error');
        if (fileInput.value) fileInput.value.value = '';
        if (fallbackFileInput.value) fallbackFileInput.value.value = '';
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
        toast('Foto dokumentasi kelas berhasil diambil!', 'success');
    } catch (error) {
        form.documentation_photo = file;
        photoPreview.value = URL.createObjectURL(file);
        compressionStats.value = null;
    } finally {
        isCompressing.value = false;
    }
};

const handlePhotoSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    await processPhotoFile(file);
};

onBeforeUnmount(() => {
    stopCameraStream();
});

const formatBytes = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
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
    if (fallbackFileInput.value) fallbackFileInput.value.value = '';
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

                        <!-- 7. Ambil Foto Dokumentasi (Kamera HP / Webcam Laptop & Auto Compress) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    7. Foto Dokumentasi Kelas <span class="text-rose-500">* (Wajib)</span>
                                </label>
                                <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                    Auto Compress Aktif
                                </span>
                            </div>

                            <!-- Input File Hidden dengan capture="environment" untuk langsung meluncurkan kamera HP -->
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                class="hidden"
                                @change="handlePhotoSelect"
                            />

                            <!-- Input File Cadangan untuk upload berkas dari disk jika kamera tidak tersedia -->
                            <input
                                ref="fallbackFileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handlePhotoSelect"
                            />

                            <!-- Upload Box / Tombol Buka Kamera -->
                            <div v-if="!photoPreview" class="relative">
                                <div
                                    @click="openCamera"
                                    class="w-full border-2 border-dashed border-orange-200 hover:border-orange-400 rounded-2xl p-5 text-center transition-all bg-orange-50/20 hover:bg-orange-50/50 flex flex-col items-center justify-center gap-2 cursor-pointer group"
                                >
                                    <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-500/30 flex items-center justify-center group-hover:scale-105 transition-transform">
                                        <Camera v-if="!isCompressing" class="w-6 h-6" />
                                        <Loader2 v-else class="w-6 h-6 animate-spin" />
                                    </div>

                                    <div>
                                        <p class="text-xs font-bold text-slate-800">
                                            {{ isCompressing ? 'Sedang Memproses Foto...' : 'Buka Kamera & Ambil Foto Kelas' }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Otomatis membuka <span class="font-semibold text-orange-600">Kamera HP</span> di ponsel atau <span class="font-semibold text-orange-600">Webcam</span> di laptop
                                        </p>
                                    </div>

                                    <!-- Action buttons -->
                                    <div class="flex items-center gap-2 mt-1" @click.stop>
                                        <button
                                            type="button"
                                            @click="openCamera"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-[11px] font-bold shadow-xs transition-colors cursor-pointer"
                                        >
                                            <Camera class="w-3.5 h-3.5" />
                                            Buka Kamera Langsung
                                        </button>
                                        <button
                                            type="button"
                                            @click="fallbackFileInput?.click()"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-medium transition-colors cursor-pointer"
                                            title="Pilih foto dari berkas penyimpanan jika kamera terkendala"
                                        >
                                            <UploadCloud class="w-3 h-3" />
                                            Pilih Berkas
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo Preview Box -->
                            <div v-else class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 group">
                                <img
                                    :src="photoPreview"
                                    alt="Dokumentasi Kelas"
                                    class="w-full h-48 object-cover object-center"
                                />

                                <!-- Floating info badge & actions -->
                                <div class="absolute bottom-2 left-2 right-2 bg-slate-900/90 backdrop-blur-xs text-white p-2 rounded-xl text-[11px] flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                                        <span v-if="compressionStats" class="font-mono text-[10px]">
                                            {{ formatBytes(compressionStats.originalSize) }} &rarr; <strong class="text-emerald-300">{{ formatBytes(compressionStats.compressedSize) }}</strong>
                                        </span>
                                        <span v-else class="text-slate-300 truncate text-[10px]">Foto siap diunggah</span>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button
                                            @click="openCamera"
                                            type="button"
                                            class="px-2 py-1 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-[10px] font-bold transition-colors cursor-pointer flex items-center gap-1"
                                            title="Ambil foto ulang menggunakan kamera"
                                        >
                                            <Camera class="w-3 h-3" />
                                            Foto Ulang
                                        </button>
                                        <button
                                            @click="removePhoto"
                                            type="button"
                                            class="p-1 rounded-lg hover:bg-rose-500 text-white transition-colors cursor-pointer"
                                            title="Hapus Foto"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
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
                                    <span>Semua Tidak Hadir</span>
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

    <!-- ========================================== -->
    <!-- MODAL LIVE CAMERA VIEWFINDER (WEBCAM/HP)   -->
    <!-- ========================================== -->
    <Teleport to="body">
        <div
            v-if="isCameraModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200"
        >
            <div class="relative w-full max-w-xl bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-800 flex flex-col max-h-[92vh]">
                <!-- Header Modal -->
                <div class="px-5 py-3.5 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between text-white shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></div>
                        <h3 class="text-xs sm:text-sm font-bold tracking-wide flex items-center gap-1.5">
                            <Camera class="w-4 h-4 text-orange-400" />
                            Kamera Dokumentasi Kelas
                        </h3>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <!-- Switch Front/Back Camera -->
                        <button
                            type="button"
                            @click="switchCamera"
                            class="p-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors cursor-pointer"
                            title="Ganti Kamera Depan / Belakang"
                        >
                            <SwitchCamera class="w-4 h-4" />
                        </button>
                        <!-- Close Button -->
                        <button
                            type="button"
                            @click="closeCameraModal"
                            class="p-1.5 rounded-xl bg-slate-800 hover:bg-rose-500 text-slate-200 hover:text-white transition-colors cursor-pointer"
                            title="Tutup Kamera"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Camera Viewfinder / Video Area -->
                <div class="relative flex-1 bg-black min-h-[320px] sm:min-h-[400px] flex items-center justify-center overflow-hidden">
                    <!-- Video Stream Element -->
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        muted
                        class="w-full h-full object-cover"
                    ></video>

                    <!-- Shutter Flash Effect -->
                    <div
                        v-if="isShutterTriggered"
                        class="absolute inset-0 bg-white z-20 pointer-events-none animate-out fade-out duration-200"
                    ></div>

                    <!-- Loading State -->
                    <div
                        v-if="isCameraLoading"
                        class="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center text-white gap-3 z-10"
                    >
                        <Loader2 class="w-8 h-8 text-orange-400 animate-spin" />
                        <p class="text-xs font-semibold">Mengaktifkan kamera...</p>
                    </div>

                    <!-- Error State -->
                    <div
                        v-if="cameraError"
                        class="absolute inset-0 bg-slate-950/95 flex flex-col items-center justify-center text-white p-6 text-center gap-3 z-10"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center">
                            <AlertCircle class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-bold text-rose-300 max-w-sm">{{ cameraError }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-2">
                            <button
                                type="button"
                                @click="startCameraStream"
                                class="px-3.5 py-1.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold transition-colors cursor-pointer"
                            >
                                Coba Lagi
                            </button>
                            <button
                                type="button"
                                @click="triggerNativeCamera(); closeCameraModal();"
                                class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-colors cursor-pointer"
                            >
                                Buka Kamera HP
                            </button>
                            <button
                                type="button"
                                @click="fallbackFileInput?.click(); closeCameraModal();"
                                class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-colors cursor-pointer"
                            >
                                Pilih Berkas
                            </button>
                        </div>
                    </div>

                    <!-- Viewfinder Guides (Corner Marks) -->
                    <div v-if="!isCameraLoading && !cameraError" class="absolute inset-6 sm:inset-10 pointer-events-none flex flex-col justify-between">
                        <div class="flex justify-between">
                            <div class="w-6 h-6 border-t-2 border-l-2 border-orange-400/80 rounded-tl-lg"></div>
                            <div class="w-6 h-6 border-t-2 border-r-2 border-orange-400/80 rounded-tr-lg"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="w-6 h-6 border-b-2 border-l-2 border-orange-400/80 rounded-bl-lg"></div>
                            <div class="w-6 h-6 border-b-2 border-r-2 border-orange-400/80 rounded-br-lg"></div>
                        </div>
                    </div>

                    <!-- Live Tag / Tip -->
                    <div v-if="!isCameraLoading && !cameraError" class="absolute top-3 left-3 pointer-events-none">
                        <span class="px-2.5 py-1 rounded-full bg-slate-900/70 backdrop-blur-xs text-[10px] text-white font-medium flex items-center gap-1.5 border border-white/10">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Live Kamera Aktif
                        </span>
                    </div>
                </div>

                <!-- Footer Toolbar / Shutter Button -->
                <div class="p-4 bg-slate-900 border-t border-slate-800 flex items-center justify-between gap-3 shrink-0">
                    <!-- Mobile Camera Trigger button inside modal -->
                    <button
                        type="button"
                        @click="triggerNativeCamera(); closeCameraModal();"
                        class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors"
                        title="Gunakan aplikasi kamera bawaan perangkat"
                    >
                        <Smartphone class="w-3.5 h-3.5 text-orange-400" />
                        <span class="hidden sm:inline">Kamera Bawaan HP</span>
                    </button>

                    <!-- Main Shutter Button -->
                    <div class="flex items-center justify-center">
                        <button
                            type="button"
                            @click="captureWebcamSnapshot"
                            :disabled="isCameraLoading || !!cameraError"
                            class="group relative flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white border-4 border-orange-500/40 hover:border-orange-500 shadow-xl hover:scale-105 active:scale-95 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Jepret Foto Sekarang"
                        >
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-orange-500 group-hover:bg-orange-600 transition-colors flex items-center justify-center text-white">
                                <Camera class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </button>
                    </div>

                    <!-- Switch Camera Button on Right -->
                    <button
                        type="button"
                        @click="switchCamera"
                        class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors"
                        title="Putar Kamera Depan/Belakang"
                    >
                        <span class="hidden sm:inline">Putar Kamera</span>
                        <SwitchCamera class="w-3.5 h-3.5 text-orange-400" />
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
