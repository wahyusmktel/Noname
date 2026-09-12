<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    ShieldCheck,
    UserCheck,
    UserX,
    Users,
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
    Key,
    Mail,
    Phone,
    User as UserIcon,
    Shield,
    Check,
    ToggleLeft,
    ToggleRight,
    Info,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface AdminUserItem {
    id: string;
    name: string;
    username: string;
    email: string | null;
    phone: string | null;
    role: 'admin_bimbel' | 'staff' | 'superadmin';
    status: 'active' | 'inactive' | 'suspended';
    avatar: string | null;
    created_at: string;
}

interface Props {
    users: {
        data: AdminUserItem[];
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
        admin_bimbel: number;
        staff: number;
        inactive: number;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = page.props.auth.user;
const { toast, confirmAction } = useNotification();

// ==========================================
// SEARCH & FILTER STATE (Rule #8 Debounce)
// ==========================================
const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');
let searchTimer: any = null;

const applyFilters = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/admin-users', {
            search: searchQuery.value || undefined,
            role: roleFilter.value || undefined,
            status: statusFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
};

watch(searchQuery, applyFilters);
watch([roleFilter, statusFilter], () => {
    router.get('/admin-users', {
        search: searchQuery.value || undefined,
        role: roleFilter.value || undefined,
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
// FORM STATE
// ==========================================
const form = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    role: 'admin_bimbel',
    password: '',
    status: 'active',
});

// Open Modal for Create
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.username = '';
    form.email = '';
    form.phone = '';
    form.role = 'admin_bimbel';
    form.password = '';
    form.status = 'active';
    hasCustomPos.value = false;
    isMaximized.value = false;
    isMinimized.value = false;
    isModalOpen.value = true;
};

// Open Modal for Edit
const openEditModal = (user: AdminUserItem) => {
    isEditing.value = true;
    editingId.value = user.id;
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.username = user.username || '';
    form.email = user.email || '';
    form.phone = user.phone || '';
    form.role = (user.role === 'staff' ? 'staff' : 'admin_bimbel');
    form.password = '';
    form.status = user.status;
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
const saveUser = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/admin-users/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast('Data akun admin berhasil diperbarui!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Terjadi kesalahan validasi data.', 'error');
            },
        });
    } else {
        form.post('/admin-users', {
            preserveScroll: true,
            onSuccess: () => {
                toast('Akun pengguna admin berhasil ditambahkan!', 'success');
                closeModal();
            },
            onError: (err: any) => {
                toast(err.error || 'Terjadi kesalahan validasi data.', 'error');
            },
        });
    }
};

