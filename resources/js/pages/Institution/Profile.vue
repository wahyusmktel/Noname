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
    ShieldCheck,
    Save,
    RotateCcw,
    Loader2,
    CheckCircle2,
    FileText,
    Users,
    GraduationCap,
    School,
    Calendar,
    BadgeCheck,
    Upload,
    Image,
    Trash2,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Props {
    tenant: {
        id: string;
        name: string;
        slug: string;
        tagline?: string;
        description?: string;
        logo?: string;
        logo_url?: string;
        phone: string;
        whatsapp_sender?: string;
        email?: string;
        website?: string;
        address?: string;
        city: string;
        province?: string;
        postal_code?: string;
        operating_hours?: string;
        package_type: string;
        status: string;
        created_at: string;
    };
    stats: {
        total_students: number;
        total_tutors: number;
        total_groups: number;
        attendance_rate: number;
        joined_since: string;
    };
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

// Active tab for form organization
const activeTab = ref<'identity' | 'contact'>('identity');

// Logo file ref & preview
const logoInputRef = ref<HTMLInputElement | null>(null);
const previewLogo = ref<string | null>(null);

// Form setup with Inertia useForm
const form = useForm({
    name: props.tenant.name || '',
    tagline: props.tenant.tagline || 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time',
    description: props.tenant.description || 'Lembaga bimbingan belajar berkualitas yang memadukan pengajaran interaktif dengan sistem presensi real-time terintegrasi notifikasi WhatsApp ke orang tua.',
    logo: null as File | null,
    remove_logo: false,
    phone: props.tenant.phone || '',
    whatsapp_sender: props.tenant.whatsapp_sender || props.tenant.phone || '',
    email: props.tenant.email || '',
    website: props.tenant.website || 'https://bimbelnoname.com',
    address: props.tenant.address || '',
    city: props.tenant.city || '',
    province: props.tenant.province || 'DKI Jakarta',
    postal_code: props.tenant.postal_code || '12340',
    operating_hours: props.tenant.operating_hours || 'Senin - Sabtu (08:00 - 20:00 WIB)',
});

const onFileSelect = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            toast('Ukuran berkas logo maksimal 5MB.', 'error');
            return;
        }
        form.logo = file;
        form.remove_logo = false;
        previewLogo.value = URL.createObjectURL(file);
        toast('Logo berhasil dipilih. Klik "Simpan Perubahan Profil" untuk menerapkan.', 'info');
    }
};

const triggerFileInput = () => {
    logoInputRef.value?.click();
};

const removeSelectedLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    previewLogo.value = '/images/logo_bnn.png';
    if (logoInputRef.value) {
        logoInputRef.value.value = '';
    }
    toast('Logo akan dikembalikan ke logo bawaan setelah disimpan.', 'info');
};

// Save changes handler (uses POST with forceFormData for multipart file upload)
const submit = () => {
    form.post('/lembaga/profil', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast('Profil dan logo lembaga bimbel berhasil diperbarui!', 'success');
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
    previewLogo.value = null;
    if (logoInputRef.value) {
        logoInputRef.value.value = '';
    }
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
                </div>
            </div>

            <!-- HERO BRANDING CARD -->
            <div class="rounded-3xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-6 sm:p-8 text-white shadow-lg shadow-orange-500/15 relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 h-48 w-48 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-4">
                        <!-- Logo Avatar with Soft Orange Accent -->
                        <img
                            :src="previewLogo || tenant.logo_url || '/images/logo_bnn.png'"
                            :alt="form.name"
                            class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-white p-1.5 object-contain shadow-xl shrink-0 border border-white/40"
                        />
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl sm:text-2xl font-black tracking-tight">{{ form.name }}</h2>
                                <BadgeCheck class="h-5 w-5 text-amber-200 shrink-0" />
                            </div>
                            <p class="text-xs sm:text-sm text-orange-100 font-medium">{{ form.tagline }}</p>
                            <div class="flex flex-wrap items-center gap-3 pt-1 text-xs text-orange-100/90 font-medium">
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
                            <span class="block text-lg sm:text-xl font-black text-white">{{ stats.total_groups }}</span>
                            <span class="text-[10px] text-orange-100 font-semibold uppercase">Kelompok</span>
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
                    <span>Identitas & Logo Lembaga</span>
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
                        <h3 class="text-base font-bold text-slate-900">Identitas & Logo Lembaga</h3>
                        <p class="text-xs text-slate-500">Kelola nama resmi, logo lembaga, moto, dan visi bimbingan belajar.</p>
                    </div>

                    <!-- LOGO LEMBAGA UPLOADER -->
                    <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        <div class="relative shrink-0">
                            <img
                                :src="previewLogo || tenant.logo_url || '/images/logo_bnn.png'"
                                :alt="form.name"
                                class="h-20 w-20 sm:h-24 sm:w-24 object-contain rounded-2xl bg-white p-2 border border-slate-200 shadow-sm"
                            />
                        </div>
                        <div class="space-y-2 flex-1">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Logo Resmi Lembaga Bimbel</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    Logo ini otomatis terpasang pada header aplikasi, halaman login, dashboard, serta halaman landing page. Format didukung: PNG, JPG, JPEG, SVG, WebP (Maksimal 5MB).
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <input
                                    ref="logoInputRef"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onFileSelect"
                                />
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-bold text-xs shadow-xs transition-all cursor-pointer"
                                >
                                    <Upload class="h-3.5 w-3.5" />
                                    <span>Pilih File Logo Baru</span>
                                </button>
                                <button
                                    v-if="tenant.logo || previewLogo"
                                    type="button"
                                    @click="removeSelectedLogo"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-slate-600 font-semibold text-xs transition-all cursor-pointer"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Kembalikan ke Default</span>
                                </button>
                            </div>
                            <p v-if="form.errors.logo" class="text-[11px] text-rose-500 font-semibold mt-1">{{ form.errors.logo }}</p>
                        </div>
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
