<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Users,
    UserCheck,
    Plus,
    Search,
    Edit2,
    Trash2,
    X,
    Maximize2,
    Minimize2,
    Minus,
    UploadCloud,
    Phone,
    Mail,
    GraduationCap,
    BookOpen,
    Sparkles,
    Loader2,
    ChevronLeft,
    ChevronRight,
    Move,
    CheckCircle2,
    AlertCircle,
    User,
    Check,
    ChevronDown,
    KeyRound,
    Download,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface TentorItem {
    id: string;
    title_prefix?: string | null;
    name: string;
    title_suffix?: string | null;
    full_name: string;
    phone?: string | null;
    email?: string | null;
    photo?: string | null;
    photo_url?: string | null;
    specialization?: string | null;
    status: 'active' | 'inactive';
    username?: string | null;
    plain_password?: string | null;
    created_at: string;
}

interface Props {
    tentors: {
        data: TentorItem[];
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
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
    titlePrefixes: string[];
    titleSuffixes: string[];
    subjects?: Array<{
        id: string;
        name: string;
    }>;
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
        router.get('/tentors', {
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
    router.get('/tentors', {
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
// FORM STATE & PHOTO DRAG-AND-DROP
// ==========================================
const form = useForm({
    name: '',
    title_prefix: '',
    title_suffix: '',
    phone: '',
    email: '',
    specialization: '',
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

// ==========================================
// SEARCHABLE GELAR (TITLE) & SUBJECT DROPDOWNS
// ==========================================
const isPrefixDropdownOpen = ref(false);
const searchPrefix = ref('');
const prefixDropdownRef = ref<HTMLElement | null>(null);

const isSuffixDropdownOpen = ref(false);
const searchSuffix = ref('');
const suffixDropdownRef = ref<HTMLElement | null>(null);

const isSubjectDropdownOpen = ref(false);
const searchSubject = ref('');
const subjectDropdownRef = ref<HTMLElement | null>(null);

const filteredPrefixes = computed(() => {
    const q = searchPrefix.value.trim().toLowerCase();
    if (!q) return props.titlePrefixes;
    return props.titlePrefixes.filter((item) => item.toLowerCase().includes(q));
});

const filteredSuffixes = computed(() => {
    const q = searchSuffix.value.trim().toLowerCase();
    if (!q) return props.titleSuffixes;
    return props.titleSuffixes.filter((item) => item.toLowerCase().includes(q));
});

const filteredSubjects = computed(() => {
    const list = props.subjects || [];
    const q = searchSubject.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((item) => item.name.toLowerCase().includes(q));
});

const selectPrefix = (prefix: string) => {
    form.title_prefix = prefix;
    isPrefixDropdownOpen.value = false;
    searchPrefix.value = '';
};

const selectSuffix = (suffix: string) => {
    form.title_suffix = suffix;
    isSuffixDropdownOpen.value = false;
    searchSuffix.value = '';
};

const selectSubject = (subjectName: string) => {
    form.specialization = subjectName;
    isSubjectDropdownOpen.value = false;
    searchSubject.value = '';
};

const togglePrefixDropdown = () => {
    isPrefixDropdownOpen.value = !isPrefixDropdownOpen.value;
    if (isPrefixDropdownOpen.value) {
        isSuffixDropdownOpen.value = false;
        isSubjectDropdownOpen.value = false;
        searchPrefix.value = '';
    }
};

const toggleSuffixDropdown = () => {
    isSuffixDropdownOpen.value = !isSuffixDropdownOpen.value;
    if (isSuffixDropdownOpen.value) {
        isPrefixDropdownOpen.value = false;
        isSubjectDropdownOpen.value = false;
        searchSuffix.value = '';
    }
};

const toggleSubjectDropdown = () => {
    isSubjectDropdownOpen.value = !isSubjectDropdownOpen.value;
    if (isSubjectDropdownOpen.value) {
        isPrefixDropdownOpen.value = false;
        isSuffixDropdownOpen.value = false;
        searchSubject.value = '';
    }
};

const resetDropdowns = () => {
    isPrefixDropdownOpen.value = false;
    isSuffixDropdownOpen.value = false;
    isSubjectDropdownOpen.value = false;
    searchPrefix.value = '';
    searchSuffix.value = '';
    searchSubject.value = '';
};

const handleClickOutsideDropdowns = (e: MouseEvent) => {
    const target = e.target as Node;
    if (prefixDropdownRef.value && !prefixDropdownRef.value.contains(target)) {
        isPrefixDropdownOpen.value = false;
    }
    if (suffixDropdownRef.value && !suffixDropdownRef.value.contains(target)) {
        isSuffixDropdownOpen.value = false;
    }
    if (subjectDropdownRef.value && !subjectDropdownRef.value.contains(target)) {
        isSubjectDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideDropdowns);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsideDropdowns);
});

// Open Modal for Create
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    photoPreviewUrl.value = null;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    resetDropdowns();
    isModalOpen.value = true;
};

// Open Modal for Edit
const openEditModal = (tentor: TentorItem) => {
    isEditing.value = true;
    editingId.value = tentor.id;
    form.reset();
    form.clearErrors();
    form.name = tentor.name;
    form.title_prefix = tentor.title_prefix || '';
    form.title_suffix = tentor.title_suffix || '';
    form.phone = tentor.phone || '';
    form.email = tentor.email || '';
    form.specialization = tentor.specialization || '';
    form.status = tentor.status;
    form.photo = null;
    form._method = 'PUT';
    photoPreviewUrl.value = tentor.photo_url || null;
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    resetDropdowns();
    isModalOpen.value = true;
};

// Close Modal
const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    photoPreviewUrl.value = null;
    resetDropdowns();
};

// Save Form Handler
const saveTentor = () => {
    if (isEditing.value && editingId.value) {
        form.post(`/tentors/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Data tentor berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err) => {
                const firstErr = Object.values(err)[0];
                if (firstErr) toast(firstErr, 'error');
            },
        });
    } else {
        form.post('/tentors', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Tentor baru berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err) => {
                const firstErr = Object.values(err)[0];
                if (firstErr) toast(firstErr, 'error');
            },
        });
    }
};

// Delete Handler with SweetAlert2 (Mandatory Rule #3)
const deleteTentor = async (tentor: TentorItem) => {
    const confirmed = await confirmAction({
        title: 'Hapus Data Tentor?',
        text: `Apakah Anda yakin ingin menghapus data "${tentor.full_name}"? Data yang dihapus dapat dipulihkan melalui arsip soft delete.`,
        confirmText: 'Ya, Hapus Data',
        cancelText: 'Batal',
        icon: 'warning',
    });

    if (confirmed) {
        router.delete(`/tentors/${tentor.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Data tentor berhasil dihapus.', 'info');
            },
        });
    }
};

// ==========================================
// GENERATE & RESET AKUN TENTOR
// ==========================================
const isGeneratingAccounts = ref(false);

const hasAccounts = computed(() => {
    return props.tentors.data.some((t) => !!t.username);
});

const handleGenerateAccounts = async () => {
    const confirmed = await confirmAction({
        title: 'Generate Akun Tentor?',
        text: 'Sistem akan membuat Username 6 digit (berawalan 26) dan Password 6 digit angka untuk semua tentor yang belum memiliki akun.',
        confirmText: 'Ya, Buat Akun',
        icon: 'question',
    });

    if (confirmed) {
        isGeneratingAccounts.value = true;
        router.post('/tentors/generate-accounts', {}, {
            preserveScroll: true,
            onFinish: () => {
                isGeneratingAccounts.value = false;
            },
            onSuccess: () => {
                toast('Proses pembuatan akun tentor berhasil dijalankan!', 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal membuat akun tentor.', 'error');
            },
        });
    }
};

const handleResetPassword = async (tentor: TentorItem) => {
    const confirmed = await confirmAction({
        title: `Reset Password ${tentor.full_name}?`,
        text: 'Sistem akan menghasilkan 6 digit kata sandi acak baru untuk akun tentor ini.',
        confirmText: 'Ya, Reset Password',
        icon: 'warning',
    });

    if (confirmed) {
        router.post(`/tentors/${tentor.id}/reset-password`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                toast(`Password untuk tentor ${tentor.full_name} berhasil direset!`, 'success');
            },
            onError: (err: any) => {
                toast(err.error || 'Gagal mereset password tentor.', 'error');
            },
        });
    }
};

const downloadAccountsExcel = () => {
    window.location.href = '/tentors/export-accounts';
};
</script>

<template>
    <AuthenticatedLayout title="Manajemen Tentor">
        <Head title="Daftar Tentor - Bimbel No Name" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- PAGE TITLE & TOP ACTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 font-medium mb-1">
                        <span>Lembaga Bimbel</span>
                        <span>&bull;</span>
                        <span class="text-orange-600 font-semibold">Tentor & Pengajar</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daftar Tentor (Guru Bimbel)</h1>
                        <span class="px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">
                            {{ tentors.total }} Pengajar
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola data tenaga pendidik, spesialisasi mata pelajaran, kontak, dan foto profil tentor.</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Unduh Rekap Akun Tentor -->
                    <button
                        v-if="hasAccounts"
                        @click="downloadAccountsExcel"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-amber-800 bg-amber-50 border border-amber-300 hover:bg-amber-100 transition-all cursor-pointer shadow-xs"
                        title="Unduh rekap data akun tentor format Excel"
                    >
                        <Download class="h-4 w-4 text-amber-600" />
                        <span>Unduh Rekap Akun</span>
                    </button>

                    <!-- Generate Akun Tentor -->
                    <button
                        @click="handleGenerateAccounts"
                        :disabled="isGeneratingAccounts"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-orange-700 bg-orange-50 border border-orange-200 hover:bg-orange-100 transition-all cursor-pointer disabled:opacity-50"
                        title="Generate username 6 digit berawalan 26 dan password 6 digit untuk tentor yang belum punya akun"
                    >
                        <Loader2 v-if="isGeneratingAccounts" class="h-4 w-4 animate-spin text-orange-600" />
                        <KeyRound v-else class="h-4 w-4 text-orange-600" />
                        <span>Generate Akun Tentor</span>
                    </button>

                    <!-- Action Button "+ Tambah Tentor" -->
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>+ Tambah Tentor Baru</span>
                    </button>
                </div>
            </div>

            <!-- SEARCH, FILTER & TABLE CONTAINER -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
                <!-- Filter Bar -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/40">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama tentor, email, nomor HP, mata pelajaran..."
                            class="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder:text-slate-400 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none transition-all font-medium"
                        />
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-500 font-semibold">Status:</span>
                            <select
                                v-model="statusFilter"
                                class="h-10 px-3 pr-8 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                            >
                                <option value="">Semua Status</option>
                                <option value="active">Aktif Mengajar</option>
                                <option value="inactive">Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TABLE SECTION -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3.5">Tentor / Guru</th>
                                <th class="px-5 py-3.5">Mata Pelajaran / Spesialisasi</th>
                                <th class="px-5 py-3.5">Kontak WhatsApp & Email</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="tentor in tentors.data"
                                :key="tentor.id"
                                class="hover:bg-orange-50/20 transition-colors group"
                            >
                                <!-- Tentor Avatar & Full Name -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Photo or Fallback Initials -->
                                        <div class="relative h-10 w-10 rounded-full shrink-0 overflow-hidden bg-gradient-to-tr from-orange-500 to-amber-400 text-white flex items-center justify-center font-bold text-xs ring-2 ring-orange-500/20">
                                            <img
                                                v-if="tentor.photo_url"
                                                :src="tentor.photo_url"
                                                :alt="tentor.full_name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span v-else>{{ tentor.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                                <span>{{ tentor.full_name }}</span>
                                                <span v-if="tentor.title_prefix || tentor.title_suffix" class="text-[10px] text-orange-600 font-semibold bg-orange-50 px-1.5 py-0.2 rounded-md">
                                                    Gelar
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span v-if="tentor.username" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-orange-100 text-orange-800 text-[10px] font-mono font-bold" title="Username Tentor">
                                                    <KeyRound class="h-2.5 w-2.5" />
                                                    <span>{{ tentor.username }}</span>
                                                </span>
                                                <span v-else class="text-[10px] text-slate-400 italic">
                                                    Belum ada akun
                                                </span>
                                                <span v-if="tentor.plain_password" class="text-[10px] text-slate-400 font-mono" title="Password Akun">
                                                    (Pass: {{ tentor.plain_password }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Specialization -->
                                <td class="px-5 py-4">
                                    <div v-if="tentor.specialization" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-[11px] font-semibold">
                                        <BookOpen class="h-3 w-3" />
                                        <span>{{ tentor.specialization }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic text-[11px]">Belum diatur</span>
                                </td>

                                <!-- Contact Info -->
                                <td class="px-5 py-4 space-y-1">
                                    <div v-if="tentor.phone" class="flex items-center gap-1.5 text-[11px] text-slate-700 font-medium">
                                        <Phone class="h-3.5 w-3.5 text-emerald-500" />
                                        <a :href="'https://wa.me/' + tentor.phone.replace(/[^0-9]/g, '')" target="_blank" class="hover:text-emerald-600 hover:underline">
                                            {{ tentor.phone }}
                                        </a>
                                    </div>
                                    <div v-if="tentor.email" class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <Mail class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ tentor.email }}</span>
                                    </div>
                                    <span v-if="!tentor.phone && !tentor.email" class="text-slate-400 italic text-[11px]">-</span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="tentor.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="tentor.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                        {{ tentor.status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Reset Password Tentor -->
                                        <button
                                            v-if="tentor.username"
                                            @click="handleResetPassword(tentor)"
                                            type="button"
                                            class="p-2 rounded-xl text-amber-600 hover:text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer"
                                            title="Reset Password Tentor (6 Digit Baru)"
                                        >
                                            <KeyRound class="h-4 w-4" />
                                        </button>

                                        <button
                                            @click="openEditModal(tentor)"
                                            type="button"
                                            class="p-2 rounded-xl text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors cursor-pointer"
                                            title="Edit Data Tentor"
                                        >
                                            <Edit2 class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="deleteTentor(tentor)"
                                            type="button"
                                            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Tentor"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="tentors.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400 space-y-2">
                                    <UserCheck class="h-10 w-10 mx-auto text-slate-300" />
                                    <p class="text-sm font-bold text-slate-700">Tidak ada data tentor yang ditemukan</p>
                                    <p class="text-xs text-slate-400">Silakan ubah kata kunci pencarian atau tambahkan tentor baru.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION (Server-side Rule #8) -->
                <div v-if="tentors.total > 0" class="p-4 sm:p-5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500 bg-slate-50/40">
                    <div>
                        Menampilkan <span class="font-bold text-slate-800">{{ tentors.from }}</span> sampai <span class="font-bold text-slate-800">{{ tentors.to }}</span> dari <span class="font-bold text-slate-800">{{ tentors.total }}</span> tentor
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, idx) in tentors.links"
                            :key="idx"
                            @click="link.url && router.visit(link.url)"
                            :disabled="!link.url || link.active"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            :class="{
                                'bg-orange-500 text-white shadow-xs': link.active,
                                'text-slate-600 hover:bg-white border border-slate-200': !link.active && link.url,
                                'text-slate-300 pointer-events-none': !link.url,
                            }"
                        ></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL INTERAKTIF: DRAGGABLE HEADER, MAXIMIZE, MINIMIZE, NO OUTSIDE CLOSE -->
        <!-- ========================================================================= -->
        <teleport to="body">
            <!-- Modal Backdrop (Click does NOT close the modal!) -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
                :class="isMinimized ? 'pointer-events-none' : ''"
            >
                <!-- Main Modal Card Container -->
                <div
                    class="bg-white shadow-2xl transition-all duration-200 border border-slate-200 flex flex-col pointer-events-auto"
                    :class="[
                        isMaximized ? 'fixed inset-0 w-full h-full rounded-none' : 'w-full max-w-2xl rounded-3xl max-h-[90vh]',
                        isMinimized ? 'fixed bottom-4 right-4 w-80 h-auto rounded-2xl shadow-xl' : '',
                    ]"
                    :style="(!isMaximized && !isMinimized && hasCustomPos) ? { transform: `translate(${modalPos.x}px, ${modalPos.y}px)` } : {}"
                >
                    <!-- ===================================================== -->
                    <!-- MODAL HEADER (DRAGGABLE HANDLE) -->
                    <!-- ===================================================== -->
                    <div
                        @mousedown="startDrag"
                        class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between select-none cursor-grab active:cursor-grabbing bg-slate-50/80 rounded-t-3xl"
                        :class="isMaximized ? 'rounded-none' : ''"
                        title="Tahan dan geser untuk memindahkan posisi modal"
                    >
                        <!-- Title & Drag Indicator -->
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold">
                                <Move class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 leading-tight">
                                    {{ isEditing ? 'Edit Data Tentor' : 'Tambah Tentor Baru' }}
                                </h3>
                                <p class="text-[10px] text-slate-400">Header bisa digeser (Drag & Drop)</p>
                            </div>
                        </div>

                        <!-- Modal Window Controls: Minimize, Maximize, Close -->
                        <div class="flex items-center gap-1">
                            <!-- Minimize Button -->
                            <button
                                @click="toggleMinimize"
                                type="button"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors"
                                :title="isMinimized ? 'Pulihkan Modal' : 'Minimize Modal'"
                            >
                                <Minus class="h-4 w-4" />
                            </button>

                            <!-- Maximize / Restore Button -->
                            <button
                                v-if="!isMinimized"
                                @click="toggleMaximize"
                                type="button"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors"
                                :title="isMaximized ? 'Kembalikan Ukuran' : 'Maximize Layar Penuh'"
                            >
                                <Minimize2 v-if="isMaximized" class="h-4 w-4" />
                                <Maximize2 v-else class="h-4 w-4" />
                            </button>

                            <!-- Close Button (The ONLY way to close the modal) -->
                            <button
                                @click="closeModal"
                                type="button"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
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
                        <form @submit.prevent="saveTentor" id="tentorForm" class="space-y-4">
                            <!-- Gelar Depan, Nama Tentor, Gelar Belakang -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                                <!-- 1. Gelar Depan (Opsional) Searchable Select -->
                                <div class="sm:col-span-3 space-y-1.5 relative" ref="prefixDropdownRef">
                                    <label class="block text-xs font-bold text-slate-700">Gelar Depan</label>
                                    <div class="relative">
                                        <button
                                            type="button"
                                            @click="togglePrefixDropdown"
                                            class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-left flex items-center justify-between transition-all hover:border-slate-300 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                            :class="form.title_prefix ? 'text-slate-900 font-semibold' : 'text-slate-400'"
                                        >
                                            <span class="truncate">
                                                {{ form.title_prefix || '(Tanpa Gelar)' }}
                                            </span>
                                            <div class="flex items-center gap-1 shrink-0 ml-1">
                                                <span
                                                    v-if="form.title_prefix"
                                                    @click.stop="selectPrefix('')"
                                                    class="p-0.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 cursor-pointer"
                                                    title="Hapus gelar"
                                                >
                                                    <X class="h-3.5 w-3.5" />
                                                </span>
                                                <ChevronDown class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': isPrefixDropdownOpen }" />
                                            </div>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div
                                            v-if="isPrefixDropdownOpen"
                                            class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-xl shadow-xl border border-slate-200/90 py-2 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                        >
                                            <!-- Search Input -->
                                            <div class="px-2.5 pb-2 border-b border-slate-100">
                                                <div class="relative">
                                                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                                    <input
                                                        v-model="searchPrefix"
                                                        type="text"
                                                        placeholder="Cari gelar depan..."
                                                        class="w-full h-8 pl-8 pr-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 focus:outline-none"
                                                        @click.stop
                                                        autofocus
                                                    />
                                                </div>
                                            </div>

                                            <!-- List Items -->
                                            <div class="max-h-48 overflow-y-auto py-1 text-xs">
                                                <button
                                                    type="button"
                                                    @click="selectPrefix('')"
                                                    class="w-full px-3 py-1.5 text-left text-slate-600 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': !form.title_prefix }"
                                                >
                                                    <span>(Tanpa Gelar)</span>
                                                    <Check v-if="!form.title_prefix" class="h-3.5 w-3.5 text-orange-500" />
                                                </button>

                                                <button
                                                    v-for="item in filteredPrefixes"
                                                    :key="item"
                                                    type="button"
                                                    @click="selectPrefix(item)"
                                                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': form.title_prefix === item }"
                                                >
                                                    <span>{{ item }}</span>
                                                    <Check v-if="form.title_prefix === item" class="h-3.5 w-3.5 text-orange-500" />
                                                </button>

                                                <!-- Custom Input Option if search query doesn't match predefined -->
                                                <button
                                                    v-if="searchPrefix.trim() && !filteredPrefixes.includes(searchPrefix.trim())"
                                                    type="button"
                                                    @click="selectPrefix(searchPrefix.trim())"
                                                    class="w-full px-3 py-2 text-left bg-orange-50/70 hover:bg-orange-100 text-orange-700 font-medium flex items-center gap-1.5 border-t border-orange-100 mt-1"
                                                >
                                                    <Plus class="h-3.5 w-3.5 text-orange-600" />
                                                    <span class="truncate">Gunakan "<strong>{{ searchPrefix.trim() }}</strong>"</span>
                                                </button>

                                                <div v-if="filteredPrefixes.length === 0 && !searchPrefix.trim()" class="px-3 py-3 text-center text-slate-400 text-[11px]">
                                                    Tidak ada pilihan gelar
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Nama Tentor (Wajib diisi) -->
                                <div class="sm:col-span-6 space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">
                                        Nama Lengkap Tentor <span class="text-orange-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Contoh: Aris Sudrajat"
                                        class="w-full h-10.5 px-3.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                    />
                                    <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold mt-1">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- 3. Gelar Belakang (Opsional) Searchable Select -->
                                <div class="sm:col-span-3 space-y-1.5 relative" ref="suffixDropdownRef">
                                    <label class="block text-xs font-bold text-slate-700">Gelar Belakang</label>
                                    <div class="relative">
                                        <button
                                            type="button"
                                            @click="toggleSuffixDropdown"
                                            class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-left flex items-center justify-between transition-all hover:border-slate-300 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                            :class="form.title_suffix ? 'text-slate-900 font-semibold' : 'text-slate-400'"
                                        >
                                            <span class="truncate">
                                                {{ form.title_suffix || '(Tanpa Gelar)' }}
                                            </span>
                                            <div class="flex items-center gap-1 shrink-0 ml-1">
                                                <span
                                                    v-if="form.title_suffix"
                                                    @click.stop="selectSuffix('')"
                                                    class="p-0.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 cursor-pointer"
                                                    title="Hapus gelar"
                                                >
                                                    <X class="h-3.5 w-3.5" />
                                                </span>
                                                <ChevronDown class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': isSuffixDropdownOpen }" />
                                            </div>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div
                                            v-if="isSuffixDropdownOpen"
                                            class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-xl shadow-xl border border-slate-200/90 py-2 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                        >
                                            <!-- Search Input -->
                                            <div class="px-2.5 pb-2 border-b border-slate-100">
                                                <div class="relative">
                                                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                                    <input
                                                        v-model="searchSuffix"
                                                        type="text"
                                                        placeholder="Cari gelar belakang (S.Pd., dll)..."
                                                        class="w-full h-8 pl-8 pr-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 focus:outline-none"
                                                        @click.stop
                                                        autofocus
                                                    />
                                                </div>
                                            </div>

                                            <!-- List Items -->
                                            <div class="max-h-48 overflow-y-auto py-1 text-xs">
                                                <button
                                                    type="button"
                                                    @click="selectSuffix('')"
                                                    class="w-full px-3 py-1.5 text-left text-slate-600 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': !form.title_suffix }"
                                                >
                                                    <span>(Tanpa Gelar)</span>
                                                    <Check v-if="!form.title_suffix" class="h-3.5 w-3.5 text-orange-500" />
                                                </button>

                                                <button
                                                    v-for="item in filteredSuffixes"
                                                    :key="item"
                                                    type="button"
                                                    @click="selectSuffix(item)"
                                                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': form.title_suffix === item }"
                                                >
                                                    <span>{{ item }}</span>
                                                    <Check v-if="form.title_suffix === item" class="h-3.5 w-3.5 text-orange-500" />
                                                </button>

                                                <!-- Custom Input Option if search query doesn't match predefined -->
                                                <button
                                                    v-if="searchSuffix.trim() && !filteredSuffixes.includes(searchSuffix.trim())"
                                                    type="button"
                                                    @click="selectSuffix(searchSuffix.trim())"
                                                    class="w-full px-3 py-2 text-left bg-orange-50/70 hover:bg-orange-100 text-orange-700 font-medium flex items-center gap-1.5 border-t border-orange-100 mt-1"
                                                >
                                                    <Plus class="h-3.5 w-3.5 text-orange-600" />
                                                    <span class="truncate">Gunakan "<strong>{{ searchSuffix.trim() }}</strong>"</span>
                                                </button>

                                                <div v-if="filteredSuffixes.length === 0 && !searchSuffix.trim()" class="px-3 py-3 text-center text-slate-400 text-[11px]">
                                                    Tidak ada pilihan gelar
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bidang Spesialisasi & Status -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1.5 relative" ref="subjectDropdownRef">
                                    <label class="block text-xs font-bold text-slate-700">Mata Pelajaran / Bidang Keahlian</label>
                                    <div class="relative">
                                        <button
                                            type="button"
                                            @click="toggleSubjectDropdown"
                                            class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-medium text-left flex items-center justify-between transition-all hover:border-slate-300 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                            :class="form.specialization ? 'text-slate-900 font-semibold' : 'text-slate-400'"
                                        >
                                            <div class="flex items-center gap-2 truncate">
                                                <BookOpen class="h-3.5 w-3.5 text-orange-500 shrink-0" />
                                                <span class="truncate">
                                                    {{ form.specialization || 'Pilih Mata Pelajaran...' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0 ml-1">
                                                <span
                                                    v-if="form.specialization"
                                                    @click.stop="selectSubject('')"
                                                    class="p-0.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 cursor-pointer"
                                                    title="Hapus pilihan"
                                                >
                                                    <X class="h-3.5 w-3.5" />
                                                </span>
                                                <ChevronDown class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': isSubjectDropdownOpen }" />
                                            </div>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div
                                            v-if="isSubjectDropdownOpen"
                                            class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white rounded-xl shadow-xl border border-slate-200/90 py-2 overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                        >
                                            <!-- Search Input -->
                                            <div class="px-2.5 pb-2 border-b border-slate-100">
                                                <div class="relative">
                                                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                                    <input
                                                        v-model="searchSubject"
                                                        type="text"
                                                        placeholder="Cari mata pelajaran..."
                                                        class="w-full h-8 pl-8 pr-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 focus:outline-none"
                                                        @click.stop
                                                        autofocus
                                                    />
                                                </div>
                                            </div>

                                            <!-- List Items -->
                                            <div class="max-h-48 overflow-y-auto py-1 text-xs">
                                                <button
                                                    type="button"
                                                    @click="selectSubject('')"
                                                    class="w-full px-3 py-1.5 text-left text-slate-600 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': !form.specialization }"
                                                >
                                                    <span>(Tanpa Mata Pelajaran)</span>
                                                    <Check v-if="!form.specialization" class="h-3.5 w-3.5 text-orange-500" />
                                                </button>

                                                <button
                                                    v-for="sub in filteredSubjects"
                                                    :key="sub.id"
                                                    type="button"
                                                    @click="selectSubject(sub.name)"
                                                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-orange-50 hover:text-orange-600 flex items-center justify-between"
                                                    :class="{ 'font-semibold text-orange-600 bg-orange-50/50': form.specialization === sub.name }"
                                                >
                                                    <div class="flex items-center gap-2 truncate">
                                                        <BookOpen class="h-3.5 w-3.5 text-orange-500 shrink-0" />
                                                        <span class="truncate">{{ sub.name }}</span>
                                                    </div>
                                                    <Check v-if="form.specialization === sub.name" class="h-3.5 w-3.5 text-orange-500 shrink-0 ml-2" />
                                                </button>

                                                <!-- Custom Input Option if typed subject doesn't match list -->
                                                <button
                                                    v-if="searchSubject.trim() && !filteredSubjects.some(s => s.name.toLowerCase() === searchSubject.trim().toLowerCase())"
                                                    type="button"
                                                    @click="selectSubject(searchSubject.trim())"
                                                    class="w-full px-3 py-2 text-left bg-orange-50/70 hover:bg-orange-100 text-orange-700 font-medium flex items-center gap-1.5 border-t border-orange-100 mt-1"
                                                >
                                                    <Plus class="h-3.5 w-3.5 text-orange-600" />
                                                    <span class="truncate">Gunakan "<strong>{{ searchSubject.trim() }}</strong>" (Kustom)</span>
                                                </button>

                                                <div v-if="filteredSubjects.length === 0 && !searchSubject.trim()" class="px-3 py-3 text-center text-slate-400 text-[11px]">
                                                    Belum ada mata pelajaran aktif. Tambahkan di menu Mata Pelajaran.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Status Keaktifan</label>
                                    <select
                                        v-model="form.status"
                                        class="w-full h-10.5 px-3 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                    >
                                        <option value="active">Aktif Mengajar</option>
                                        <option value="inactive">Non-Aktif / Cuti</option>
                                    </select>
                                </div>
                            </div>

                            <!-- No HP & Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- 4. Nomor HP Tentor (Opsional) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Nomor HP / WhatsApp Tentor</label>
                                    <div class="relative">
                                        <Phone class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                        <input
                                            v-model="form.phone"
                                            type="text"
                                            placeholder="0812-3456-7890"
                                            class="w-full h-10.5 pl-10 pr-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                        />
                                    </div>
                                </div>

                                <!-- 5. Email Tentor (Opsional) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Email Tentor</label>
                                    <div class="relative">
                                        <Mail class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="tentor@bimbelnoname.com"
                                            class="w-full h-10.5 pl-10 pr-3.5 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 focus:outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- 6. KOTAK UPLOAD PHOTO PROFILE (DRAG & DROP + PREVIEW) -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Foto Profil Tentor (Opsional)</label>

                                <!-- Drag & Drop Box Container -->
                                <div
                                    @dragover.prevent="isPhotoDragging = true"
                                    @dragleave.prevent="isPhotoDragging = false"
                                    @drop.prevent="handlePhotoDrop"
                                    class="border-2 border-dashed rounded-2xl p-4 sm:p-6 text-center transition-all cursor-pointer relative"
                                    :class="isPhotoDragging ? 'border-orange-500 bg-orange-50/50 scale-99' : 'border-slate-200 hover:border-orange-400 bg-slate-50/40'"
                                    @click="fileInputRef?.click()"
                                >
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept="image/png, image/jpeg, image/jpg, image/webp"
                                        class="hidden"
                                        @change="handlePhotoSelect"
                                    />

                                    <!-- If Preview Available -->
                                    <div v-if="photoPreviewUrl" class="flex flex-col items-center gap-3">
                                        <div class="relative h-20 w-20 rounded-full overflow-hidden ring-4 ring-orange-500/20 shadow-md">
                                            <img :src="photoPreviewUrl" alt="Preview Foto" class="h-full w-full object-cover" />
                                        </div>
                                        <div class="text-xs space-y-1">
                                            <p class="font-bold text-slate-800">Foto Siap Diunggah</p>
                                            <p class="text-[11px] text-slate-400">Klik kotak untuk mengganti foto lain</p>
                                        </div>
                                        <button
                                            @click.stop="removePhoto"
                                            type="button"
                                            class="px-3 py-1 rounded-lg bg-rose-50 text-rose-600 font-semibold text-[11px] hover:bg-rose-100 transition-colors"
                                        >
                                            Hapus Foto
                                        </button>
                                    </div>

                                    <!-- If No Photo Selected Yet -->
                                    <div v-else class="space-y-2">
                                        <div class="h-12 w-12 mx-auto rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                            <UploadCloud class="h-6 w-6" />
                                        </div>
                                        <div class="text-xs">
                                            <span class="font-bold text-orange-600 hover:underline">Klik untuk pilih gambar</span>
                                            <span class="text-slate-500"> atau geser & taruh (drag and drop) file ke sini</span>
                                        </div>
                                        <p class="text-[10px] text-slate-400">Format JPG, PNG, WEBP (Maks. 2MB)</p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- ===================================================== -->
                    <!-- MODAL FOOTER (SAVE & CANCEL ACTIONS) -->
                    <!-- ===================================================== -->
                    <div v-show="!isMinimized" class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/80 rounded-b-3xl flex items-center justify-between">
                        <button
                            @click="closeModal"
                            type="button"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-200/60 text-xs font-semibold text-slate-600 transition-all active:scale-95"
                        >
                            Tutup
                        </button>

                        <button
                            form="tentorForm"
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Check v-else class="h-4 w-4" />
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Tentor' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </AuthenticatedLayout>
</template>
