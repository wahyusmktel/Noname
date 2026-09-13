<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Building2,
    Users,
    GraduationCap,
    UserCheck,
    Calendar,
    CalendarCheck,
    QrCode,
    BookOpen,
    ClipboardList,
    FileBarChart,
    LogOut,
    Bell,
    ChevronDown,
    ChevronRight,
    Menu,
    X,
    Layers,
    Shield,
    CheckCircle2,
    Sparkles,
    User,
    SlidersHorizontal,
    HelpCircle,
    BadgeCheck,
    ShieldCheck,
    Database,
    Globe,
    Server,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Props {
    title?: string;
}

const props = defineProps<Props>();
const page = usePage();
const { toast, confirmAction } = useNotification();

// User & Tenant info from Inertia shared props
const user = computed(() => (page.props.auth as any)?.user);
const tenant = computed(() => user.value?.tenant);

// Sidebar states
const isSidebarOpen = ref(true); // Desktop collapse state
const isMobileMenuOpen = ref(false); // Mobile drawer state

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// Profile & Notification Dropdowns
const isProfileDropdownOpen = ref(false);
const isNotificationDropdownOpen = ref(false);

const toggleProfileDropdown = () => {
    isProfileDropdownOpen.value = !isProfileDropdownOpen.value;
    if (isProfileDropdownOpen.value) isNotificationDropdownOpen.value = false;
};

const toggleNotificationDropdown = () => {
    isNotificationDropdownOpen.value = !isNotificationDropdownOpen.value;
    if (isNotificationDropdownOpen.value) isProfileDropdownOpen.value = false;
};

// Close dropdowns on outside click
const handleOutsideClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (!target.closest('#profile-dropdown-container')) {
        isProfileDropdownOpen.value = false;
    }
    if (!target.closest('#notification-dropdown-container')) {
        isNotificationDropdownOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleOutsideClick);
    loadReadNotificationIds();

    // Auto trigger toast jika ada pesan flash dari session backend
    const flash = (page.props as any).flash;
    if (flash?.success) {
        toast(flash.success, 'success');
    }
    if (flash?.error) {
        toast(flash.error, 'error');
    }
});

onUnmounted(() => {
    window.removeEventListener('click', handleOutsideClick);
});

// Treeview Menu definition
interface MenuItem {
    id: string;
    title: string;
    icon: any;
    href?: string;
    badge?: string;
    submenus?: {
        title: string;
        href: string;
        badge?: string;
    }[];
}

const openMenus = ref<Record<string, boolean>>({});

const isSubmenuActive = (menu: MenuItem) => {
    if (!menu.submenus) return false;
    return menu.submenus.some(sub => sub.href !== '#' && (page.url === sub.href || page.url.startsWith(sub.href)));
};

const toggleSubmenu = (menuId: string) => {
    const willOpen = !openMenus.value[menuId];
    // Accordion: tutup semua submenu lain agar hanya menu yang dibuka yang aktif
    Object.keys(openMenus.value).forEach(key => {
        openMenus.value[key] = false;
    });
    openMenus.value[menuId] = willOpen;
};

const handleMenuClick = (menu: MenuItem) => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        Object.keys(openMenus.value).forEach(key => {
            openMenus.value[key] = false;
        });
        openMenus.value[menu.id] = true;
    } else {
        toggleSubmenu(menu.id);
    }
};