// Delete Handler with SweetAlert2
const deleteUser = (user: AdminUserItem) => {
    if (user.id === currentUser?.id) {
        toast('Anda tidak dapat menghapus akun Anda sendiri.', 'warning');
        return;
    }

    confirmAction(
        `Hapus Akun ${user.name}?`,
        `Akun admin "${user.name}" (${user.username}) akan dipindahkan ke arsip (Soft Delete) dan tidak dapat lagi masuk ke sistem.`,
        () => {
            router.delete(`/admin-users/${user.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Akun admin berhasil dihapus.', 'success');
                },
                onError: (err: any) => {
                    toast(err.error || 'Gagal menghapus akun admin.', 'error');
                },
            });
        }
    );
};

// Toggle Status Handler
const toggleUserStatus = (user: AdminUserItem) => {
    if (user.id === currentUser?.id) {
        toast('Anda tidak dapat mengubah status akun Anda sendiri.', 'warning');
        return;
    }

    const actionText = user.status === 'active' ? 'Nonaktifkan' : 'Aktifkan';
    confirmAction(
        `${actionText} Akun ${user.name}?`,
        user.status === 'active'
            ? `Akun ini tidak akan dapat login ke dalam sistem hingga diaktifkan kembali.`
            : `Akun ini akan dapat login kembali ke dalam sistem.`,
        () => {
            router.post(`/admin-users/${user.id}/toggle-status`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast(`Akun berhasil ${user.status === 'active' ? 'dinonaktifkan' : 'diaktifkan'}!`, 'success');
                },
                onError: (err: any) => {
                    toast(err.error || 'Gagal mengubah status akun.', 'error');
                },
            });
        }
    );
};
</script>

<template>
    <Head title="Manajemen Pengguna Admin & Staff" />

    <AuthenticatedLayout>
        <div class="space-y-6 pb-12">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-xs">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        Pengguna Admin & Staff
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola akun pengelola bimbel, hak akses peran Administrator Bimbel dan Staff Operasional.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold shadow-xs shadow-indigo-200 transition-all cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Pengguna</span>
                    </button>
                </div>
            </div>

            <!-- Kartu Statistik -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Pengguna</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.total }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Admin & staff terdaftar</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <Users class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Admin Bimbel</p>
                        <p class="text-2xl font-bold text-purple-700 mt-1">{{ stats.admin_bimbel }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Akses penuh lembaga</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <Shield class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Staff Operasional</p>
                        <p class="text-2xl font-bold text-sky-700 mt-1">{{ stats.staff }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Bantuan administrasi</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <UserCheck class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Akun Nonaktif</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.inactive }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Dibatasi aksesnya</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <UserX class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Filter & Pencarian -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="relative w-full md:w-80">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, username, email..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 hover:bg-slate-100/80 focus:bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none"
                    />
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <!-- Filter Role -->
                    <select
                        v-model="roleFilter"
                        class="w-full md:w-44 text-sm bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                    >
                        <option value="">Semua Peran</option>
                        <option value="admin_bimbel">Admin Bimbel</option>
                        <option value="staff">Staff Operasional</option>
                    </select>

                    <!-- Filter Status -->
                    <select
                        v-model="statusFilter"
                        class="w-full md:w-40 text-sm bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Tabel Data Pengguna -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-600 text-xs uppercase font-semibold tracking-wider">
                                <th class="py-3.5 px-4 w-12 text-center">#</th>
                                <th class="py-3.5 px-4">Pengguna</th>
                                <th class="py-3.5 px-4">Username</th>
                                <th class="py-3.5 px-4">Kontak</th>
                                <th class="py-3.5 px-4 text-center">Peran</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="(user, idx) in users.data"
                                :key="user.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-xs">
                                    {{ (users.current_page - 1) * users.per_page + idx + 1 }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                            {{ user.name.slice(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 flex items-center gap-2">
                                                <span>{{ user.name }}</span>
                                                <span
                                                    v-if="user.id === currentUser?.id"
                                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                >
                                                    Anda
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500">{{ user.email || 'Tanpa email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        @{{ user.username }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 text-xs">
                                    <div v-if="user.phone" class="flex items-center gap-1.5">
                                        <Phone class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ user.phone }}</span>
                                    </div>
                                    <div v-else class="text-slate-400 italic">-</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="user.role === 'admin_bimbel'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200/60"
                                    >
                                        <Shield class="w-3 h-3" />
                                        Admin Bimbel
                                    </span>
                                    <span
                                        v-else-if="user.role === 'staff'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60"
                                    >
                                        <UserCheck class="w-3 h-3" />
                                        Staff Operasional
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        :disabled="user.id === currentUser?.id"
                                        :title="user.id === currentUser?.id ? 'Tidak dapat mengubah status akun sendiri' : 'Klik untuk mengubah status'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold transition-all cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-200/60 hover:bg-amber-100'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        {{ user.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(user)"
                                            type="button"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                            title="Edit Akun"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="user.id !== currentUser?.id"
                                            @click="deleteUser(user)"
                                            type="button"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Akun"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <Users class="w-10 h-10 mx-auto text-slate-300 mb-2" />
                                    <p class="font-medium text-slate-600">Tidak ada data pengguna ditemukan</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.total > 0"
                    class="p-4 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500"
                >
                    <p>
                        Menampilkan <span class="font-semibold text-slate-700">{{ users.from || 0 }}</span> - <span class="font-semibold text-slate-700">{{ users.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ users.total }}</span> pengguna
                    </p>

                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="(link, i) in users.links"
                            :key="i"
                            :disabled="!link.url"
                            @click="router.get(link.url!, {}, { preserveState: true, preserveScroll: true })"
                            class="px-3 py-1.5 rounded-lg font-medium transition-colors cursor-pointer disabled:cursor-not-allowed disabled:opacity-40"
                            :class="link.active ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- DRAGGABLE / RESIZABLE MODAL: FORM USER      -->
        <!-- ========================================== -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            :class="{ 'pointer-events-none': isMinimized }"
        >
            <!-- Backdrop (disabled when minimized) -->
            <div
                v-if="!isMinimized"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                @click="closeModal"
            />

            <!-- Modal Dialog Container -->
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-150 z-10 pointer-events-auto"
                :class="[
                    isMaximized
                        ? '!fixed !inset-4 !w-auto !h-auto !max-w-none !transform-none !rounded-2xl'
                        : isMinimized
                        ? '!fixed !bottom-6 !right-6 !w-80 !h-auto !transform-none !shadow-xl'
                        : 'w-full max-w-xl max-h-[90vh]'
                ]"
                :style="hasCustomPos && !isMaximized && !isMinimized ? { transform: `translate(${modalPos.x}px, ${modalPos.y}px)` } : {}"
            >
                <!-- Modal Header (Draggable Handle) -->
                <div
                    @mousedown="startDrag"
                    class="px-5 py-4 border-b border-slate-100 flex items-center justify-between select-none bg-slate-50/70"
                    :class="{ 'cursor-grab': !isMaximized && !isMinimized, 'cursor-grabbing': isDragging }"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-lg bg-indigo-100 text-indigo-700">
                            <ShieldCheck class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm">
                                {{ isEditing ? 'Edit Pengguna Admin' : 'Tambah Pengguna Admin Baru' }}
                            </h3>
                            <p v-if="!isMinimized" class="text-[11px] text-slate-400">
                                {{ isEditing ? 'Perbarui informasi profil dan hak akses' : 'Lengkapi data akun admin atau staff baru' }}
                            </p>
                        </div>
                    </div>

                    <!-- Window Control Buttons -->
                    <div class="flex items-center gap-1 text-slate-400">
                        <button
                            @click.stop="toggleMinimize"
                            type="button"
                            class="p-1.5 hover:bg-slate-200/70 rounded-lg hover:text-slate-700 transition-colors cursor-pointer"
                            :title="isMinimized ? 'Pulihkan' : 'Minimalkan'"
                        >
                            <Minus class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click.stop="toggleMaximize"
                            type="button"
                            class="p-1.5 hover:bg-slate-200/70 rounded-lg hover:text-slate-700 transition-colors cursor-pointer"
                            :title="isMaximized ? 'Pulihkan Ukuran' : 'Maksimalkan'"
                        >
                            <Maximize2 v-if="!isMaximized" class="w-3.5 h-3.5" />
                            <Minimize2 v-else class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click.stop="closeModal"
                            type="button"
                            class="p-1.5 hover:bg-rose-100 rounded-lg hover:text-rose-600 transition-colors cursor-pointer"
                            title="Tutup"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Modal Body (Hidden when Minimized) -->
                <form
                    v-if="!isMinimized"
                    @submit.prevent="saveUser"
                    class="flex-1 overflow-y-auto p-6 space-y-4 text-slate-700"
                >
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <UserIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: Budi Santoso, S.Pd."
                                class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                :class="form.errors.name ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Username -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Username <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="text-xs font-bold text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2">@</span>
                                <input
                                    v-model="form.username"
                                    type="text"
                                    required
                                    placeholder="budisantoso"
                                    class="w-full pl-8 pr-4 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all font-mono"
                                    :class="form.errors.username ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                                />
                            </div>
                            <p v-if="form.errors.username" class="text-xs text-rose-500 mt-1">{{ form.errors.username }}</p>
                        </div>

                        <!-- Peran / Role -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Peran / Hak Akses <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.role"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                :class="form.errors.role ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                            >
                                <option value="admin_bimbel">Admin Bimbel (Akses Penuh)</option>
                                <option value="staff">Staff Operasional (Administrasi)</option>
                            </select>
                            <p v-if="form.errors.role" class="text-xs text-rose-500 mt-1">{{ form.errors.role }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Alamat Email (Opsional)
                            </label>
                            <div class="relative">
                                <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="budi@example.com"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    :class="form.errors.email ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">{{ form.errors.email }}</p>
                        </div>

                        <!-- Nomor HP -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Nomor WhatsApp / HP (Opsional)
                            </label>
                            <div class="relative">
                                <Phone class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="081234567890"
                                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    :class="form.errors.phone ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                                />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Password <span v-if="!isEditing" class="text-rose-500">*</span>
                            <span v-else class="text-slate-400 font-normal">(Kosongkan jika tidak ingin mengubah)</span>
                        </label>
                        <div class="relative">
                            <Key class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!isEditing"
                                :placeholder="isEditing ? '•••••••• (Tetap sama)' : 'Minimal 6 karakter'"
                                class="w-full pl-10 pr-4 py-2.5 text-sm bg-white border rounded-xl outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                :class="form.errors.password ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500'"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Status Akun (Switch) -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-800">Status Akun Aktif</span>
                            <p class="text-[11px] text-slate-500">Jika dinonaktifkan, pengguna tidak dapat masuk ke aplikasi.</p>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="form.status === 'active'"
                                @change="form.status = ($event.target as HTMLInputElement).checked ? 'active' : 'inactive'"
                                :disabled="isEditing && editingId === currentUser?.id"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 flex items-center justify-end gap-2.5">
                        <button
                            @click="closeModal"
                            type="button"
                            class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-semibold shadow-xs shadow-indigo-200 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
