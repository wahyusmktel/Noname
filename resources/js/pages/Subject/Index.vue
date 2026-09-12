<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    BookOpen,
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
    Layers,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface SubjectItem {
    id: string;
    name: string;
    is_active: boolean;
    created_at: string;
}

interface Props {
    subjects: {
        data: SubjectItem[];
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
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
    filters: {
        search?: string;
        status?: string;
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
let searchTimer: any = null;

const applySearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/subjects', {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
};

watch(searchQuery, applySearch);
watch(statusFilter, () => {
    router.get('/subjects', {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
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

// Window State: Normal, Maximized, Minimized
const isMaximized = ref(false);
const isMinimized = ref(false);

// Drag & Drop Coordinates for Header Movement
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
    if (isMaximized.value) {
        isMinimized.value = false;
    }
};

const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
};

// ==========================================
// FORM STATE & COOL CHECKBOX/SWITCH
// ==========================================
const form = useForm({
    name: '',
    is_active: true,
});

// Open Modal for Create
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.is_active = true;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

// Open Modal for Edit
const openEditModal = (subject: SubjectItem) => {
    isEditing.value = true;
    editingId.value = subject.id;
    form.reset();
    form.clearErrors();
    form.name = subject.name;
    form.is_active = Boolean(subject.is_active);
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

// Close Modal
const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

// Save Form Handler
const saveSubject = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/subjects/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Mata pelajaran berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Terjadi kesalahan validasi data.', 'error');
            },
        });
    } else {
        form.post('/subjects', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Mata pelajaran baru berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menambahkan mata pelajaran.', 'error');
            },
        });
    }
};

