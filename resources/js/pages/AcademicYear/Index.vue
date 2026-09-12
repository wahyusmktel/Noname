<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Calendar,
    Plus,
    Edit2,
    Trash2,
    X,
    Maximize2,
    Minimize2,
    Minus,
    CheckCircle2,
    Sparkles,
    Check,
    Loader2,
    Move,
    Layers,
    Users,
    CalendarCheck,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface AcademicYearItem {
    id: string;
    name: string;
    is_active: boolean;
    start_date: string | null;
    end_date: string | null;
    students_count?: number;
    study_groups_count?: number;
    created_at: string;
}

interface Props {
    academicYears: AcademicYearItem[];
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

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
// FORM STATE
// ==========================================
const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    is_active: false,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.start_date = '';
    form.end_date = '';
    form.is_active = props.academicYears.length === 0; // Jika pertama kali, otomatis aktif
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

const openEditModal = (year: AcademicYearItem) => {
    isEditing.value = true;
    editingId.value = year.id;
    form.reset();
    form.clearErrors();
    form.name = year.name;
    form.start_date = year.start_date ? year.start_date.slice(0, 10) : '';
    form.end_date = year.end_date ? year.end_date.slice(0, 10) : '';
    form.is_active = Boolean(year.is_active);
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const saveAcademicYear = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/academic-years/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Data tahun pelajaran berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal memperbarui tahun pelajaran.', 'error');
            },
        });
    } else {
        form.post('/academic-years', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Tahun pelajaran baru berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menambahkan tahun pelajaran.', 'error');
            },
        });
    }
};

const setActiveYear = async (year: AcademicYearItem) => {
    const confirmed = await confirmAction({
        title: 'Jadikan Tahun Aktif?',
        text: `Aktifkan Tahun Pelajaran "${year.name}" sebagai acuan utama sistem presensi dan pendataan?`,
        confirmButtonText: 'Ya, Aktifkan',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.post(`/academic-years/${year.id}/set-active`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Tahun Pelajaran "${year.name}" kini aktif.`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal mengubah tahun aktif.', 'error');
            },
        });
    }
};

const deleteAcademicYear = async (year: AcademicYearItem) => {
    if (year.is_active) {
        toast('Tahun pelajaran yang sedang aktif tidak dapat dihapus.', 'warning');
        return;
    }

    const confirmed = await confirmAction({
        title: 'Hapus Tahun Pelajaran?',
        text: `Apakah Anda yakin ingin mengarsipkan tahun pelajaran "${year.name}"? Seluruh data terkait akan tersimpan aman (soft delete).`,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        router.delete(`/academic-years/${year.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Tahun pelajaran berhasil dihapus.', 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal menghapus tahun pelajaran.', 'error');
            },
        });
    }
};

