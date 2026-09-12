<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    Building2,
    MapPin,
    Phone,
    Mail,
    Globe,
    Clock,
    Sparkles,
    ShieldCheck,
    Save,
    RotateCcw,
    Loader2,
    CheckCircle2,
    Palette,
    FileText,
    Users,
    GraduationCap,
    School,
    Calendar,
    BadgeCheck,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Props {
    tenant: {
        id: string;
        name: string;
        slug: string;
        tagline?: string;
        description?: string;
        phone: string;
        whatsapp_sender?: string;
        email?: string;
        website?: string;
        address?: string;
        city: string;
        province?: string;
        postal_code?: string;
        operating_hours?: string;
        brand_color?: string;
        package_type: string;
        status: string;
        created_at: string;
    };
    stats: {
        total_students: number;
        total_tutors: number;
        total_classes: number;
        attendance_rate: number;
        joined_since: string;
    };
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

// Active tab for form organization
const activeTab = ref<'identity' | 'contact' | 'whatsapp'>('identity');

// Form setup with Inertia useForm
const form = useForm({
    name: props.tenant.name || '',
    tagline: props.tenant.tagline || 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time',
    description: props.tenant.description || 'Lembaga bimbingan belajar berkualitas yang memadukan pengajaran interaktif dengan sistem presensi real-time terintegrasi notifikasi WhatsApp ke orang tua.',
    phone: props.tenant.phone || '',
    whatsapp_sender: props.tenant.whatsapp_sender || props.tenant.phone || '',
    email: props.tenant.email || '',
    website: props.tenant.website || 'https://bimbelnoname.com',
    address: props.tenant.address || '',
    city: props.tenant.city || '',
    province: props.tenant.province || 'DKI Jakarta',
    postal_code: props.tenant.postal_code || '12340',
    operating_hours: props.tenant.operating_hours || 'Senin - Sabtu (08:00 - 20:00 WIB)',
    brand_color: props.tenant.brand_color || '#F97316',
});

// Color palettes for brand theme picker
const presetColors = [
    { name: 'Soft Modern Orange (Brand)', value: '#F97316' },
    { name: 'Amber Warm', value: '#F59E0B' },
    { name: 'Coral Rose', value: '#FB7185' },
    { name: 'Indigo Modern', value: '#6366F1' },
    { name: 'Emerald Fresh', value: '#10B981' },
];

// Save changes handler
const submit = async () => {
    form.put('/lembaga/profil', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Profil lembaga bimbel berhasil disimpan!', 'success');
        },
        onError: (err) => {
            const firstErr = Object.values(err)[0];
            if (firstErr) {
                toast(firstErr, 'error');
            }
        },
    });
};

// Reset form values
const resetForm = () => {
    form.reset();
    toast('Perubahan form telah dibatalkan.', 'info');
};
</script>

