<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    HelpCircle,
    Search,
    BookOpen,
    ShieldCheck,
    GraduationCap,
    Users,
    ChevronDown,
    ChevronUp,
    Phone,
    Mail,
    Sparkles,
    CheckCircle2,
    MessageCircleQuestion,
    Building2,
    CalendarCheck,
    Camera,
    FileSpreadsheet,
    Smartphone
} from 'lucide-vue-next';

interface Section {
    id: string;
    title: string;
    steps: string[];
}

interface RoleGuide {
    role_label: string;
    description: string;
    sections: Section[];
}

interface FAQItem {
    question: string;
    answer: string;
}

interface TenantInfo {
    name: string;
    phone: string;
    email: string;
}

const props = defineProps<{
    userRole: string;
    guidesByRole: Record<string, RoleGuide>;
    faqs: FAQItem[];
    tenant: TenantInfo;
}>();

// Menentukan tab role default sesuai role pengguna login
const initialRole = computed(() => {
    if (props.userRole === 'tutor') return 'tutor';
    if (props.userRole === 'siswa' || props.userRole === 'orang_tua') return 'siswa';
    return 'admin_bimbel';
});

const selectedRoleTab = ref<string>(initialRole.value);
const searchQuery = ref<string>('');

// State FAQ accordion (index yang terbuka)
const openFaqIndex = ref<number | null>(null);
const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

// Filter panduan berdasarkan kata kunci pencarian
const currentGuide = computed(() => {
    return props.guidesByRole[selectedRoleTab.value] || props.guidesByRole['admin_bimbel'];
});

const filteredSections = computed(() => {
    if (!searchQuery.value.trim()) {
        return currentGuide.value.sections;
    }
    const q = searchQuery.value.toLowerCase();
    return currentGuide.value.sections.filter(sec => {
        const titleMatch = sec.title.toLowerCase().includes(q);
        const stepsMatch = sec.steps.some(step => step.toLowerCase().includes(q));
        return titleMatch || stepsMatch;
    });
});

const filteredFaqs = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.faqs;
    }
    const q = searchQuery.value.toLowerCase();
    return props.faqs.filter(f =>
        f.question.toLowerCase().includes(q) || f.answer.toLowerCase().includes(q)
    );
});
</script>