const formatDate = (dateStr: string | null) => {
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
    <Head title="Manajemen Tahun Pelajaran" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- TOP BAR -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-orange-600 mb-1">
                        <CalendarCheck class="h-4 w-4" />
                        <span>Pengaturan Periode Akademik</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                        Tahun Pelajaran
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola periode tahun pelajaran aktif utama untuk pendataan siswa dan kelompok belajar.
                    </p>
                </div>

                <div>
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Tahun Pelajaran</span>
                    </button>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Tahun Pelajaran</th>
                                <th class="py-3.5 px-4 text-center">Rentang Tanggal</th>
                                <th class="py-3.5 px-4 text-center">Kelompok Bimbel</th>
                                <th class="py-3.5 px-4 text-center">Peserta Didik</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(year, index) in academicYears"
                                :key="year.id"
                                class="hover:bg-orange-50/20 transition-colors group"
                                :class="year.is_active ? 'bg-orange-50/15' : ''"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                    {{ index + 1 }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-9 w-9 rounded-xl flex items-center justify-center font-bold shrink-0"
                                            :class="year.is_active ? 'bg-orange-500 text-white shadow-sm shadow-orange-500/30' : 'bg-slate-100 text-slate-500'"
                                        >
                                            <Calendar class="h-4.5 w-4.5" />
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-900 text-sm block">
                                                {{ year.name }}
                                            </span>
                                            <span class="text-[11px] text-slate-400">ID: {{ year.id.slice(0, 8) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center text-slate-600 font-medium">
                                    <span v-if="year.start_date || year.end_date">
                                        {{ formatDate(year.start_date) }} - {{ formatDate(year.end_date) }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">Fleksibel</span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-[11px] font-bold">
                                        <Layers class="h-3 w-3" />
                                        {{ year.study_groups_count || 0 }} Kelompok
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold">
                                        <Users class="h-3 w-3" />
                                        {{ year.students_count || 0 }} Siswa
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="year.is_active"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-orange-500 text-white shadow-sm shadow-orange-500/25"
                                    >
                                        <Sparkles class="h-3 w-3 animate-pulse" />
                                        Tahun Aktif
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="setActiveYear(year)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 hover:bg-orange-100 text-slate-600 hover:text-orange-700 transition-colors cursor-pointer"
                                        title="Jadikan tahun ini sebagai acuan aktif"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Jadikan Aktif
                                    </button>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(year)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors cursor-pointer"
                                            title="Edit Tahun Pelajaran"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            v-if="!year.is_active"
                                            @click="deleteAcademicYear(year)"
                                            type="button"
                                            class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Tahun Pelajaran"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="academicYears.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Belum ada tahun pelajaran yang tersimpan. Klik "Tambah Tahun Pelajaran" untuk memulai.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FLOATING DOCK (MINIMIZED) -->
        <div
            v-if="isModalOpen && isMinimized"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl border border-slate-700/50 animate-bounce cursor-pointer hover:bg-slate-800 transition-all"
            @click="isMinimized = false"
        >
            <Calendar class="h-4 w-4 text-orange-400" />
            <span class="text-xs font-bold">{{ isEditing ? 'Edit Tahun' : 'Tambah Tahun' }} (Diminimalkan)</span>
            <Maximize2 class="h-3.5 w-3.5 ml-2 text-slate-400" />
        </div>

        <!-- MODAL (DRAGGABLE, MAXIMIZE, MINIMIZE, LOCKED BACKDROP) -->
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
                <!-- HEADER -->
                <div
                    @mousedown="startDrag"
                    class="px-5 py-4 bg-gradient-to-r from-orange-50 via-amber-50/40 to-white border-b border-orange-100/60 flex items-center justify-between select-none cursor-move shrink-0"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
                            <Calendar class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-black text-slate-900">
                                    {{ isEditing ? 'Perbarui Tahun Pelajaran' : 'Tambah Tahun Pelajaran' }}
                                </h3>
                                <Move class="h-3 w-3 text-slate-400 opacity-60" />
                            </div>
                            <p class="text-[10px] text-slate-500 font-medium">Geser header ini untuk memindahkan modal</p>
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
                    <form @submit.prevent="saveAcademicYear" id="academicYearForm" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama Tahun Pelajaran <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: 2024/2025"
                                class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Tanggal Mulai (Opsional)</label>
                                <input
                                    v-model="form.start_date"
                                    type="date"
                                    class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Tanggal Selesai (Opsional)</label>
                                <input
                                    v-model="form.end_date"
                                    type="date"
                                    class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                />
                            </div>
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
                                    <p class="text-xs font-bold text-slate-900">Jadikan Tahun Pelajaran Aktif</p>
                                    <p class="text-[11px] text-slate-500">Menjadikan periode ini sebagai acuan utama sistem presensi.</p>
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

                <!-- FOOTER -->
                <div v-show="!isMinimized" class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <p class="text-[11px] text-slate-400"><span class="text-orange-500 font-bold">*</span> Wajib diisi</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200">
                            Batal
                        </button>
                        <button
                            type="submit"
                            form="academicYearForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-3.5 w-3.5 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Tahun Pelajaran' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