const navigationMenus = computed<MenuItem[]>(() => {
    // Menu khusus peran Tutor / Guru (saat ini hanya 1 menu: Absensi Siswa)
    if (user.value?.role === 'tutor') {
        return [
            {
                id: 'tutor_attendance',
                title: 'Absensi Siswa',
                icon: CalendarCheck,
                href: '/tutor/attendance',
            },
        ];
    }

    // Menu khusus peran Orang Tua / Siswa (Portal Monitoring Belajar Anak)
    if (user.value?.role === 'siswa' || user.value?.role === 'orang_tua') {
        return [
            {
                id: 'parent_dashboard',
                title: 'Monitoring Ananda',
                icon: GraduationCap,
                href: '/student/dashboard',
            },
        ];
    }

    return [
        {
            id: 'dashboard',
            title: 'Dashboard',
            icon: LayoutDashboard,
            href: '/dashboard',
        },
        {
            id: 'lembaga',
            title: 'Lembaga Bimbel',
            icon: Building2,
            submenus: [
                { title: 'Profil Lembaga', href: '/lembaga/profil' },
                { title: 'Tahun Pelajaran', href: '/academic-years' },
            ],
        },
        {
            id: 'landing_cms',
            title: 'Kelola Landing Page',
            icon: Globe,
            href: '/landing-page-settings',
        },
        {
            id: 'dev_environment',
            title: 'Kontrol Website Dev',
            icon: Server,
            href: '/system/dev-environment',
        },
        {
            id: 'manajemen_data',
            title: 'Manajemen Data',
            icon: Database,
            submenus: [
                { title: 'Mata Pelajaran', href: '/subjects' },
                { title: 'Kelompok Bimbel', href: '/study-groups' },
                { title: 'Tentor (Guru Bimbel)', href: '/tentors' },
                { title: 'Peserta Didik', href: '/students' },
            ],
        },
        {
            id: 'admin_users',
            title: 'Pengguna Admin',
            icon: ShieldCheck,
            href: '/admin-users',
        },
        {
            id: 'presensi',
            title: 'Presensi & Absensi',
            icon: CalendarCheck,
            submenus: [
                { title: 'Input Presensi Manual', href: '/attendance/manual' },
            ],
        },
        {
            id: 'laporan',
            title: 'Laporan & Rekapitulasi',
            icon: FileBarChart,
            submenus: [
                { title: 'Kehadiran Peserta Didik', href: '/reports/student-attendance' },
                { title: 'Kehadiran Guru', href: '/reports/tutor-attendance' },
            ],
        },
    ];
});

// Sinkronkan status open submenu hanya untuk menu yang aktif sesuai rute saat ini
const syncOpenMenusWithRoute = () => {
    let matchedMenuId: string | null = null;
    navigationMenus.value.forEach(menu => {
        if (isSubmenuActive(menu)) {
            matchedMenuId = menu.id;
        }
    });

    const newOpenState: Record<string, boolean> = {};
    navigationMenus.value.forEach(menu => {
        if (menu.submenus) {
            newOpenState[menu.id] = (menu.id === matchedMenuId);
        }
    });
    openMenus.value = newOpenState;
};

// Pantau perubahan URL rute agar treeview otomatis menyesuaikan
watch(
    () => page.url,
    () => {
        syncOpenMenusWithRoute();
    },
    { immediate: true }
);

// ==========================================
// NOTIFIKASI AKTIVITAS ABSENSI GURU
// ==========================================
const READ_NOTIFS_KEY = 'bimbel_read_notification_ids';
const readNotificationIds = ref<string[]>([]);

const loadReadNotificationIds = () => {
    try {
        const stored = localStorage.getItem(READ_NOTIFS_KEY);
        if (stored) {
            readNotificationIds.value = JSON.parse(stored);
        }
    } catch {
        readNotificationIds.value = [];
    }
};

const saveReadNotificationIds = (ids: string[]) => {
    try {
        localStorage.setItem(READ_NOTIFS_KEY, JSON.stringify(ids));
        readNotificationIds.value = ids;
    } catch (e) {
        console.error(e);
    }
};

const isParentOrStudent = computed(() => {
    return user.value?.role === 'siswa' || user.value?.role === 'orang_tua';
});

const notifications = computed(() => {
    const raw = (page.props as any).recent_notifications || [];
    return raw.map((item: any) => ({
        ...item,
        unread: !readNotificationIds.value.includes(item.id),
    }));
});

const unreadCount = computed(() => {
    return notifications.value.filter((n: any) => n.unread).length;
});

const markAllAsRead = () => {
    const allIds = notifications.value.map((n: any) => n.id);
    saveReadNotificationIds(Array.from(new Set([...readNotificationIds.value, ...allIds])));
    toast('Semua notifikasi ditandai sudah dibaca', 'info');
};

