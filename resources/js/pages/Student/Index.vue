<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    GraduationCap,
    Plus,
    Search,
    Edit2,
    Trash2,
    X,
    Maximize2,
    Minimize2,
    Minus,
    CheckCircle2,
    XCircle,
    Loader2,
    ChevronLeft,
    ChevronRight,
    Move,
    Sparkles,
    Check,
    Phone,
    Layers,
    Copy,
    Calendar,
    FileSpreadsheet,
    Download,
    UploadCloud,
    HelpCircle,
    UserX,
    UserCheck,
    ArrowRight,
    Info,
    KeyRound,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface AcademicYearItem {
    id: string;
    name: string;
    is_active: boolean;
}

interface StudyGroupOption {
    id: string;
    name: string;
    education_level?: 'SD' | 'SMP' | 'SMA' | null;
}

interface StudentItem {
    id: string;
    academic_year_id: string;
    study_group_id: string | null;
    study_group?: StudyGroupOption | null;
    name: string;
    parent_phone: string | null;
    student_phone: string | null;
    photo: string | null;
    photo_url: string | null;
    status: 'active' | 'inactive';
    username?: string | null;
    plain_password?: string | null;
    created_at: string;
}

interface Props {
    students: {
        data: StudentItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    currentYear: AcademicYearItem | null;
    allYears: AcademicYearItem[];
    studyGroups: StudyGroupOption[];
    stats: {
        total: number;
        active: number;
        inactive: number;
        unassigned: number;
    };
    filters: {
        search?: string;
        status?: string;
        study_group_id?: string;
        academic_year_id?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

// ==========================================
// SEARCH & FILTER STATE (Rule #8 Debounce)
// ==========================================
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const groupFilter = ref(props.filters.study_group_id || '');
const selectedYearId = ref(props.currentYear?.id || '');
let searchTimer: any = null;

const applySearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/students', {
            academic_year_id: selectedYearId.value || undefined,
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
            study_group_id: groupFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
};

watch(searchQuery, applySearch);
watch([statusFilter, groupFilter, selectedYearId], () => {
    router.get('/students', {
        academic_year_id: selectedYearId.value || undefined,
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        study_group_id: groupFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// ==========================================
// MODAL STATE: DRAGGABLE, MAXIMIZE, MINIMIZE
// ==========================================
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<string | null>(null);

const isMaximized = ref(false);
const isMinimized = ref(false);

const modalPos = reactive({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = reactive({ mouseX: 0, mouseY: 0, posX: 0, posY: 0 });
const hasCustomPos = ref(false);

const startDrag = (e: MouseEvent) => {
    if (isMaximized.value || isMinimized.value) return;
    isDragging.value = true;
    dragStart.mouseX = e.clientX;
    dragStart.mouseY = e.clientY;
    dragStart.posX = modalPos.x;
    dragStart.posY = modalPos.y;

    window.addEventListener('mousemove', onDrag);
    window.addEventListener('mouseup', stopDrag);
};

const onDrag = (e: MouseEvent) => {
    if (!isDragging.value) return;
    const dx = e.clientX - dragStart.mouseX;
    const dy = e.clientY - dragStart.mouseY;
    modalPos.x = dragStart.posX + dx;
    modalPos.y = dragStart.posY + dy;
    hasCustomPos.value = true;
};

const stopDrag = () => {
    isDragging.value = false;
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('mouseup', stopDrag);
};

const toggleMaximize = () => {
    isMaximized.value = !isMaximized.value;
    if (isMaximized.value) isMinimized.value = false;
};

const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
};

// ==========================================
// FORM STATE & PHOTO UPLOAD DRAG-AND-DROP
// ==========================================
const form = useForm({
    name: '',
    academic_year_id: props.currentYear?.id || '',
    study_group_id: '' as string | null,
    parent_phone: '',
    student_phone: '',
    status: 'active' as 'active' | 'inactive',
    photo: null as File | null,
    _method: 'POST',
});

const photoPreviewUrl = ref<string | null>(null);
const isPhotoDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const handlePhotoSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        processPhotoFile(target.files[0]);
    }
};

const handlePhotoDrop = (e: DragEvent) => {
    isPhotoDragging.value = false;
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
        processPhotoFile(e.dataTransfer.files[0]);
    }
};

const processPhotoFile = (file: File) => {
    if (!file.type.startsWith('image/')) {
        toast('Hanya berkas gambar (JPG, PNG, WEBP) yang diperbolehkan.', 'error');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast('Ukuran gambar maksimal 2MB.', 'warning');
        return;
    }
    form.photo = file;
    photoPreviewUrl.value = URL.createObjectURL(file);
};

const removePhoto = () => {
    form.photo = null;
    photoPreviewUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// Open Modal for Create
const openCreateModal = () => {
    if (!props.currentYear) {
        toast('Silakan tentukan tahun pelajaran terlebih dahulu.', 'warning');
        return;
    }
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.academic_year_id = props.currentYear.id;
    form.status = 'active';
    form._method = 'POST';
    photoPreviewUrl.value = null;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

// Open Modal for Edit
const openEditModal = (student: StudentItem) => {
    isEditing.value = true;
    editingId.value = student.id;
    form.reset();
    form.clearErrors();
    form.name = student.name;
    form.academic_year_id = student.academic_year_id;
    form.study_group_id = student.study_group_id || '';
    form.parent_phone = student.parent_phone || '';
    form.student_phone = student.student_phone || '';
    form.status = student.status;
    form.photo = null;
    form._method = 'PUT';
    photoPreviewUrl.value = student.photo_url || null;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    photoPreviewUrl.value = null;
};

// Save Student
const saveStudent = () => {
    if (isEditing.value && editingId.value) {
        form.post(`/students/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Data peserta didik berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal memperbarui data peserta didik.', 'error');
            },
        });
    } else {
        form.post('/students', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Peserta didik baru berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menambahkan peserta didik.', 'error');
            },
        });
    }
};

// Toggle Active / Inactive (Menonaktifkan Siswa Keluar dari Bimbel)
const toggleStudentStatus = async (student: StudentItem) => {
    const isDeactivating = student.status === 'active';
    const actionText = isDeactivating ? 'Nonaktifkan (Siswa Keluar)' : 'Aktifkan Kembali';
    const descText = isDeactivating
        ? `Apakah Anda ingin menonaktifkan peserta didik "${student.name}"? Ini menandakan siswa telah keluar atau berhenti dari program bimbel.`
        : `Apakah Anda ingin mengaktifkan kembali peserta didik "${student.name}"?`;

    const confirmed = await confirmAction({
        title: `${actionText}?`,
        text: descText,
        confirmButtonText: isDeactivating ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.post(`/students/${student.id}/toggle-status`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Status "${student.name}" berhasil diubah.`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal mengubah status peserta didik.', 'error');
            },
        });
    }
};

// Delete Student
const deleteStudent = async (student: StudentItem) => {
    const confirmed = await confirmAction({
        title: 'Hapus Peserta Didik?',
        text: `Apakah Anda yakin ingin menghapus "${student.name}"? Data akan diarsipkan (soft delete).`,
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/students/${student.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Data "${student.name}" berhasil dihapus.`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menghapus data peserta didik.', 'error');
            },
        });
    }
};

// ==========================================
// EXCEL IMPORT MODAL & TEMPLATE DOWNLOAD
// ==========================================
const isImportModalOpen = ref(false);
const importForm = useForm({
    academic_year_id: props.currentYear?.id || '',
    file: null as File | null,
});
const isExcelDragging = ref(false);
const excelFileInputRef = ref<HTMLInputElement | null>(null);

const openImportModal = () => {
    importForm.reset();
    importForm.academic_year_id = props.currentYear?.id || '';
    isImportModalOpen.value = true;
};

const handleExcelSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.file = target.files[0];
    }
};

const handleExcelDrop = (e: DragEvent) => {
    isExcelDragging.value = false;
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
        importForm.file = e.dataTransfer.files[0];
    }
};

const executeImport = () => {
    if (!importForm.file) {
        toast('Pilih berkas Excel terlebih dahulu.', 'warning');
        return;
    }

    importForm.post('/students/import-excel', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Import data peserta didik berhasil!', 'success');
            isImportModalOpen.value = false;
        },
        onError: (err: any) => {
            toast(err.error || 'Gagal melakukan import data Excel.', 'error');
        },
    });
};

// ==========================================
// SALIN DATA DARI TAHUN LALU
// ==========================================
const isCopyModalOpen = ref(false);
const copyForm = useForm({
    source_year_id: '',
    target_year_id: props.currentYear?.id || '',
});

const openCopyModal = () => {
    copyForm.reset();
    copyForm.target_year_id = props.currentYear?.id || '';
    isCopyModalOpen.value = true;
};

const executeCopyStudents = () => {
    copyForm.post('/students/copy-from-previous', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Data peserta didik berhasil disalin dari tahun ajaran sebelumnya!', 'success');
            isCopyModalOpen.value = false;
        },
        onError: (err: any) => {
            toast(err.error || 'Gagal menyalin peserta didik.', 'error');
        },
    });
};

// ==========================================
// PANDUAN PENGGUNA MODAL
// ==========================================
const isGuideModalOpen = ref(false);

// ==========================================
// GENERATE & RESET AKUN PESERTA DIDIK
// ==========================================
const isGeneratingAccounts = ref(false);

const hasAccounts = computed(() => {
    return props.students.data.some((s) => !!s.username);
});

const handleGenerateAccounts = async () => {
    const confirmed = await confirmAction({
        title: 'Generate Akun Peserta Didik?',
        text: 'Sistem akan membuat Username format 261xxx dan Password 4 digit angka untuk semua peserta didik yang belum memiliki akun pada tahun ajaran ini.',
        confirmText: 'Ya, Buat Akun',
        icon: 'question',
    });

    if (confirmed) {
        isGeneratingAccounts.value = true;
        router.post('/students/generate-accounts', {
            academic_year_id: selectedYearId.value,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isGeneratingAccounts.value = false;
            },
            onSuccess: () => {
                toast('Proses pembuatan akun peserta didik berhasil dijalankan!', 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal men-generate akun peserta didik.', 'error');
            },
        });
    }
};

const handleResetPassword = async (student: StudentItem) => {
    const confirmed = await confirmAction({
        title: `Reset Password ${student.name}?`,
        text: 'Sistem akan menghasilkan 4 digit kata sandi acak baru untuk peserta didik ini.',
        confirmText: 'Ya, Reset Password',
        icon: 'warning',
    });

    if (confirmed) {
        router.post(`/students/${student.id}/reset-password`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Password untuk ${student.name} berhasil direset!`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal mereset password.', 'error');
            },
        });
    }
};

const downloadAccountsExcel = () => {
    const params = new URLSearchParams();
    if (selectedYearId.value) {
        params.append('academic_year_id', selectedYearId.value);
    }
    window.location.href = `/students/export-accounts?${params.toString()}`;
};
</script>

<template>
    <Head title="Manajemen Peserta Didik" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-orange-600 mb-1">
                        <GraduationCap class="h-4 w-4" />
                        <span>Data Pokok Peserta Didik</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        Peserta Didik (Siswa)
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola data induk siswa bimbel, kontak orang tua, foto profil, dan status keaktifan.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Panduan Button -->
                    <button
                        @click="isGuideModalOpen = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-all cursor-pointer"
                        title="Buka panduan import & pendataan siswa"
                    >
                        <HelpCircle class="h-4 w-4 text-orange-500" />
                        <span>Panduan</span>
                    </button>

                    <!-- Unduh Rekap Akun (Excel 1 Kelompok 1 Sheet) -->
                    <button
                        v-if="hasAccounts"
                        @click="downloadAccountsExcel"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-amber-800 bg-amber-50 border border-amber-300 hover:bg-amber-100 transition-all cursor-pointer shadow-xs"
                        title="Unduh rekap akun siswa format Excel (1 kelompok 1 sheet)"
                    >
                        <Download class="h-4 w-4 text-amber-600" />
                        <span>Unduh Rekap Akun</span>
                    </button>

                    <!-- Generate Akun Siswa -->
                    <button
                        @click="handleGenerateAccounts"
                        :disabled="isGeneratingAccounts"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-orange-700 bg-orange-50 border border-orange-200 hover:bg-orange-100 transition-all cursor-pointer disabled:opacity-50"
                        title="Generate username format 261xxx dan password 4 digit untuk siswa yang belum punya akun"
                    >
                        <Loader2 v-if="isGeneratingAccounts" class="h-4 w-4 animate-spin text-orange-600" />
                        <KeyRound v-else class="h-4 w-4 text-orange-600" />
                        <span>Generate Akun</span>
                    </button>

                    <!-- Salin dari Tahun Lalu -->
                    <button
                        @click="openCopyModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 transition-all cursor-pointer"
                    >
                        <Copy class="h-4 w-4 text-orange-500" />
                        <span>Salin dari Tahun Lalu</span>
                    </button>

                    <!-- Import Excel -->
                    <button
                        @click="openImportModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-all cursor-pointer"
                    >
                        <FileSpreadsheet class="h-4 w-4 text-emerald-600" />
                        <span>Import Excel</span>
                    </button>

                    <!-- Tambah Siswa -->
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Siswa</span>
                    </button>
                </div>
            </div>

            <!-- ACADEMIC YEAR ACTIVE BANNER & SELECTOR -->
            <div class="bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 rounded-2xl p-4 text-white shadow-md shadow-orange-500/15 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center font-bold">
                        <Calendar class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-orange-100 uppercase tracking-wider">Tahun Pelajaran Aktif:</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-white text-orange-600">
                                {{ currentYear ? 'Periode Berjalan' : 'Belum Ditentukan' }}
                            </span>
                        </div>
                        <h2 class="text-lg font-black tracking-tight">
                            {{ currentYear ? currentYear.name : 'Silakan Buat Tahun Pelajaran' }}
                        </h2>
                    </div>
                </div>

                <!-- Academic Year Switcher -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-orange-100">Ganti Periode:</span>
                    <select
                        v-model="selectedYearId"
                        class="h-9 px-3 rounded-xl bg-white/10 text-white font-bold text-xs border border-white/20 focus:bg-white focus:text-slate-900 focus:outline-none transition-all cursor-pointer"
                    >
                        <option v-for="y in allYears" :key="y.id" :value="y.id" class="text-slate-900 font-semibold">
                            {{ y.name }} {{ y.is_active ? '(Aktif)' : '' }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- STATS SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Siswa</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.total }} <span class="text-xs font-medium text-slate-500">Orang</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Siswa Aktif</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.active }} <span class="text-xs font-medium text-slate-500">Aktif Bimbel</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <UserX class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Siswa Keluar / Cuti</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.inactive }} <span class="text-xs font-medium text-slate-500">Nonaktif</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Belum Masuk Kelompok</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.unassigned }} <span class="text-xs font-medium text-slate-500">Perlu Mapping</span></p>
                    </div>
                </div>
            </div>

            <!-- FILTER & SEARCH BAR -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama atau No. HP siswa/ortu..."
                        class="w-full h-10.5 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <!-- Filter Kelompok -->
                    <select
                        v-model="groupFilter"
                        class="h-10.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-700 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                    >
                        <option value="">Semua Kelompok Bimbel</option>
                        <option value="unassigned">(Belum Masuk Kelompok)</option>
                        <option v-for="g in studyGroups" :key="g.id" :value="g.id">
                            {{ g.name }}{{ g.education_level ? ` - ${g.education_level}` : '' }}
                        </option>
                    </select>

                    <!-- Filter Status -->
                    <select
                        v-model="statusFilter"
                        class="h-10.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-700 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif Bimbel</option>
                        <option value="inactive">Nonaktif / Keluar</option>
                    </select>
                </div>
            </div>

            <!-- DATA TABLE -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Nama Peserta Didik</th>
                                <th class="py-3.5 px-4">Kelompok Bimbel</th>
                                <th class="py-3.5 px-4">Kontak WhatsApp (Ortu & Siswa)</th>
                                <th class="py-3.5 px-4 text-center">Status Siswa</th>
                                <th class="py-3.5 px-4 text-right w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(student, index) in students.data"
                                :key="student.id"
                                class="hover:bg-orange-50/20 transition-colors group"
                                :class="student.status === 'inactive' ? 'opacity-65 bg-slate-50/40' : ''"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ (students.current_page - 1) * students.per_page + index + 1 }}
                                </td>

                                <!-- Photo & Name -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative h-10 w-10 rounded-full shrink-0 overflow-hidden bg-gradient-to-tr from-orange-500 to-amber-400 text-white flex items-center justify-center font-bold text-xs ring-2 ring-orange-500/20">
                                            <img
                                                v-if="student.photo_url"
                                                :src="student.photo_url"
                                                :alt="student.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span v-else>{{ student.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-sm group-hover:text-orange-600 transition-colors">
                                                {{ student.name }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span v-if="student.username" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-orange-100 text-orange-800 text-[10px] font-mono font-bold" title="Username Siswa">
                                                    <KeyRound class="h-2.5 w-2.5" />
                                                    <span>{{ student.username }}</span>
                                                </span>
                                                <span v-else class="text-[10px] text-slate-400 italic">
                                                    Belum ada akun
                                                </span>
                                                <span v-if="student.plain_password" class="text-[10px] text-slate-400 font-mono" title="Password Akun">
                                                    (Pass: {{ student.plain_password }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kelompok Bimbel -->
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="student.study_group"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 text-[11px] font-bold border border-orange-200/60"
                                    >
                                        <Layers class="h-3 w-3" />
                                        <span>{{ student.study_group.name }}</span>
                                        <span
                                            v-if="student.study_group.education_level"
                                            class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider bg-white border border-orange-300 text-orange-800"
                                        >
                                            {{ student.study_group.education_level }}
                                        </span>
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 italic">
                                        (Belum Masuk Kelompok)
                                    </span>
                                </td>

                                <!-- WhatsApp Kontak -->
                                <td class="py-3.5 px-4 space-y-1">
                                    <div v-if="student.parent_phone" class="flex items-center gap-1.5 text-[11px] text-slate-700 font-medium">
                                        <Phone class="h-3.5 w-3.5 text-emerald-500" />
                                        <span class="text-slate-400 text-[10px]">Ortu:</span>
                                        <a :href="'https://wa.me/' + student.parent_phone.replace(/[^0-9]/g, '')" target="_blank" class="hover:text-emerald-600 hover:underline">
                                            {{ student.parent_phone }}
                                        </a>
                                    </div>
                                    <div v-if="student.student_phone" class="flex items-center gap-1.5 text-[11px] text-slate-700 font-medium">
                                        <Phone class="h-3.5 w-3.5 text-blue-500" />
                                        <span class="text-slate-400 text-[10px]">Siswa:</span>
                                        <a :href="'https://wa.me/' + student.student_phone.replace(/[^0-9]/g, '')" target="_blank" class="hover:text-blue-600 hover:underline">
                                            {{ student.student_phone }}
                                        </a>
                                    </div>
                                    <span v-if="!student.parent_phone && !student.student_phone" class="text-slate-400 italic text-[11px]">
                                        Belum ada nomor kontak
                                    </span>
                                </td>

                                <!-- Status Aktif / Keluar -->
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        @click="toggleStudentStatus(student)"
                                        type="button"
                                        class="cursor-pointer transition-transform active:scale-95 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        :class="[
                                            student.status === 'active'
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                                : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100'
                                        ]"
                                        :title="student.status === 'active' ? 'Klik untuk menonaktifkan siswa (keluar bimbel)' : 'Klik untuk mengaktifkan kembali'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="student.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                        <span>{{ student.status === 'active' ? 'Aktif Bimbel' : 'Keluar / Cuti' }}</span>
                                    </button>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Reset Password Siswa (Hanya jika sudah ada akun) -->
                                        <button
                                            v-if="student.username"
                                            @click="handleResetPassword(student)"
                                            type="button"
                                            class="p-2 rounded-lg text-amber-600 hover:text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer"
                                            title="Reset Password Siswa (4 Digit Baru)"
                                        >
                                            <KeyRound class="h-3.5 w-3.5" />
                                        </button>

                                        <button
                                            @click="openEditModal(student)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors cursor-pointer"
                                            title="Edit Data Siswa"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            @click="deleteStudent(student)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Siswa"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="students.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Tidak ada data peserta didik ditemukan. Klik "Tambah Siswa" atau "Import Excel" untuk menambahkan data.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="students.last_page > 1" class="px-4 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <p class="text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-700">{{ students.from || 0 }}</span> - <span class="font-semibold text-slate-700">{{ students.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ students.total }}</span> siswa
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, i) in students.links"
                            :key="i"
                            :disabled="!link.url"
                            @click="router.visit(link.url!)"
                            class="min-h-8 min-w-8 px-2 rounded-lg font-semibold flex items-center justify-center transition-all cursor-pointer"
                            :class="[
                                link.active ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        >
                            <span v-if="link.label.includes('Previous')"><ChevronLeft class="h-3.5 w-3.5" /></span>
                            <span v-else-if="link.label.includes('Next')"><ChevronRight class="h-3.5 w-3.5" /></span>
                            <span v-else v-html="link.label"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- MODAL TAMBAH / EDIT SISWA -->
        <!-- ===================================================== -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden transition-all duration-200"
            :class="isMinimized ? 'pointer-events-none opacity-0' : 'bg-slate-950/60 backdrop-blur-xs'"
            @click.self="() => {}"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 flex flex-col transition-all duration-150 overflow-hidden"
                :class="isMaximized ? 'w-full h-full rounded-none' : 'w-full max-w-lg mx-4 max-h-[92vh]'"
                :style="!isMaximized && hasCustomPos ? { transform: `translate3d(${modalPos.x}px, ${modalPos.y}px, 0px)` } : {}"
            >
                <!-- HEADER -->
                <div
                    @mousedown="startDrag"
                    class="px-5 py-4 bg-gradient-to-r from-orange-50 via-amber-50/40 to-white border-b border-orange-100/60 flex items-center justify-between select-none cursor-move shrink-0"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
                            <GraduationCap class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-black text-slate-900">
                                    {{ isEditing ? 'Perbarui Data Peserta Didik' : 'Tambah Peserta Didik Baru' }}
                                </h3>
                                <Move class="h-3 w-3 text-slate-400 opacity-60" />
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium">Geser header ini untuk memindahkan letak modal</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1" @mousedown.stop>
                        <button type="button" @click="toggleMinimize" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                            <Minus class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" @click="toggleMaximize" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                            <Minimize2 v-if="isMaximized" class="h-3.5 w-3.5" />
                            <Maximize2 v-else class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" @click="closeModal" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- BODY -->
                <div v-show="!isMinimized" class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4">
                    <form @submit.prevent="saveStudent" id="studentForm" class="space-y-4">
                        <!-- 1. Nama Lengkap Siswa -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama Lengkap Peserta Didik <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Muhammad Farhan Pratama"
                                class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <!-- 2. Kelompok Bimbel -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Kelompok Bimbel</label>
                            <select
                                v-model="form.study_group_id"
                                class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            >
                                <option value="">(Belum Masuk Kelompok / Bebas)</option>
                                <option v-for="g in studyGroups" :key="g.id" :value="g.id">
                                    {{ g.name }}{{ g.education_level ? ` - ${g.education_level}` : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- 3. Nomor HP Orang Tua & Siswa -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Nomor WhatsApp Orang Tua</label>
                                <input
                                    v-model="form.parent_phone"
                                    type="text"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Nomor WhatsApp Siswa</label>
                                <input
                                    v-model="form.student_phone"
                                    type="text"
                                    placeholder="Contoh: 085712345678"
                                    class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- 4. Upload Foto Profil (Drag and drop) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Foto Profil Peserta Didik (Opsional)</label>
                            <div
                                @dragover.prevent="isPhotoDragging = true"
                                @dragleave.prevent="isPhotoDragging = false"
                                @drop.prevent="handlePhotoDrop"
                                class="border-2 border-dashed rounded-2xl p-4 transition-all flex items-center gap-4"
                                :class="[
                                    isPhotoDragging ? 'border-orange-500 bg-orange-50/50 scale-[1.01]' : 'border-slate-200 hover:border-slate-300 bg-slate-50/40'
                                ]"
                            >
                                <div class="relative h-16 w-16 rounded-2xl shrink-0 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200">
                                    <img
                                        v-if="photoPreviewUrl"
                                        :src="photoPreviewUrl"
                                        alt="Preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <GraduationCap v-else class="h-8 w-8 text-slate-400" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-700">Tarik & Letakkan foto di sini</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Mendukung format JPG, PNG, WEBP (Maksimal 2MB)</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <button
                                            type="button"
                                            @click="fileInputRef?.click()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold text-orange-600 bg-orange-50 hover:bg-orange-100 transition-colors cursor-pointer"
                                        >
                                            Pilih Berkas
                                        </button>
                                        <button
                                            v-if="photoPreviewUrl"
                                            type="button"
                                            @click="removePhoto"
                                            class="px-2 py-1 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                        >
                                            Hapus Foto
                                        </button>
                                    </div>
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handlePhotoSelect"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 5. Status Aktif / Keluar -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Status Keaktifan</label>
                            <div
                                @click="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex items-center justify-between"
                                :class="form.status === 'active' ? 'bg-orange-50/90 border-orange-200' : 'bg-slate-50/70 border-slate-200'"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-8 w-8 rounded-xl flex items-center justify-center shrink-0"
                                        :class="form.status === 'active' ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
                                    >
                                        <UserCheck v-if="form.status === 'active'" class="h-4 w-4" />
                                        <UserX v-else class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-bold text-slate-900">
                                                {{ form.status === 'active' ? 'Peserta Didik Aktif' : 'Peserta Didik Keluar / Cuti' }}
                                            </p>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                                                :class="form.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                            >
                                                {{ form.status === 'active' ? 'Aktif' : 'Keluar' }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            {{ form.status === 'active' ? 'Siswa dapat dipresensi pada sesi kelas aktif.' : 'Siswa telah keluar dari program bimbel.' }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                    :class="form.status === 'active' ? 'bg-orange-500' : 'bg-slate-300'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                        :class="form.status === 'active' ? 'translate-x-5' : 'translate-x-0'"
                                    >
                                        <Check v-if="form.status === 'active'" class="h-3 w-3 text-orange-600 font-bold" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- FOOTER -->
                <div v-show="!isMinimized" class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <p class="text-[11px] text-slate-400"><span class="text-orange-500 font-bold">*</span> Wajib diisi</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200">
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="studentForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Siswa' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- MODAL IMPORT EXCEL -->
        <!-- ===================================================== -->
        <div
            v-if="isImportModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                            <FileSpreadsheet class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Import Data Peserta Didik (Excel)</h3>
                            <p class="text-[11px] text-slate-500">Unggah berkas .xlsx atau .csv untuk input massal</p>
                        </div>
                    </div>
                    <button type="button" @click="isImportModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Template Download Card -->
                <div class="bg-orange-50/70 border border-orange-200/80 rounded-2xl p-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <Download class="h-4 w-4 text-orange-600" />
                        <div>
                            <p class="text-xs font-bold text-orange-950">Unduh Format Template Excel</p>
                            <p class="text-[10px] text-orange-700">Lengkap dengan kolom nama kelompok otomatis</p>
                        </div>
                    </div>
                    <a
                        href="/students/export-template"
                        class="px-3 py-1.5 rounded-xl bg-white border border-orange-200 text-xs font-bold text-orange-600 hover:bg-orange-600 hover:text-white transition-all shadow-xs"
                    >
                        Unduh Template (.xlsx)
                    </a>
                </div>

                <!-- File Drag and Drop -->
                <div
                    @dragover.prevent="isExcelDragging = true"
                    @dragleave.prevent="isExcelDragging = false"
                    @drop.prevent="handleExcelDrop"
                    class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
                    :class="[
                        isExcelDragging ? 'border-emerald-500 bg-emerald-50/40 scale-[1.01]' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50'
                    ]"
                    @click="excelFileInputRef?.click()"
                >
                    <UploadCloud class="h-8 w-8 mx-auto text-slate-400 mb-2" />
                    <p v-if="importForm.file" class="text-xs font-bold text-emerald-600">
                        Berkas Terpilih: {{ importForm.file.name }}
                    </p>
                    <div v-else>
                        <p class="text-xs font-bold text-slate-700">Tarik berkas ke sini atau klik untuk memilih</p>
                        <p class="text-[11px] text-slate-400 mt-1">Mendukung format .xlsx, .xls, atau .csv (Maks. 5MB)</p>
                    </div>
                    <input
                        ref="excelFileInputRef"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="handleExcelSelect"
                    />
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="isImportModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200">
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeImport"
                        :disabled="importForm.processing || !importForm.file"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 shadow-md shadow-emerald-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                    >
                        <Loader2 v-if="importForm.processing" class="h-3.5 w-3.5 animate-spin" />
                        <span>Mulai Import Data</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- MODAL SALIN DARI TAHUN LALU -->
        <!-- ===================================================== -->
        <div
            v-if="isCopyModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
                            <Copy class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Salin Siswa dari Tahun Lalu</h3>
                            <p class="text-[11px] text-slate-500">Duplikasi data siswa aktif ke tahun pelajaran baru</p>
                        </div>
                    </div>
                    <button type="button" @click="isCopyModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="executeCopyStudents" class="space-y-3 pt-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Tahun Pelajaran Sumber (Asal)</label>
                        <select
                            v-model="copyForm.source_year_id"
                            required
                            class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                        >
                            <option value="" disabled>Pilih Tahun Asal...</option>
                            <option v-for="y in allYears.filter(y => y.id !== currentYear?.id)" :key="y.id" :value="y.id">
                                {{ y.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-center py-1 text-slate-400">
                        <ArrowRight class="h-5 w-5" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Tahun Pelajaran Tujuan (Saat Ini)</label>
                        <input
                            type="text"
                            disabled
                            :value="currentYear ? currentYear.name : 'Tidak Ada'"
                            class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-600 cursor-not-allowed"
                        />
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button type="button" @click="isCopyModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="copyForm.processing || !copyForm.source_year_id"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="copyForm.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>Mulai Salin Siswa</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- MODAL PANDUAN PENGGUNA (USER GUIDE) -->
        <!-- ===================================================== -->
        <div
            v-if="isGuideModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="px-5 py-4 bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold">
                            <Info class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">Panduan Pengelolaan Peserta Didik</h3>
                            <p class="text-[11px] text-slate-500">Petunjuk penginputan data, pemetaan kelompok & import Excel</p>
                        </div>
                    </div>
                    <button type="button" @click="isGuideModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4 text-xs text-slate-600 leading-relaxed">
                    <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100 space-y-2">
                        <h4 class="font-black text-slate-900 flex items-center gap-1.5 text-sm">
                            <CheckCircle2 class="h-4 w-4 text-orange-500" />
                            1. Menambahkan Siswa Secara Manual
                        </h4>
                        <p>Klik tombol <strong>"+ Tambah Siswa"</strong> di pojok kanan atas. Isi nama lengkap siswa (wajib), nomor WhatsApp orang tua (untuk notifikasi absensi otomatis), nomor HP siswa, pilih kelompok bimbel, dan tentukan status keaktifan.</p>
                    </div>

                    <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100 space-y-2">
                        <h4 class="font-black text-slate-900 flex items-center gap-1.5 text-sm">
                            <FileSpreadsheet class="h-4 w-4 text-emerald-600" />
                            2. Import Data Massal Menggunakan Excel
                        </h4>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Klik tombol <strong>"Import Excel"</strong>.</li>
                            <li>Unduh template resmi dengan mengklik <strong>"Unduh Template (.xlsx)"</strong>.</li>
                            <li>Buka template di Microsoft Excel atau Google Sheets.</li>
                            <li>Isi kolom: <em>Nama Siswa</em> (wajib), <em>No HP Ortu</em>, <em>No HP Siswa</em>, <em>Nama Kelompok Bimbel</em>, dan <em>Status</em>.</li>
                            <li><strong>Auto-Mapping Kelompok:</strong> Kolom <em>Nama Kelompok Bimbel</em> otomatis mencocokkan atau membuat kelompok baru jika belum ada.</li>
                            <li>Simpan dan unggah kembali berkas ke sistem.</li>
                        </ol>
                    </div>

                    <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 space-y-2">
                        <h4 class="font-black text-slate-900 flex items-center gap-1.5 text-sm">
                            <Copy class="h-4 w-4 text-blue-600" />
                            3. Salin Data Antar Tahun Pelajaran
                        </h4>
                        <p>Ketika memasuki tahun ajaran baru, Anda tidak perlu menginput ulang seluruh siswa. Cukup gunakan tombol <strong>"Salin dari Tahun Lalu"</strong> untuk menduplikasi seluruh siswa aktif ke tahun ajaran baru secara instan.</p>
                    </div>

                    <div class="bg-rose-50/50 p-4 rounded-2xl border border-rose-100 space-y-2">
                        <h4 class="font-black text-slate-900 flex items-center gap-1.5 text-sm">
                            <UserX class="h-4 w-4 text-rose-600" />
                            4. Menonaktifkan Siswa yang Keluar dari Bimbel
                        </h4>
                        <p>Jika ada peserta didik yang keluar, lulus, atau berhenti dari bimbingan belajar, klik badge <strong>"Aktif Bimbel"</strong> pada baris tabel siswa tersebut. Sistem akan mengonfirmasi penonaktifan tanpa menghapus riwayat kehadiran yang sudah tercatat.</p>
                    </div>
                </div>

                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="isGuideModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 transition-colors"
                    >
                        Saya Paham
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
