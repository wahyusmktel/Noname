<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Layers,
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
    Users,
    Copy,
    UserCheck,
    Calendar,
    ArrowRight,
    ArrowLeft,
    GripVertical,
    UserPlus,
    UserMinus,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface AcademicYearItem {
    id: string;
    name: string;
    is_active: boolean;
}

interface StudentItem {
    id: string;
    name: string;
    study_group_id: string | null;
    student_phone: string | null;
}

interface StudyGroupItem {
    id: string;
    name: string;
    education_level: 'SD' | 'SMP' | 'SMA';
    academic_year_id: string;
    is_active: boolean;
    students_count?: number;
    created_at: string;
}

interface Props {
    studyGroups: {
        data: StudyGroupItem[];
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
    studentsInYear: StudentItem[];
    stats: {
        total: number;
        active: number;
        total_students: number;
    };
    filters: {
        search?: string;
        status?: string;
        education_level?: string;
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
const educationLevelFilter = ref(props.filters.education_level || '');
const selectedYearId = ref(props.currentYear?.id || '');
let searchTimer: any = null;

const applySearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/study-groups', {
            academic_year_id: selectedYearId.value || undefined,
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
            education_level: educationLevelFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
};

watch(searchQuery, applySearch);
watch([statusFilter, educationLevelFilter, selectedYearId], () => {
    router.get('/study-groups', {
        academic_year_id: selectedYearId.value || undefined,
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        education_level: educationLevelFilter.value || undefined,
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
// CREATE & EDIT FORM
// ==========================================
const form = useForm({
    name: '',
    education_level: 'SD' as 'SD' | 'SMP' | 'SMA',
    academic_year_id: props.currentYear?.id || '',
    is_active: true,
});

const openCreateModal = () => {
    if (!props.currentYear) {
        toast('Silakan buat tahun pelajaran terlebih dahulu.', 'warning');
        return;
    }
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.education_level = 'SD';
    form.academic_year_id = props.currentYear.id;
    form.is_active = true;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

const openEditModal = (group: StudyGroupItem) => {
    isEditing.value = true;
    editingId.value = group.id;
    form.reset();
    form.clearErrors();
    form.name = group.name;
    form.education_level = group.education_level || 'SD';
    form.academic_year_id = group.academic_year_id;
    form.is_active = Boolean(group.is_active);
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const saveStudyGroup = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/study-groups/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Kelompok bimbel berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal memperbarui kelompok bimbel.', 'error');
            },
        });
    } else {
        form.post('/study-groups', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Kelompok bimbel baru berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menambahkan kelompok bimbel.', 'error');
            },
        });
    }
};

const deleteStudyGroup = async (group: StudyGroupItem) => {
    const confirmed = await confirmAction({
        title: 'Hapus Kelompok Bimbel?',
        text: `Apakah Anda yakin ingin menghapus "${group.name}"? Siswa di dalam kelompok ini akan menjadi "Belum Masuk Kelompok".`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/study-groups/${group.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Kelompok "${group.name}" berhasil dihapus.`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menghapus kelompok bimbel.', 'error');
            },
        });
    }
};

// ==========================================
// MAPPING SISWA MODAL (2-KOLOM DRAG & DROP)
// ==========================================
const isMappingModalOpen = ref(false);
const activeMappingGroup = ref<StudyGroupItem | null>(null);
const isMappingSubmitting = ref(false);

// State 2 Kolom Siswa
const assignedStudents = ref<StudentItem[]>([]);
const unassignedStudents = ref<StudentItem[]>([]);
const initialAssignedIds = ref<string[]>([]);

// State Pencarian Masing-Masing Kolom
const searchAssigned = ref('');
const searchUnassigned = ref('');

// State Drag and Drop
const draggedStudent = ref<StudentItem | null>(null);
const dragSource = ref<'assigned' | 'unassigned' | null>(null);
const isDragOverAssigned = ref(false);
const isDragOverUnassigned = ref(false);

const openMappingModal = (group: StudyGroupItem) => {
    activeMappingGroup.value = group;

    // 1. Kolom Kiri: Siswa yang saat ini tergabung pada kelompok ini
    assignedStudents.value = props.studentsInYear
        .filter((s) => s.study_group_id === group.id)
        .map((s) => ({ ...s }));

    // 2. Kolom Kanan: Siswa yang BELUM memiliki kelompok belajar sama sekali
    unassignedStudents.value = props.studentsInYear
        .filter((s) => !s.study_group_id)
        .map((s) => ({ ...s }));

    initialAssignedIds.value = assignedStudents.value.map((s) => s.id);
    searchAssigned.value = '';
    searchUnassigned.value = '';
    draggedStudent.value = null;
    dragSource.value = null;
    isDragOverAssigned.value = false;
    isDragOverUnassigned.value = false;
    isMappingModalOpen.value = true;
};

// Computed Filter Siswa
const filteredAssignedStudents = computed(() => {
    const q = searchAssigned.value.trim().toLowerCase();
    if (!q) return assignedStudents.value;
    return assignedStudents.value.filter((s) => s.name.toLowerCase().includes(q));
});

const filteredUnassignedStudents = computed(() => {
    const q = searchUnassigned.value.trim().toLowerCase();
    if (!q) return unassignedStudents.value;
    return unassignedStudents.value.filter((s) => s.name.toLowerCase().includes(q));
});

// Mutasi Siswa (Pindah Kolom)
const assignStudent = (student: StudentItem) => {
    const idx = unassignedStudents.value.findIndex((s) => s.id === student.id);
    if (idx !== -1) {
        unassignedStudents.value.splice(idx, 1);
    }
    if (!assignedStudents.value.some((s) => s.id === student.id)) {
        assignedStudents.value.push(student);
    }
};

const unassignStudent = (student: StudentItem) => {
    const idx = assignedStudents.value.findIndex((s) => s.id === student.id);
    if (idx !== -1) {
        assignedStudents.value.splice(idx, 1);
    }
    if (!unassignedStudents.value.some((s) => s.id === student.id)) {
        unassignedStudents.value.push(student);
    }
};

const assignAllVisible = () => {
    const toMove = [...filteredUnassignedStudents.value];
    toMove.forEach((s) => assignStudent(s));
};

const unassignAllVisible = () => {
    const toMove = [...filteredAssignedStudents.value];
    toMove.forEach((s) => unassignStudent(s));
};

// Drag and Drop Event Handlers
const onDragStart = (student: StudentItem, source: 'assigned' | 'unassigned', event: DragEvent) => {
    draggedStudent.value = student;
    dragSource.value = source;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', student.id);
    }
};

const onDragEnd = () => {
    draggedStudent.value = null;
    dragSource.value = null;
    isDragOverAssigned.value = false;
    isDragOverUnassigned.value = false;
};

const onDropToAssigned = () => {
    if (draggedStudent.value && dragSource.value === 'unassigned') {
        assignStudent(draggedStudent.value);
    }
    onDragEnd();
};

const onDropToUnassigned = () => {
    if (draggedStudent.value && dragSource.value === 'assigned') {
        unassignStudent(draggedStudent.value);
    }
    onDragEnd();
};

// Statistik Perubahan
const newlyAddedCount = computed(() => {
    return assignedStudents.value.filter((s) => !initialAssignedIds.value.includes(s.id)).length;
});

const removedCount = computed(() => {
    return initialAssignedIds.value.filter((id) => !assignedStudents.value.some((s) => s.id === id)).length;
});

// Simpan Pemetaan ke Server
const saveMapping = () => {
    if (!activeMappingGroup.value) return;
    isMappingSubmitting.value = true;

    const studentIds = assignedStudents.value.map((s) => s.id);

    router.post(`/study-groups/${activeMappingGroup.value.id}/map-students`, {
        student_ids: studentIds,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast(`Pemetaan ${studentIds.length} siswa ke kelompok "${activeMappingGroup.value?.name}" berhasil disimpan!`, 'success');
            isMappingModalOpen.value = false;
            isMappingSubmitting.value = false;
        },
        onError: (err: any) => {
            toast(err.error || 'Gagal menyimpan pemetaan siswa.', 'error');
            isMappingSubmitting.value = false;
        },
    });
};

// ==========================================
// SALIN ANGGOTA KELOMPOK DARI TAHUN SEBELUMNYA
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

const executeCopyGroups = () => {
    copyForm.post('/study-groups/copy-from-previous', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Kelompok bimbel dan mapping anggota berhasil disalin!', 'success');
            isCopyModalOpen.value = false;
        },
        onError: (err: any) => {
            toast(err.error || 'Gagal menyalin kelompok bimbel.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Manajemen Kelompok Bimbel" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-orange-600 mb-1">
                        <Layers class="h-4 w-4" />
                        <span>Manajemen Rombel & Kelas</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        Kelompok Bimbel
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola kelompok belajar, kelas bimbingan, dan mapping anggota siswa per tahun pelajaran.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="openCopyModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-200 transition-all cursor-pointer shadow-xs"
                    >
                        <Copy class="h-4 w-4 text-orange-500" />
                        <span>Salin dari Tahun Lalu</span>
                    </button>

                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Kelompok</span>
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
                        <span class="text-xs font-semibold text-orange-100 uppercase tracking-wider block">Tahun Pelajaran Aktif:</span>
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

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Kelompok</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.total }} <span class="text-xs font-medium text-slate-500">Kelompok</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Kelompok Aktif</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.active }} <span class="text-xs font-medium text-slate-500">Bisa Digunakan</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Siswa Sudah Masuk Kelompok</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.total_students }} <span class="text-xs font-medium text-slate-500">Peserta Didik</span></p>
                    </div>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama kelompok bimbel..."
                        class="w-full h-10.5 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select
                        v-model="educationLevelFilter"
                        class="h-10.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-700 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all w-full sm:w-36"
                    >
                        <option value="">Semua Jenjang</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                    </select>

                    <select
                        v-model="statusFilter"
                        class="h-10.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-700 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all w-full sm:w-36"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
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
                                <th class="py-3.5 px-4">Nama Kelompok Bimbel</th>
                                <th class="py-3.5 px-4 text-center">Jumlah Anggota</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">Pemetaan Siswa</th>
                                <th class="py-3.5 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(group, index) in studyGroups.data"
                                :key="group.id"
                                class="hover:bg-orange-50/25 transition-colors group"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ (studyGroups.current_page - 1) * studyGroups.per_page + index + 1 }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 font-bold">
                                            <Layers class="h-4.5 w-4.5" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-slate-900 text-sm group-hover:text-orange-600 transition-colors">
                                                    {{ group.name }}
                                                </p>
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-black tracking-wider border shadow-xs"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border-emerald-200/80': group.education_level === 'SD',
                                                        'bg-blue-50 text-blue-700 border-blue-200/80': group.education_level === 'SMP',
                                                        'bg-purple-50 text-purple-700 border-purple-200/80': group.education_level === 'SMA',
                                                    }"
                                                >
                                                    {{ group.education_level || 'SD' }}
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400">ID: {{ group.id.slice(0, 8) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700">
                                        <Users class="h-3.5 w-3.5" />
                                        {{ group.students_count || 0 }} Siswa
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="group.is_active"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                </td>

                                <!-- Tombol Mapping Siswa -->
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        @click="openMappingModal(group)"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs text-orange-600 bg-orange-50 hover:bg-orange-100 border border-orange-200/60 transition-colors cursor-pointer"
                                        title="Kelola anggota kelompok"
                                    >
                                        <UserCheck class="h-3.5 w-3.5" />
                                        <span>Mapping Siswa</span>
                                    </button>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(group)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors cursor-pointer"
                                            title="Edit Kelompok"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            @click="deleteStudyGroup(group)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Kelompok"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="studyGroups.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Tidak ada kelompok bimbel ditemukan pada periode ini. Klik "Tambah Kelompok" untuk membuat baru.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="studyGroups.last_page > 1" class="px-4 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <p class="text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-700">{{ studyGroups.from || 0 }}</span> - <span class="font-semibold text-slate-700">{{ studyGroups.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ studyGroups.total }}</span> kelompok
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, i) in studyGroups.links"
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
        <!-- MODAL CREATE / EDIT GROUP -->
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
                <div
                    @mousedown="startDrag"
                    class="px-5 py-4 bg-gradient-to-r from-orange-50 via-amber-50/40 to-white border-b border-orange-100/60 flex items-center justify-between select-none cursor-move shrink-0"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
                            <Layers class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-black text-slate-900">
                                    {{ isEditing ? 'Perbarui Kelompok Bimbel' : 'Tambah Kelompok Bimbel Baru' }}
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

                <div v-show="!isMinimized" class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4">
                    <form @submit.prevent="saveStudyGroup" id="studyGroupForm" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama Kelompok Bimbel <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Kelas 12 IPA 1, Intensif TPS A"
                                class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <!-- Pilihan Jenjang -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Jenjang Pendidikan <span class="text-orange-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="form.education_level = 'SD'"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
                                    :class="form.education_level === 'SD'
                                        ? 'bg-emerald-500 text-white border-emerald-600 shadow-md shadow-emerald-500/20'
                                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                >
                                    <span class="text-sm font-black">SD</span>
                                    <span class="text-[10px] opacity-85">Kelas 1 - 6</span>
                                </button>
                                <button
                                    type="button"
                                    @click="form.education_level = 'SMP'"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
                                    :class="form.education_level === 'SMP'
                                        ? 'bg-blue-500 text-white border-blue-600 shadow-md shadow-blue-500/20'
                                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                >
                                    <span class="text-sm font-black">SMP</span>
                                    <span class="text-[10px] opacity-85">Kelas 7 - 9</span>
                                </button>
                                <button
                                    type="button"
                                    @click="form.education_level = 'SMA'"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
                                    :class="form.education_level === 'SMA'
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-md shadow-purple-500/20'
                                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                >
                                    <span class="text-sm font-black">SMA</span>
                                    <span class="text-[10px] opacity-85">Kelas 10 - 12</span>
                                </button>
                            </div>
                            <p v-if="form.errors.education_level" class="text-[11px] text-rose-500 font-semibold">{{ form.errors.education_level }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Tahun Pelajaran</label>
                            <select
                                v-model="form.academic_year_id"
                                required
                                class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            >
                                <option v-for="y in allYears" :key="y.id" :value="y.id">
                                    {{ y.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Toggle Aktif -->
                        <div
                            @click="form.is_active = !form.is_active"
                            class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex items-center justify-between"
                            :class="form.is_active ? 'bg-orange-50/90 border-orange-200' : 'bg-slate-50/70 border-slate-200'"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-xl flex items-center justify-center shrink-0"
                                    :class="form.is_active ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
                                >
                                    <Sparkles class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">Status Kelompok Aktif</p>
                                    <p class="text-[11px] text-slate-500">Kelompok aktif dapat dipilih saat pendaftaran & absensi.</p>
                                </div>
                            </div>
                            <div
                                class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                :class="form.is_active ? 'bg-orange-500' : 'bg-slate-300'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out flex items-center justify-center"
                                    :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
                                >
                                    <Check v-if="form.is_active" class="h-3 w-3 text-orange-600 font-bold" />
                                </span>
                            </div>
                        </div>
                    </form>
                </div>

                <div v-show="!isMinimized" class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <p class="text-[11px] text-slate-400"><span class="text-orange-500 font-bold">*</span> Wajib diisi</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200">
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="studyGroupForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Kelompok' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================== -->
        <!-- MODAL MAPPING SISWA KE KELOMPOK (2-KOLOM DRAG & DROP) -->
        <!-- ===================================================== -->
        <div
            v-if="isMappingModalOpen && activeMappingGroup"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs p-3 sm:p-5"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <!-- Header Modal -->
                <div class="px-5 py-4 bg-gradient-to-r from-orange-50 via-amber-50 to-orange-100/60 border-b border-orange-100 flex items-center justify-between shrink-0">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-black text-slate-900">
                                Pemetaan Siswa ke "{{ activeMappingGroup.name }}"
                            </h3>
                            <span
                                class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border shadow-xs"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200/80': activeMappingGroup.education_level === 'SD',
                                    'bg-blue-50 text-blue-700 border-blue-200/80': activeMappingGroup.education_level === 'SMP',
                                    'bg-purple-50 text-purple-700 border-purple-200/80': activeMappingGroup.education_level === 'SMA',
                                }"
                            >
                                Jenjang {{ activeMappingGroup.education_level || 'SD' }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-orange-500 text-white shadow-xs">
                                {{ assignedStudents.length }} Anggota
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tarik dan lepas (<span class="font-bold text-orange-600">drag & drop</span>) siswa untuk memindahkan, atau klik tombol panah cepat.
                        </p>
                    </div>
                    <button type="button" @click="isMappingModalOpen = false" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- 2-Kolom Container -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4 sm:p-5 flex-1 min-h-0 overflow-y-auto">
                    <!-- ============================================== -->
                    <!-- KOLOM KIRI: ANGGOTA KELOMPOK SAAT INI (DROP ZONE) -->
                    <!-- ============================================== -->
                    <div
                        class="border-2 rounded-2xl p-3.5 sm:p-4 flex flex-col h-full min-h-[420px] transition-all duration-200 bg-orange-50/30"
                        :class="isDragOverAssigned ? 'border-orange-500 bg-orange-100/60 ring-4 ring-orange-500/20 shadow-md' : 'border-orange-200/90'"
                        @dragover.prevent="isDragOverAssigned = true"
                        @dragleave="isDragOverAssigned = false"
                        @drop.prevent="onDropToAssigned"
                    >
                        <!-- Header Kolom Kiri -->
                        <div class="flex items-center justify-between pb-3 border-b border-orange-200/60 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-xs">
                                    <UserCheck class="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                        Anggota Kelompok Ini
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        {{ assignedStudents.length }} siswa terdaftar
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="assignedStudents.length > 0"
                                type="button"
                                @click="unassignAllVisible"
                                class="text-[10px] font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200/80 transition-colors"
                                title="Keluarkan seluruh siswa yang tampil"
                            >
                                Kosongkan
                            </button>
                        </div>

                        <!-- Pencarian Kolom Kiri -->
                        <div class="py-2.5 shrink-0">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                <input
                                    v-model="searchAssigned"
                                    type="text"
                                    placeholder="Cari dalam anggota kelompok..."
                                    class="w-full h-8.5 pl-8.5 pr-3 rounded-xl border border-orange-200 bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Drop Zone & List Siswa Kolom Kiri -->
                        <div class="flex-1 overflow-y-auto space-y-2 pr-1 min-h-[220px]">
                            <!-- State Kosong -->
                            <div
                                v-if="filteredAssignedStudents.length === 0"
                                class="h-full min-h-[180px] rounded-xl border-2 border-dashed border-orange-300/80 bg-white/70 flex flex-col items-center justify-center p-5 text-center transition-colors"
                                :class="isDragOverAssigned ? 'bg-orange-100/70 border-orange-500 scale-[0.99]' : ''"
                            >
                                <div class="h-11 w-11 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center mb-2">
                                    <Layers class="h-5 w-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-800">Belum ada anggota kelompok</p>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-xs">
                                    Tarik siswa dari kolom kanan dan lepas di sini, atau klik tombol panah pada siswa di sebelah kanan.
                                </p>
                            </div>

                            <!-- List Item Anggota -->
                            <div
                                v-for="student in filteredAssignedStudents"
                                :key="student.id"
                                draggable="true"
                                @dragstart="onDragStart(student, 'assigned', $event)"
                                @dragend="onDragEnd"
                                class="group bg-white rounded-xl p-2.5 border border-orange-200/90 shadow-xs flex items-center justify-between gap-2.5 hover:border-orange-400 hover:shadow-md transition-all cursor-grab active:cursor-grabbing select-none"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="text-slate-300 group-hover:text-orange-500 transition-colors shrink-0">
                                        <GripVertical class="h-4 w-4" />
                                    </div>
                                    <div class="h-8 w-8 rounded-lg bg-orange-100 text-orange-700 font-black text-xs flex items-center justify-center shrink-0">
                                        {{ student.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 truncate group-hover:text-orange-600 transition-colors">
                                            {{ student.name }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 truncate">
                                            {{ student.student_phone ? student.student_phone : 'Tanpa HP' }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="unassignStudent(student)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0 flex items-center gap-1 text-[10px] font-bold"
                                    title="Keluarkan dari kelompok"
                                >
                                    <span class="hidden sm:inline">Lepas</span>
                                    <ArrowRight class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- KOLOM KANAN: PESERTA DIDIK BELUM PUNYA KELOMPOK -->
                    <!-- ============================================== -->
                    <div
                        class="border-2 rounded-2xl p-3.5 sm:p-4 flex flex-col h-full min-h-[420px] transition-all duration-200 bg-slate-50/70"
                        :class="isDragOverUnassigned ? 'border-slate-500 bg-slate-100 ring-4 ring-slate-400/20 shadow-md' : 'border-slate-200'"
                        @dragover.prevent="isDragOverUnassigned = true"
                        @dragleave="isDragOverUnassigned = false"
                        @drop.prevent="onDropToUnassigned"
                    >
                        <!-- Header Kolom Kanan -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 rounded-xl bg-slate-700 text-white flex items-center justify-center shadow-xs">
                                    <Users class="h-4 w-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                        Belum Ada Kelompok
                                    </h4>
                                    <p class="text-[11px] text-slate-500">
                                        {{ unassignedStudents.length }} siswa belum terdaftar di kelompok manapun
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="filteredUnassignedStudents.length > 0"
                                type="button"
                                @click="assignAllVisible"
                                class="text-[10px] font-bold text-orange-600 hover:text-orange-700 hover:bg-orange-50 px-2.5 py-1 rounded-lg border border-orange-200/80 transition-colors"
                                title="Masukkan seluruh siswa yang tampil ke kelompok"
                            >
                                + Masukkan Semua
                            </button>
                        </div>

                        <!-- Pencarian Kolom Kanan -->
                        <div class="py-2.5 shrink-0">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                <input
                                    v-model="searchUnassigned"
                                    type="text"
                                    placeholder="Cari siswa belum berkelompok..."
                                    class="w-full h-8.5 pl-8.5 pr-3 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Drop Zone & List Siswa Kolom Kanan -->
                        <div class="flex-1 overflow-y-auto space-y-2 pr-1 min-h-[220px]">
                            <!-- State Kosong -->
                            <div
                                v-if="filteredUnassignedStudents.length === 0"
                                class="h-full min-h-[180px] rounded-xl border-2 border-dashed border-slate-300 bg-white/70 flex flex-col items-center justify-center p-5 text-center"
                            >
                                <div class="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                                    <CheckCircle2 class="h-5 w-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-800">Tidak ada siswa bebas tersisa</p>
                                <p class="text-[11px] text-slate-400 mt-1 max-w-xs">
                                    Seluruh siswa aktif pada tahun ajaran ini sudah terpetakan ke dalam kelompok belajar.
                                </p>
                            </div>

                            <!-- List Item Siswa Bebas -->
                            <div
                                v-for="student in filteredUnassignedStudents"
                                :key="student.id"
                                draggable="true"
                                @dragstart="onDragStart(student, 'unassigned', $event)"
                                @dragend="onDragEnd"
                                class="group bg-white rounded-xl p-2.5 border border-slate-200 shadow-xs flex items-center justify-between gap-2.5 hover:border-orange-400 hover:shadow-md transition-all cursor-grab active:cursor-grabbing select-none"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="text-slate-300 group-hover:text-orange-500 transition-colors shrink-0">
                                        <GripVertical class="h-4 w-4" />
                                    </div>
                                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-700 font-black text-xs flex items-center justify-center shrink-0">
                                        {{ student.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 truncate group-hover:text-orange-600 transition-colors">
                                            {{ student.name }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 truncate">
                                            {{ student.student_phone ? student.student_phone : 'Tanpa HP' }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="assignStudent(student)"
                                    class="px-2.5 py-1 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-bold text-[10px] transition-colors shrink-0 flex items-center gap-1 shadow-xs"
                                    title="Tambahkan ke kelompok ini"
                                >
                                    <ArrowLeft class="h-3.5 w-3.5" />
                                    <span>Masukkan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500">Total Anggota:</span>
                        <span class="font-black text-slate-900">{{ assignedStudents.length }} Siswa</span>
                        <span
                            v-if="newlyAddedCount > 0"
                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700"
                        >
                            +{{ newlyAddedCount }} Ditambahkan
                        </span>
                        <span
                            v-if="removedCount > 0"
                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700"
                        >
                            -{{ removedCount }} Dikeluarkan
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="isMappingModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="saveMapping"
                            :disabled="isMappingSubmitting"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isMappingSubmitting" class="h-3.5 w-3.5 animate-spin" />
                            <span>Simpan Pemetaan</span>
                        </button>
                    </div>
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
                            <h3 class="text-sm font-black text-slate-900">Salin Kelompok & Anggota</h3>
                            <p class="text-[11px] text-slate-500">Salin struktur kelompok dari tahun sebelumnya</p>
                        </div>
                    </div>
                    <button type="button" @click="isCopyModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="executeCopyGroups" class="space-y-3 pt-2">
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
                            <span>Mulai Salin Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