<template>
    <AuthenticatedLayout title="Profil Lembaga Bimbel">
        <Head :title="'Profil Lembaga - ' + tenant.name" />

        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- BREADCRUMB & PAGE HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-500 font-medium mb-1">
                        <span>Lembaga Bimbel</span>
                        <span>&bull;</span>
                        <span class="text-orange-600 font-semibold">Profil Lembaga</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Profil Lembaga Bimbel</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola informasi legalitas, identitas instansi, kontak resmi, dan gateway presensi.</p>
                </div>

                <!-- Status Pill -->
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Status: Lembaga Aktif
                    </span>
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-orange-50 text-orange-700 border border-orange-200 text-xs font-bold uppercase">
                        <Sparkles class="h-3 w-3" />
                        Paket {{ tenant.package_type }}
                    </span>
                </div>
            </div>

            <!-- HERO BRANDING CARD -->
            <div class="rounded-3xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15 relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 h-48 w-48 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-4">
                        <!-- Logo Avatar with Soft Orange Accent -->
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-white text-orange-600 flex items-center justify-center font-black shadow-xl shrink-0">
                            <GraduationCap class="h-9 w-9 sm:h-11 sm:w-11" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl sm:text-2xl font-black tracking-tight">{{ form.name }}</h2>
                                <BadgeCheck class="h-5 w-5 text-amber-200 shrink-0" />
                            </div>
                            <p class="text-xs sm:text-sm text-orange-100 font-medium">{{ form.tagline }}</p>
                            <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-orange-100/90 font-mono">
                                <span>ID: {{ tenant.slug }}.bimbel.id</span>
                                <span>&bull;</span>
                                <span>Domisili: {{ form.city }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Metrics Strip -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 bg-slate-950/20 backdrop-blur-md p-3 rounded-2xl border border-white/20 text-center">
                        <div class="px-2">
                            <span class="block text-lg sm:text-xl font-black text-white">{{ stats.total_students }}</span>
                            <span class="text-[10px] text-orange-100 font-semibold uppercase">Siswa</span>
                        </div>
                        <div class="px-2 border-x border-white/20">
                            <span class="block text-lg sm:text-xl font-black text-white">{{ stats.total_tutors }}</span>
                            <span class="text-[10px] text-orange-100 font-semibold uppercase">Tutor</span>
                        </div>
                        <div class="px-2">
                            <span class="block text-lg sm:text-xl font-black text-white">{{ stats.total_classes }}</span>
                            <span class="text-[10px] text-orange-100 font-semibold uppercase">Kelas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTROLLER -->
            <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
                <button
                    @click="activeTab = 'identity'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
                    :class="activeTab === 'identity' ? 'bg-orange-500 text-white shadow-sm shadow-orange-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <Building2 class="h-4 w-4" />
                    <span>Identitas & Visi Lembaga</span>
                </button>

                <button
                    @click="activeTab = 'contact'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
                    :class="activeTab === 'contact' ? 'bg-orange-500 text-white shadow-sm shadow-orange-500/25' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                >
                    <MapPin class="h-4 w-4" />
                    <span>Kontak & Lokasi Kantor</span>
                </button>
            </div>

            <!-- FORM WRAPPER -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- ======================================================== -->
                <!-- TAB 1: IDENTITAS & LEGALITAS LEMBAGA -->
                <!-- ======================================================== -->
                <div v-show="activeTab === 'identity'" class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-900">Identitas Utama Lembaga</h3>
                        <p class="text-xs text-slate-500">Nama dan slogan yang tampil pada aplikasi mobile siswa, landing page, dan cetakan laporan.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Nama Lembaga -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700">
                                Nama Resmi Lembaga Bimbel <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-semibold transition-all focus:outline-none"
                                placeholder="Contoh: Bimbel No Name"
                            />
                            <p v-if="form.errors.name" class="text-[11px] text-rose-500 font-semibold mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Tagline / Slogan -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700">Motto / Tagline Lembaga</label>
                            <input
                                v-model="form.tagline"
                                type="text"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-800 font-medium transition-all focus:outline-none"
                                placeholder="Contoh: Bimbingan Belajar Modern Berbasis Prestasi"
                            />
                        </div>

                        <!-- Deskripsi Singkat / Visi -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700">Profil & Visi Pembelajaran</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full p-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-800 font-medium transition-all focus:outline-none leading-relaxed"
                                placeholder="Tuliskan deskripsi singkat mengenai metode dan fokus bimbingan belajar..."
                            ></textarea>
                        </div>

                        <!-- Subdomain / Slug (Read-only system identifier) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">ID Subdomain Sistem</label>
                            <div class="flex items-center rounded-xl border border-slate-200 bg-slate-100 px-4 h-11 text-xs text-slate-600 font-mono select-all">
                                <span>{{ tenant.slug }}</span>
                                <span class="text-slate-400">.bimbel.id</span>
                            </div>
                            <span class="text-[10px] text-slate-400">ID unik lembaga untuk akses multi-tenant terisolasi.</span>
                        </div>

                        <!-- Brand Accent Color -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Warna Identitas Lembaga</label>
                            <div class="flex items-center gap-2 h-11">
                                <button
                                    v-for="color in presetColors"
                                    :key="color.value"
                                    type="button"
                                    @click="form.brand_color = color.value"
                                    class="h-7 w-7 rounded-full border-2 transition-transform"
                                    :class="form.brand_color === color.value ? 'scale-110 border-slate-800 shadow-md ring-2 ring-orange-500/30' : 'border-transparent hover:scale-105'"
                                    :style="{ backgroundColor: color.value }"
                                    :title="color.name"
                                ></button>
                                <span class="text-xs font-mono font-bold text-slate-600 ml-2">{{ form.brand_color }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 2: KONTAK & LOKASI KANTOR -->
                <!-- ======================================================== -->
                <div v-show="activeTab === 'contact'" class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-900">Kontak Resmi & Alamat Gedung</h3>
                        <p class="text-xs text-slate-500">Informasi kontak ini akan ditampilkan pada portal orang tua dan kop surat bukti presensi.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Telepon / WhatsApp Resmi -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Nomor Telepon / WhatsApp Kantor <span class="text-orange-500">*</span>
                            </label>
                            <div class="relative">
                                <Phone class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    required
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-semibold transition-all focus:outline-none"
                                    placeholder="0812-3456-7890"
                                />
                            </div>
                        </div>

                        <!-- Email Resmi -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Email Resmi Lembaga</label>
                            <div class="relative">
                                <Mail class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                    placeholder="info@namabimbel.com"
                                />
                            </div>
                        </div>

                        <!-- Website URL -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Website Resmi</label>
                            <div class="relative">
                                <Globe class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.website"
                                    type="text"
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                    placeholder="https://namabimbel.com"
                                />
                            </div>
                        </div>

                        <!-- Jam Operasional -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Jam Operasional Belajar</label>
                            <div class="relative">
                                <Clock class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="form.operating_hours"
                                    type="text"
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                    placeholder="Senin - Sabtu (08:00 - 20:00 WIB)"
                                />
                            </div>
                        </div>

                        <!-- Alamat Lengkap Gedung -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700">Alamat Lengkap Gedung Bimbel</label>
                            <input
                                v-model="form.address"
                                type="text"
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                placeholder="Jl. Pendidikan Utama No. 88, Lantai 1 & 2"
                            />
                        </div>

                        <!-- Kota -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Kota / Kabupaten <span class="text-orange-500">*</span>
                            </label>
                            <input
                                v-model="form.city"
                                type="text"
                                required
                                class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                placeholder="Jakarta Selatan"
                            />
                        </div>

                        <!-- Provinsi & Kode Pos -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Provinsi</label>
                                <input
                                    v-model="form.province"
                                    type="text"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                    placeholder="DKI Jakarta"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Kode Pos</label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-3 focus:ring-orange-500/15 text-xs text-slate-900 font-medium transition-all focus:outline-none"
                                    placeholder="12340"
                                />
                            </div>
                        </div>
                    </div>
                </div>


                <!-- SUBMIT ACTIONS BAR -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                    <button
                        @click="resetForm"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-600 transition-all active:scale-95"
                    >
                        <RotateCcw class="h-4 w-4" />
                        <span>Batalkan Perubahan</span>
                    </button>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        <span v-if="form.processing">Menyimpan Perubahan...</span>
                        <span v-else>Simpan Perubahan Profil</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