// Delete Subject Handler with SweetAlert2 (Mandatory Rule #3 & #4)
const deleteSubject = async (subject: SubjectItem) => {
    const confirmed = await confirmAction({
        title: 'Hapus Mata Pelajaran?',
        text: `Apakah Anda yakin ingin menghapus "${subject.name}"? Data yang dihapus akan diarsipkan (soft delete).`,
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/subjects/${subject.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Mata pelajaran "${subject.name}" berhasil dihapus.`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menghapus data mata pelajaran.', 'error');
            },
        });
    }
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Manajemen Mata Pelajaran" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- ===================================================== -->
            <!-- TOP BAR: TITLE & ACTIONS -->
            <!-- ===================================================== -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-orange-600 mb-1">
                        <Layers class="h-4 w-4" />
                        <span>Data Kurikulum Bimbel</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        Mata Pelajaran
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola daftar mata pelajaran, bidang studi, dan kurikulum aktif lembaga bimbingan belajar.
                    </p>
                </div>

                <div>
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Mata Pelajaran</span>
                    </button>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- STATS SUMMARY CARDS -->
            <!-- ===================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <BookOpen class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Mapel</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.total }} <span class="text-xs font-medium text-slate-500">Mata Pelajaran</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Mapel Aktif</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.active }} <span class="text-xs font-medium text-slate-500">Bisa Digunakan</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                    <div class="h-11 w-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                        <XCircle class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Mapel Nonaktif</p>
                        <p class="text-xl font-black text-slate-900">{{ stats.inactive }} <span class="text-xs font-medium text-slate-500">Diarsipkan</span></p>
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- FILTER & SEARCH BAR (Rule #8) -->
            <!-- ===================================================== -->
            <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama mata pelajaran..."
                            class="w-full h-10.5 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                        />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select
                            v-model="statusFilter"
                            class="h-10.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-700 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all w-full sm:w-44"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- DATA TABLE -->
            <!-- ===================================================== -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Mata Pelajaran</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">Dibuat Pada</th>
                                <th class="py-3.5 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr
                                v-for="(subject, index) in subjects.data"
                                :key="subject.id"
                                class="hover:bg-orange-50/30 transition-colors group"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ (subjects.current_page - 1) * subjects.per_page + index + 1 }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-orange-100/70 text-orange-600 flex items-center justify-center shrink-0 font-bold">
                                            <BookOpen class="h-4.5 w-4.5" />
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-orange-600 transition-colors">
                                                {{ subject.name }}
                                            </p>
                                            <p class="text-[11px] text-slate-400">
                                                ID: {{ subject.id.substring(0, 8) }}...
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="subject.is_active"
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

                                <td class="py-3.5 px-4 text-center text-slate-500 font-medium">
                                    {{ formatDate(subject.created_at) }}
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(subject)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors cursor-pointer"
                                            title="Edit Mata Pelajaran"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            @click="deleteSubject(subject)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Mata Pelajaran"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="subjects.data.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="h-14 w-14 rounded-2xl bg-orange-50 text-orange-400 flex items-center justify-center mb-3">
                                            <BookOpen class="h-7 w-7" />
                                        </div>
                                        <p class="font-bold text-slate-700 text-sm">Tidak ada mata pelajaran ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            Belum ada data mata pelajaran yang tersimpan atau tidak cocok dengan filter pencarian.
                                        </p>
                                        <button
                                            @click="openCreateModal"
                                            type="button"
                                            class="mt-4 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-orange-600 bg-orange-50 hover:bg-orange-100 transition-colors cursor-pointer"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                            <span>Tambah Mata Pelajaran Baru</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="subjects.last_page > 1" class="px-4 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <p class="text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-700">{{ subjects.from || 0 }}</span> - <span class="font-semibold text-slate-700">{{ subjects.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ subjects.total }}</span> mata pelajaran
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, i) in subjects.links"
                            :key="i"
                            :disabled="!link.url"
                            @click="router.visit(link.url!)"
                            class="min-h-8 min-w-8 px-2 rounded-lg font-semibold flex items-center justify-center transition-all cursor-pointer"
                            :class="[
                                link.active ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        >
                            <span v-if="link.label.includes('Previous')">
                                <ChevronLeft class="h-3.5 w-3.5" />
                            </span>
                            <span v-else-if="link.label.includes('Next')">
                                <ChevronRight class="h-3.5 w-3.5" />
                            </span>
                            <span v-else v-html="link.label"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- FLOATING DOCK ICON (WHEN MODAL IS MINIMIZED) -->
        <!-- ===================================================================== -->
        <div
            v-if="isModalOpen && isMinimized"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl border border-slate-700/50 animate-bounce cursor-pointer hover:bg-slate-800 transition-all"
            @click="isMinimized = false"
        >
            <BookOpen class="h-4 w-4 text-orange-400" />
            <span class="text-xs font-bold">{{ isEditing ? 'Edit Mapel' : 'Tambah Mapel' }} (Diminimalkan)</span>
            <Maximize2 class="h-3.5 w-3.5 ml-2 text-slate-400" />
        </div>

        <!-- ===================================================================== -->
        <!-- INTERACTIVE MODAL (DRAGGABLE, MAXIMIZE, MINIMIZE, LOCKED BACKDROP) -->
        <!-- ===================================================================== -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden transition-all duration-200"
            :class="isMinimized ? 'pointer-events-none opacity-0' : 'bg-slate-950/60 backdrop-blur-xs'"
            @click.self="() => {}"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 flex flex-col transition-all duration-150 overflow-hidden"
                :class="[
                    isMaximized ? 'w-full h-full rounded-none' : 'w-full max-w-lg mx-4 max-h-[92vh]',
                ]"
                :style="!isMaximized && hasCustomPos ? { transform: `translate3d(${modalPos.x}px, ${modalPos.y}px, 0px)` } : {}"
            >
                <!-- ===================================================== -->
                <!-- DRAGGABLE MODAL HEADER -->
                <!-- ===================================================== -->
                <div
                    @mousedown="startDrag"
                    class="px-5 py-4 bg-gradient-to-r from-orange-50 via-amber-50/40 to-white border-b border-orange-100/60 flex items-center justify-between select-none cursor-move shrink-0"
                    title="Klik dan tahan untuk menggeser modal"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
                            <BookOpen class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-black text-slate-900">
                                    {{ isEditing ? 'Perbarui Mata Pelajaran' : 'Tambah Mata Pelajaran Baru' }}
                                </h3>
                                <Move class="h-3 w-3 text-slate-400 opacity-60" />
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium">
                                Geser header ini untuk memindahkan letak modal
                            </p>
                        </div>
                    </div>

                    <!-- Modal Controls (Minimize, Maximize, Close) -->
                    <div class="flex items-center gap-1" @mousedown.stop>
                        <button
                            type="button"
                            @click="toggleMinimize"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                            title="Minimalkan"
                        >
                            <Minus class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="toggleMaximize"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                            :title="isMaximized ? 'Pulihkan ukuran' : 'Perbesar penuh'"
                        >
                            <Minimize2 v-if="isMaximized" class="h-3.5 w-3.5" />
                            <Maximize2 v-else class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="closeModal"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                            title="Tutup Modal"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- ===================================================== -->
                <!-- MODAL BODY (HIDDEN WHEN MINIMIZED) -->
                <!-- ===================================================== -->
                <div v-show="!isMinimized" class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">
                    <form @submit.prevent="saveSubject" id="subjectForm" class="space-y-4">
                        <!-- 1. Nama Mata Pelajaran -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama Mata Pelajaran <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Matematika Wajib, TPS Penalaran Umum"
                                class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold mt-1">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- 2. Status Aktif / Nonaktif (COOL STYLED CHECKBOX / SWITCH) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Status Keaktifan</label>
                            
                            <!-- Cool Interactive Card Checkbox/Switch -->
                            <div
                                @click="form.is_active = !form.is_active"
                                class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex items-center justify-between"
                                :class="[
                                    form.is_active
                                        ? 'bg-gradient-to-r from-orange-50/90 via-amber-50/50 to-white border-orange-200 shadow-sm'
                                        : 'bg-slate-50/70 border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-9 rounded-xl flex items-center justify-center transition-all duration-200 shrink-0"
                                        :class="[
                                            form.is_active
                                                ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25'
                                                : 'bg-slate-200 text-slate-400'
                                        ]"
                                    >
                                        <Sparkles v-if="form.is_active" class="h-4.5 w-4.5 animate-pulse" />
                                        <XCircle v-else class="h-4.5 w-4.5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-slate-900">
                                                {{ form.is_active ? 'Mata Pelajaran Aktif' : 'Mata Pelajaran Nonaktif' }}
                                            </span>
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                                                :class="form.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                            >
                                                {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            {{ form.is_active 
                                                ? 'Mata pelajaran ini dapat dipilih saat membuat sesi dan data tentor.' 
                                                : 'Mata pelajaran ini disembunyikan dari pilihan kelas & tentor.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Cool Neon Switch Slider -->
                                <div
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="form.is_active ? 'bg-orange-500' : 'bg-slate-300'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center"
                                        :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
                                    >
                                        <Check v-if="form.is_active" class="h-3 w-3 text-orange-600 font-bold" />
                                        <X v-else class="h-3 w-3 text-slate-400" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ===================================================== -->
                <!-- MODAL FOOTER -->
                <!-- ===================================================== -->
                <div v-show="!isMinimized" class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <p class="text-[11px] text-slate-400">
                        <span class="text-orange-500 font-bold">*</span> Wajib diisi
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200/70 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="subjectForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Mata Pelajaran' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