const handleNotificationClick = (item: any) => {
    if (!readNotificationIds.value.includes(item.id)) {
        saveReadNotificationIds([...readNotificationIds.value, item.id]);
    }
    isNotificationDropdownOpen.value = false;
    if (item.url) {
        router.visit(item.url);
    }
};

// Logout handler dengan konfirmasi SweetAlert2 (Mandatory Rule #3)
const handleLogout = async () => {
    isProfileDropdownOpen.value = false;
    const confirmed = await confirmAction({
        title: 'Keluar dari Sistem?',
        text: 'Anda akan mengakhiri sesi login saat ini di sistem Absensi Bimbel.',
        confirmText: 'Ya, Keluar',
        cancelText: 'Batal',
        icon: 'warning',
    });

    if (confirmed) {
        router.post('/logout', {}, {
            onSuccess: () => {
                toast('Anda telah berhasil keluar.', 'info');
            },
        });
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased flex flex-col">
        <!-- TOPBAR -->
        <header class="sticky top-0 z-40 flex h-16 w-full items-center justify-between border-b border-slate-200/80 bg-white/90 backdrop-blur-md px-4 sm:px-6 transition-all duration-300 shadow-xs">
            <!-- Left: Sidebar Toggle & Branding -->
            <div class="flex items-center gap-3">
                <button
                    @click="toggleSidebar"
                    class="hidden md:flex items-center justify-center h-10 w-10 rounded-xl text-slate-600 hover:text-orange-600 hover:bg-orange-50 transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                    title="Buka/Tutup Menu"
                >
                    <Menu class="h-5 w-5" />
                </button>

                <!-- Mobile Hamburger Button -->
                <button
                    @click="isMobileMenuOpen = true"
                    class="flex md:hidden items-center justify-center h-10 w-10 rounded-xl text-slate-600 hover:text-orange-600 hover:bg-orange-50 transition-colors focus:outline-none"
                    title="Menu"
                >
                    <Menu class="h-5 w-5" />
                </button>

                <!-- Logo & Tenant Info on Topbar -->
                <div class="flex items-center gap-2.5">
                    <img
                        :src="tenant?.logo_url || (page.props as any).app_logo || '/images/logo_bnn.png'"
                        :alt="tenant?.name ?? 'Logo Bimbel'"
                        class="h-9 w-9 object-contain rounded-xl bg-white p-0.5 border border-slate-200/80 shadow-xs shrink-0"
                    />
                    <div class="hidden sm:block">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-900 tracking-tight text-base">{{ tenant?.name ?? 'Bimbel No Name' }}</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Sistem Manajemen Bimbel</p>
                    </div>
                </div>
            </div>

            <!-- Right: Notifications & User Profile Dropdown -->
            <div class="flex items-center gap-2.5 ml-auto">
                <!-- Notification Bell Container -->
                <div id="notification-dropdown-container" class="relative">
                    <button
                        @click="toggleNotificationDropdown"
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:text-orange-600 hover:bg-orange-50 transition-colors focus:outline-none"
                        title="Notifikasi Aktivitas Guru"
                    >
                        <Bell class="h-5 w-5" />
                        <span v-if="unreadCount > 0" class="absolute top-2 right-2 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-orange-500 ring-2 ring-white"></span>
                        </span>
                    </button>

                    <!-- Notifications Dropdown Popup -->
                    <transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div
                            v-if="isNotificationDropdownOpen"
                            class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-xl border border-slate-100 py-3 z-50 animate-in fade-in"
                        >
                            <div class="flex items-center justify-between px-4 pb-2.5 border-b border-slate-100">
                                <div>
                                    <span class="font-bold text-sm text-slate-800">
                                        {{ isParentOrStudent ? 'Presensi Kehadiran Ananda' : 'Aktivitas Presensi Guru' }}
                                    </span>
                                    <p class="text-[11px] text-slate-400">
                                        {{ isParentOrStudent ? 'Pemberitahuan absensi kelas ananda' : 'Pemberitahuan absensi kelas terkini' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="unreadCount > 0"
                                        class="text-[11px] font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full"
                                    >
                                        {{ unreadCount }} Baru
                                    </span>
                                    <button
                                        v-if="unreadCount > 0"
                                        @click.stop="markAllAsRead"
                                        class="text-[11px] text-slate-400 hover:text-orange-600 transition-colors font-medium hover:underline"
                                    >
                                        Tandai Dibaca
                                    </button>
                                </div>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
                                <div
                                    v-if="notifications.length === 0"
                                    class="py-8 text-center text-slate-400 text-xs px-4"
                                >
                                    <CalendarCheck class="w-8 h-8 text-slate-300 mx-auto mb-2 opacity-60" />
                                    {{ isParentOrStudent ? 'Belum ada pemberitahuan presensi ananda.' : 'Belum ada aktivitas absensi baru dari guru.' }}
                                </div>
                                <div
                                    v-for="item in notifications"
                                    :key="item.id"
                                    @click="handleNotificationClick(item)"
                                    class="p-3.5 hover:bg-slate-50/90 transition-colors cursor-pointer flex gap-3 items-start group"
                                    :class="{ 'bg-orange-50/40': item.unread }"
                                >
                                    <div
                                        class="h-2 w-2 rounded-full mt-1.5 shrink-0 transition-colors"
                                        :class="item.unread ? 'bg-orange-500' : 'bg-transparent group-hover:bg-slate-300'"
                                    ></div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-xs font-semibold leading-snug truncate"
                                            :class="item.unread ? 'text-slate-900 font-bold' : 'text-slate-700'"
                                        >
                                            {{ item.title }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed line-clamp-2">
                                            {{ item.desc }}
                                        </p>
                                        <span class="text-[10px] text-slate-400 font-medium block mt-1">
                                            {{ item.time }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2.5 px-4 border-t border-slate-100 text-center">
                                <Link
                                    :href="isParentOrStudent ? '/student/dashboard' : '/reports/tutor-attendance'"
                                    @click="isNotificationDropdownOpen = false"
                                    class="text-xs font-semibold text-orange-600 hover:text-orange-700 inline-flex items-center gap-1 hover:underline"
                                >
                                    <span>{{ isParentOrStudent ? 'Lihat Riwayat Kehadiran Ananda' : 'Lihat Semua Rekap Kehadiran Guru' }}</span>
                                    <span>&rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </transition>
                </div>

                <div class="h-5 w-[1px] bg-slate-200 mx-0.5"></div>

                <!-- Profile Circle & Dropdown Container -->
                <div id="profile-dropdown-container" class="relative">
                    <button
                        @click="toggleProfileDropdown"
                        class="flex items-center gap-2 p-1 pl-1.5 pr-2 rounded-xl hover:bg-slate-100/80 transition-colors focus:outline-none border border-transparent hover:border-slate-200/60"
                    >
                        <!-- Profile Circle Avatar with soft orange ring -->
                        <div class="relative shrink-0">
                            <div class="h-9 w-9 rounded-full ring-2 ring-orange-500/30 bg-gradient-to-tr from-orange-500 via-amber-400 to-orange-300 flex items-center justify-center text-white font-bold text-xs shadow-sm overflow-hidden">
                                <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="user.name" class="h-full w-full object-cover" />
                                <span v-else>{{ user?.name ? user.name.charAt(0).toUpperCase() : 'A' }}</span>
                            </div>
                            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white shadow-xs"></span>
                        </div>
                        <div class="hidden md:block text-left">
                            <span class="block text-xs font-bold text-slate-800 leading-tight max-w-[120px] truncate">{{ user?.name ?? 'Administrator' }}</span>
                            <span class="block text-[10px] font-medium text-orange-600 capitalize">
                                {{ user?.role === 'admin_bimbel' ? 'Admin Lembaga' : (user?.role === 'tutor' ? 'Guru Bimbel' : (user?.role === 'siswa' || user?.role === 'orang_tua' ? 'Wali Murid' : user?.role)) }}
                            </span>
                        </div>
                        <ChevronDown class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isProfileDropdownOpen }" />
                    </button>

                    <!-- Profile Dropdown Menu -->
                    <transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div
                            v-if="isProfileDropdownOpen"
                            class="absolute right-0 mt-2 w-64 rounded-2xl bg-white shadow-xl border border-slate-100 py-2 z-50 text-xs"
                        >
                            <!-- User Header in Dropdown -->
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ user?.name }}</p>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ user?.email }}</p>
                                <div class="mt-2 flex items-center gap-1">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700">
                                        <BadgeCheck class="h-3 w-3 text-orange-500" />
                                        {{ tenant?.name ?? 'Bimbel' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu links -->
                            <div class="py-1">
                                <Link
                                    href="/user/profile"
                                    @click="isProfileDropdownOpen = false"
                                    class="flex items-center gap-2.5 px-4 py-2 font-medium text-slate-700 hover:text-orange-600 hover:bg-orange-50/50 transition-colors"
                                >
                                    <User class="h-4 w-4 text-slate-400" />
                                    <span>Profil Saya</span>
                                </Link>
                                <Link
                                    href="/user/settings"
                                    @click="isProfileDropdownOpen = false"
                                    class="flex items-center gap-2.5 px-4 py-2 font-medium text-slate-700 hover:text-orange-600 hover:bg-orange-50/50 transition-colors"
                                >
                                    <SlidersHorizontal class="h-4 w-4 text-slate-400" />
                                    <span>Pengaturan Akun</span>
                                </Link>
                                <Link
                                    href="/help-center"
                                    @click="isProfileDropdownOpen = false"
                                    class="flex items-center gap-2.5 px-4 py-2 font-medium text-slate-700 hover:text-orange-600 hover:bg-orange-50/50 transition-colors"
                                >
                                    <HelpCircle class="h-4 w-4 text-slate-400" />
                                    <span>Pusat Bantuan & Panduan</span>
                                </Link>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-slate-100 my-1"></div>

                            <!-- Logout Button (Triggers SweetAlert2) -->
                            <button
                                @click="handleLogout"
                                class="w-full flex items-center gap-2.5 px-4 py-2.5 font-semibold text-rose-600 hover:bg-rose-50/80 transition-colors text-left"
                            >
                                <LogOut class="h-4 w-4 text-rose-500" />
                                <span>Keluar dari Sistem</span>
                            </button>
                        </div>
                    </transition>
                </div>
            </div>
        </header>

        <!-- WRAPPER: SIDEBAR + MAIN CONTENT -->
        <div class="flex flex-1 relative">
            <!-- DESKTOP SIDEBAR (Treeview Smooth Concept) -->
            <aside
                class="hidden md:flex flex-col border-r border-slate-200/80 bg-white sticky top-16 h-[calc(100vh-4rem)] z-30 transition-all duration-300 ease-in-out shrink-0 select-none"
                :class="isSidebarOpen ? 'w-64' : 'w-20'"
            >
                <!-- Lembaga Tagline in Sidebar -->
                <div v-if="isSidebarOpen" class="px-5 py-4 border-b border-slate-100 bg-orange-50/30">
                    <span class="text-[10px] font-bold tracking-wider text-orange-600 uppercase">Lembaga Terdaftar</span>
                    <p class="text-xs font-bold text-slate-800 truncate mt-0.5">{{ tenant?.name ?? 'Bimbel Mandiri' }}</p>
                </div>

                <!-- Navigation Treeview Scrollable (Smooth and Independent) -->
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1.5 scrollbar-thin scroll-smooth">
                    <div v-for="menu in navigationMenus" :key="menu.id" class="text-xs">
                        <!-- Case 1: Simple Single Link (No Submenus) -->
                        <Link
                            v-if="!menu.submenus"
                            :href="menu.href ?? '#'"
                            class="flex items-center rounded-xl font-semibold transition-all group"
                            :class="[
                                isSidebarOpen ? 'w-full gap-3 px-3 py-2.5' : 'justify-center p-2.5 mx-auto w-11 h-11',
                                (menu.href && (page.url === menu.href || (menu.href !== '/' && page.url.startsWith(menu.href))))
                                    ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-sm shadow-orange-500/25'
                                    : 'text-slate-600 hover:text-orange-600 hover:bg-orange-50/70'
                            ]"
                            :title="!isSidebarOpen ? menu.title : undefined"
                        >
                            <component :is="menu.icon" class="h-5 w-5 shrink-0 transition-transform group-hover:scale-105" />
                            <span v-if="isSidebarOpen" class="flex-1 truncate">{{ menu.title }}</span>
                            <span v-if="isSidebarOpen && menu.badge" class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-white/20 text-white">
                                {{ menu.badge }}
                            </span>
                        </Link>

                        <!-- Case 2: Treeview Menu with Submenus -->
                        <div v-else class="space-y-1">
                            <!-- Parent Menu Button -->
                            <button
                                @click="handleMenuClick(menu)"
                                class="flex items-center rounded-xl font-semibold transition-colors group cursor-pointer"
                                :class="[
                                    isSidebarOpen ? 'w-full gap-3 px-3 py-2.5 text-slate-700 hover:text-orange-600 hover:bg-orange-50/70' : 'justify-center p-2.5 mx-auto w-11 h-11 text-slate-600 hover:text-orange-600 hover:bg-orange-50/70',
                                    isSubmenuActive(menu)
                                        ? 'bg-orange-50 text-orange-700 font-bold'
                                        : (openMenus[menu.id] && isSidebarOpen ? 'bg-orange-50/40 text-orange-700' : '')
                                ]"
                                :title="!isSidebarOpen ? menu.title : undefined"
                            >
                                <component
                                    :is="menu.icon"
                                    class="h-5 w-5 shrink-0 transition-colors"
                                    :class="(openMenus[menu.id] && isSidebarOpen) || isSubmenuActive(menu) ? 'text-orange-600' : 'text-slate-500 group-hover:text-orange-500'"
                                />
                                <span v-if="isSidebarOpen" class="flex-1 text-left truncate">{{ menu.title }}</span>
                                <span v-if="isSidebarOpen && menu.badge" class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-orange-100 text-orange-700">
                                    {{ menu.badge }}
                                </span>
                                <ChevronRight
                                    v-if="isSidebarOpen"
                                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-90 text-orange-600': openMenus[menu.id] }"
                                />
                            </button>

                            <!-- Smooth Submenu Items (Treeview Child) -->
                            <transition
                                enter-active-class="transition-all ease-out duration-200 overflow-hidden"
                                enter-from-class="max-h-0 opacity-0"
                                enter-to-class="max-h-72 opacity-100"
                                leave-active-class="transition-all ease-in duration-150 overflow-hidden"
                                leave-from-class="max-h-72 opacity-100"
                                leave-to-class="max-h-0 opacity-0"
                            >
                                <div
                                    v-if="isSidebarOpen && openMenus[menu.id]"
                                    class="ml-6 pl-3 border-l-2 border-orange-200 space-y-1 pt-1"
                                >
                                    <Link
                                        v-for="(sub, subIdx) in menu.submenus"
                                        :key="subIdx"
                                        :href="sub.href"
                                        class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-medium transition-colors group"
                                        :class="(page.url === sub.href || (sub.href !== '#' && page.url.startsWith(sub.href))) ? 'bg-orange-50 text-orange-600 font-bold' : 'text-slate-600 hover:text-orange-600 hover:bg-orange-50/60'"
                                    >
                                        <span class="truncate">{{ sub.title }}</span>
                                        <span v-if="sub.badge" class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">
                                            {{ sub.badge }}
                                        </span>
                                    </Link>
                                </div>
                            </transition>
                        </div>
                    </div>
                </nav>

                <!-- Sidebar Bottom Status -->
                <div v-if="isSidebarOpen" class="p-3 border-t border-slate-100 bg-slate-50/50">
                    <div class="rounded-xl bg-white border border-slate-200/80 p-3 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sistem Siap Digunakan</span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1 leading-tight">Presensi otomatis real-time</p>
                    </div>
                </div>
                <div v-else class="p-3 border-t border-slate-100 flex justify-center">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" title="Sistem Siap Digunakan"></span>
                </div>
            </aside>

            <!-- MOBILE SIDEBAR DRAWER OVERLAY -->
            <transition
                enter-active-class="transition-opacity ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isMobileMenuOpen"
                    class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs md:hidden"
                    @click="closeMobileMenu"
                ></div>
            </transition>

            <!-- Mobile Drawer Menu -->
            <transition
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div
                    v-if="isMobileMenuOpen"
                    class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl flex flex-col md:hidden"
                >
                    <!-- Mobile Drawer Header -->
                    <div class="flex items-center justify-between px-4 py-4 border-b border-slate-100 bg-orange-50/40">
                        <div class="flex items-center gap-2.5">
                            <img
                                :src="tenant?.logo_url || (page.props as any).app_logo || '/images/logo_bnn.png'"
                                :alt="tenant?.name ?? 'Logo Bimbel'"
                                class="h-8 w-8 object-contain rounded-xl bg-white p-0.5 border border-slate-200/80 shadow-xs shrink-0"
                            />
                            <span class="font-bold text-slate-900 text-sm truncate">{{ tenant?.name ?? 'Bimbel No Name' }}</span>
                        </div>
                        <button @click="closeMobileMenu" class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Mobile Drawer Nav items -->
                    <div class="flex-1 overflow-y-auto p-3 space-y-1">
                        <div v-for="menu in navigationMenus" :key="'mob-' + menu.id" class="text-xs">
                            <Link
                                v-if="!menu.submenus"
                                :href="menu.href ?? '#'"
                                @click="closeMobileMenu"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-semibold transition-all"
                                :class="(menu.href && (page.url === menu.href || (menu.href !== '/' && page.url.startsWith(menu.href)))) ? 'bg-orange-500 text-white font-bold' : 'text-slate-600 hover:bg-orange-50'"
                            >
                                <component :is="menu.icon" class="h-5 w-5 shrink-0" />
                                <span>{{ menu.title }}</span>
                            </Link>

                            <div v-else class="space-y-1">
                                <button
                                    @click="toggleSubmenu(menu.id)"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl font-semibold text-slate-700 hover:bg-orange-50"
                                >
                                    <div class="flex items-center gap-3">
                                        <component :is="menu.icon" class="h-5 w-5 text-slate-500" />
                                        <span>{{ menu.title }}</span>
                                    </div>
                                    <ChevronRight class="h-4 w-4 text-slate-400 transition-transform" :class="{ 'rotate-90': openMenus[menu.id] }" />
                                </button>
                                <div v-if="openMenus[menu.id]" class="ml-7 pl-3 border-l-2 border-orange-200 space-y-1 py-1">
                                    <Link
                                        v-for="(sub, sIdx) in menu.submenus"
                                        :key="sIdx"
                                        :href="sub.href"
                                        @click="closeMobileMenu"
                                        class="block py-1.5 text-xs font-medium transition-colors"
                                        :class="page.url.startsWith(sub.href) && sub.href !== '#' ? 'text-orange-600 font-bold' : 'text-slate-600 hover:text-orange-600'"
                                    >
                                        {{ sub.title }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-100">
                        <button
                            @click="handleLogout"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-rose-50 text-rose-600 font-semibold text-xs hover:bg-rose-100 transition-colors"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Keluar dari Akun</span>
                        </button>
                    </div>
                </div>
            </transition>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                <div class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    <slot />
                </div>

                <!-- FOOTER -->
                <footer class="border-t border-slate-200/80 bg-white px-4 sm:px-8 py-4 text-xs text-slate-500 mt-auto">
                    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-800">{{ tenant?.name ?? 'Absensi Bimbel Enterprise' }}</span>
                            <span>&bull;</span>
                            <span>&copy; 2026 Hak Cipta Dilindungi</span>
                        </div>
                        <div class="flex items-center gap-4 text-slate-400 font-medium">
                            <span>v1.0.0</span>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Custom subtle scrollbar */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
</style>