<template>
    <AuthenticatedLayout title="Pusat Bantuan & Panduan">
        <Head title="Pusat Bantuan & Panduan Penggunaan - Sistem Absensi Bimbel" />

        <div class="max-w-5xl mx-auto space-y-8 pb-16">
            <!-- Hero Banner Pusat Bantuan -->
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-orange-600 rounded-3xl p-6 sm:p-10 text-white shadow-xl shadow-orange-500/15">
                <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 max-w-2xl space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold">
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>Dokumentasi Resmi Sistem Absensi Bimbel</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-snug">
                        Pusat Bantuan & Panduan Penggunaan
                    </h1>
                    <p class="text-xs sm:text-sm text-orange-50/90 leading-relaxed">
                        Pelajari alur operasional, tata cara presensi, dan fitur monitoring sesuai peran Anda di lembaga bimbingan belajar.
                    </p>

                    <!-- Search Bar Cepat -->
                    <div class="pt-2">
                        <div class="relative max-w-lg">
                            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari panduan, kata kunci, atau pertanyaan..."
                                class="w-full h-11 pl-10 pr-4 text-xs bg-white text-slate-800 rounded-2xl shadow-md border-0 focus:outline-none focus:ring-2 focus:ring-white font-medium"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Selector Tabs (Pilihan Panduan Tiap Role) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-orange-600" />
                            <span>Pilih Panduan Berdasarkan Peran</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Panduan langkah demi langkah yang disesuaikan untuk setiap pengguna</p>
                    </div>
                </div>

                <!-- 3 Tombol Segmented Role Tabs (Grid 3 Kolom Responsif) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Tab 1: Admin Bimbel -->
                    <button
                        type="button"
                        @click="selectedRoleTab = 'admin_bimbel'"
                        class="p-4 rounded-2xl border text-left transition-all flex items-start gap-3.5 cursor-pointer"
                        :class="selectedRoleTab === 'admin_bimbel'
                            ? 'bg-orange-50/80 border-orange-400 ring-2 ring-orange-500/20 shadow-xs'
                            : 'bg-white border-slate-200/80 hover:bg-slate-50 text-slate-700'"
                    >
                        <div
                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 font-bold"
                            :class="selectedRoleTab === 'admin_bimbel' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-600'"
                        >
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="block text-xs font-black" :class="selectedRoleTab === 'admin_bimbel' ? 'text-orange-950' : 'text-slate-800'">
                                Admin Lembaga Bimbel
                            </span>
                            <span class="text-[11px] text-slate-500 mt-0.5 block leading-tight">
                                Master data, generate akun, rekapitulasi & ekspor laporan
                            </span>
                        </div>
                    </button>

                    <!-- Tab 2: Guru / Tentor -->
                    <button
                        type="button"
                        @click="selectedRoleTab = 'tutor'"
                        class="p-4 rounded-2xl border text-left transition-all flex items-start gap-3.5 cursor-pointer"
                        :class="selectedRoleTab === 'tutor'
                            ? 'bg-orange-50/80 border-orange-400 ring-2 ring-orange-500/20 shadow-xs'
                            : 'bg-white border-slate-200/80 hover:bg-slate-50 text-slate-700'"
                    >
                        <div
                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 font-bold"
                            :class="selectedRoleTab === 'tutor' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-600'"
                        >
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="block text-xs font-black" :class="selectedRoleTab === 'tutor' ? 'text-orange-950' : 'text-slate-800'">
                                Guru / Tentor Bimbel
                            </span>
                            <span class="text-[11px] text-slate-500 mt-0.5 block leading-tight">
                                Absensi tatap muka, foto wajib, & jurnal pembelajaran
                            </span>
                        </div>
                    </button>

                    <!-- Tab 3: Wali Murid / Siswa -->
                    <button
                        type="button"
                        @click="selectedRoleTab = 'siswa'"
                        class="p-4 rounded-2xl border text-left transition-all flex items-start gap-3.5 cursor-pointer"
                        :class="selectedRoleTab === 'siswa'
                            ? 'bg-orange-50/80 border-orange-400 ring-2 ring-orange-500/20 shadow-xs'
                            : 'bg-white border-slate-200/80 hover:bg-slate-50 text-slate-700'"
                    >
                        <div
                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 font-bold"
                            :class="selectedRoleTab === 'siswa' ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-600'"
                        >
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="block text-xs font-black" :class="selectedRoleTab === 'siswa' ? 'text-orange-950' : 'text-slate-800'">
                                Wali Murid / Siswa
                            </span>
                            <span class="text-[11px] text-slate-500 mt-0.5 block leading-tight">
                                Ruang pantau ananda, lencana apresiasi, & galeri foto kelas
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Konten Panduan Terpilih -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-orange-100 text-orange-700 px-2.5 py-0.5 rounded-full">
                            Panduan Resmi
                        </span>
                        <h3 class="text-lg font-black text-slate-900">{{ currentGuide.role_label }}</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ currentGuide.description }}</p>
                </div>

                <!-- Daftar Seksi Langkah-Langkah -->
                <div class="space-y-6">
                    <div
                        v-for="section in filteredSections"
                        :key="section.id"
                        class="p-5 rounded-2xl bg-slate-50/80 border border-slate-100 space-y-3"
                    >
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                            <span>{{ section.title }}</span>
                        </h4>

                        <div class="space-y-2 pl-6">
                            <div
                                v-for="(step, sIdx) in section.steps"
                                :key="sIdx"
                                class="flex items-start gap-2.5 text-xs text-slate-600 leading-relaxed"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-orange-500 shrink-0 mt-2"></span>
                                <span>{{ step }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredSections.length === 0" class="py-12 text-center text-slate-400 text-xs">
                        Tidak ada panduan yang cocok dengan kata kunci "{{ searchQuery }}".
                    </div>
                </div>
            </div>

            <!-- Bagian Tanya Jawab Umum (FAQ Accordion) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                        <MessageCircleQuestion class="h-5 w-5 text-orange-600" />
                        <span>Pertanyaan Sering Diajukan (FAQ)</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Jawaban cepat untuk pertanyaan teknis dan operasional yang sering ditanyakan</p>
                </div>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="(faq, fIdx) in filteredFaqs"
                        :key="fIdx"
                        class="py-3.5"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(fIdx)"
                            class="w-full flex items-center justify-between gap-4 text-left font-bold text-xs sm:text-sm text-slate-800 hover:text-orange-600 transition-colors cursor-pointer py-1"
                        >
                            <span>{{ faq.question }}</span>
                            <ChevronDown
                                class="h-4 w-4 text-slate-400 shrink-0 transition-transform duration-200"
                                :class="{ 'rotate-180 text-orange-600': openFaqIndex === fIdx }"
                            />
                        </button>

                        <div
                            v-if="openFaqIndex === fIdx"
                            class="pt-2 text-xs text-slate-600 leading-relaxed pl-1 animate-in fade-in"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Kontak Bantuan Lembaga -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 text-white rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="space-y-2 text-center sm:text-left">
                    <h3 class="text-base font-black">Butuh Bantuan Langsung dari Pengelola Bimbel?</h3>
                    <p class="text-xs text-slate-300 max-w-lg leading-relaxed">
                        Jika Anda mengalami kendala teknis atau pertanyaan administrasi, silakan hubungi tim layanan {{ tenant.name }}.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a
                        :href="`https://wa.me/${tenant.phone.replace(/[^0-9]/g, '')}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="h-11 px-5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <Phone class="h-4 w-4" />
                        <span>WhatsApp Bimbel</span>
                    </a>
                    <a
                        :href="`mailto:${tenant.email}`"
                        class="h-11 px-5 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/20 transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <Mail class="h-4 w-4" />
                        <span>Kirim Email</span>
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
