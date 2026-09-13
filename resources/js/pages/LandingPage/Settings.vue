<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useNotification } from '@/composables/useNotification';
import {
    Globe,
    Layers,
    Sparkles,
    BookOpen,
    Users,
    GraduationCap,
    Save,
    RotateCcw,
    ExternalLink,
    UploadCloud,
    Image as ImageIcon,
    CheckCircle2,
    Info,
    ChevronRight,
    AlertCircle,
    Loader2,
} from 'lucide-vue-next';

interface HeroSlide {
    id: number;
    badge: string;
    title: string;
    subtitle: string;
    tag?: string;
    image: string;
}

interface QualityItem {
    id: number;
    badge: string;
    title: string;
    desc: string;
    image: string;
    points: string[];
}

interface CTAData {
    badge: string;
    title: string;
    desc: string;
    button_text: string;
    button_url: string;
    image: string;
    points: string[];
}

interface Props {
    settings: {
        id?: string;
        navbar_subtitle: string;
        hero_slides: HeroSlide[];
        quality_header: {
            badge: string;
            title: string;
            subtitle: string;
        };
        quality_items: QualityItem[];
        parent_cta: CTAData;
        tentor_cta: CTAData;
        contact_section?: {
            badge: string;
            tagline_override: string;
        };
    };
    tenant: {
        id: string;
        name: string;
        logo_url?: string;
        phone: string;
        phone_2?: string | null;
    };
}

const props = defineProps<Props>();
const { toast, confirmAction } = useNotification();

type ActiveTab = 'hero' | 'quality' | 'parent_cta' | 'tentor_cta' | 'general';
const activeTab = ref<ActiveTab>('hero');

// Inisialisasi Form Inertia
const form = useForm({
    navbar_subtitle: props.settings.navbar_subtitle || '',
    hero_slides: JSON.parse(JSON.stringify(props.settings.hero_slides || [])),
    hero_slide_images: [null, null, null] as (File | null)[],
    quality_header: JSON.parse(JSON.stringify(props.settings.quality_header || {
        badge: '',
        title: '',
        subtitle: '',
    })),
    quality_items: JSON.parse(JSON.stringify(props.settings.quality_items || [])),
    quality_item_images: [null, null, null] as (File | null)[],
    parent_cta: JSON.parse(JSON.stringify(props.settings.parent_cta || {
        badge: '',
        title: '',
        desc: '',
        button_text: '',
        button_url: '',
        image: '',
        points: ['', '', ''],
    })),
    parent_cta_image: null as File | null,
    tentor_cta: JSON.parse(JSON.stringify(props.settings.tentor_cta || {
        badge: '',
        title: '',
        desc: '',
        button_text: '',
        button_url: '',
        image: '',
        points: ['', '', ''],
    })),
    tentor_cta_image: null as File | null,
    contact_section: JSON.parse(JSON.stringify(props.settings.contact_section || {
        badge: 'Lembaga Bimbingan Belajar',
        tagline_override: '',
    })),
});

// Image Preview Object URLs
const heroSlidePreviews = ref<string[]>([
    props.settings.hero_slides[0]?.image || '',
    props.settings.hero_slides[1]?.image || '',
    props.settings.hero_slides[2]?.image || '',
]);

const qualityItemPreviews = ref<string[]>([
    props.settings.quality_items[0]?.image || '',
    props.settings.quality_items[1]?.image || '',
    props.settings.quality_items[2]?.image || '',
]);

const parentCtaPreview = ref<string>(props.settings.parent_cta?.image || '');
const tentorCtaPreview = ref<string>(props.settings.tentor_cta?.image || '');

// Handlers for File Selection
const handleHeroImageChange = (idx: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.hero_slide_images[idx] = file;
        heroSlidePreviews.value[idx] = URL.createObjectURL(file);
    }
};

const handleQualityImageChange = (idx: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.quality_item_images[idx] = file;
        qualityItemPreviews.value[idx] = URL.createObjectURL(file);
    }
};

const handleParentCtaImageChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.parent_cta_image = file;
        parentCtaPreview.value = URL.createObjectURL(file);
    }
};

const handleTentorCtaImageChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.tentor_cta_image = file;
        tentorCtaPreview.value = URL.createObjectURL(file);
    }
};

// Submit Simpan Perubahan
const submitForm = () => {
    form.post('/landing-page-settings', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.success('Perubahan konten landing page berhasil disimpan!');
        },
        onError: (errs) => {
            toast.error('Mohon periksa kembali formulir yang ditandai merah.');
            console.error('Save error:', errs);
        },
    });
};

// Reset Form ke Bawaan Sistem
const handleReset = async () => {
    const confirmed = await confirmAction({
        title: 'Kembalikan Konten ke Standar Bawaan?',
        text: 'Seluruh teks judul, deskripsi, dan gambar ilustrasi pada landing page akan dikembalikan ke format default bimbel.',
        confirmButtonText: 'Ya, Kembalikan ke Bawaan',
        cancelButtonText: 'Batal',
    });

    if (confirmed) {
        form.post('/landing-page-settings/reset', {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Konten landing page telah direset ke format standar.');
                window.location.reload();
            },
            onError: () => {
                toast.error('Gagal mereset konten landing page.');
            },
        });
    }
};
</script>

<template>
    <AuthenticatedLayout title="Kelola Konten Landing Page">
        <Head title="Kelola Konten Landing Page - Bimbel No Name" />

        <div class="max-w-7xl mx-auto space-y-6 pb-20">
            <!-- TOP BAR HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20 shrink-0">
                        <Globe class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-black text-slate-900">Kelola Landing Page</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                CMS Dinamis
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Atur seluruh teks, badge keunggulan, checklist manfaat, dan foto ilustrasi landing page secara fleksibel.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Preview & Reset -->
                <div class="flex items-center gap-2.5 self-end sm:self-auto">
                    <a
                        href="/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-all shadow-2xs"
                    >
                        <ExternalLink class="h-3.5 w-3.5 text-slate-400" />
                        <span>Lihat Landing Page</span>
                    </a>

                    <button
                        @click="handleReset"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-100 text-xs font-semibold text-rose-700 transition-all active:scale-95"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset Default</span>
                    </button>
                </div>
            </div>

            <!-- TABS NAVIGATION -->
            <div class="flex items-center gap-2 p-1.5 bg-slate-100/80 rounded-2xl border border-slate-200/80 overflow-x-auto">
                <button
                    @click="activeTab = 'hero'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                    :class="activeTab === 'hero' ? 'bg-white text-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'"
                >
                    <Layers class="h-4 w-4" />
                    <span>Hero Slider Utama (3 Slide)</span>
                </button>

                <button
                    @click="activeTab = 'quality'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                    :class="activeTab === 'quality' ? 'bg-white text-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'"
                >
                    <BookOpen class="h-4 w-4" />
                    <span>Standar Mutu Pembelajaran</span>
                </button>

                <button
                    @click="activeTab = 'parent_cta'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                    :class="activeTab === 'parent_cta' ? 'bg-white text-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'"
                >
                    <Users class="h-4 w-4" />
                    <span>Banner Portal Orang Tua</span>
                </button>

                <button
                    @click="activeTab = 'tentor_cta'"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                    :class="activeTab === 'tentor_cta' ? 'bg-white text-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'"
                >
                    <GraduationCap class="h-4 w-4" />
                    <span>Banner Portal Guru / Tentor</span>
                </button>
            </div>

            <!-- FORM CONTAINER -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- ========================================================================= -->
                <!-- TAB 1: HERO SLIDER UTAMA                                                  -->
                <!-- ========================================================================= -->
                <div v-show="activeTab === 'hero'" class="space-y-6">
                    <div class="bg-amber-50/70 border border-amber-200/70 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-800">
                        <Info class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Informasi Slider Berputar:</span>
                            Landing page menampilkan 3 slide otomatis di bagian paling atas. Anda dapat mengubah teks promosi, kalimat tagline, dan mengunggah gambar ilustrasi beresolusi tinggi (rekomendasi rasio 16:9 atau 4:3, maksimal 5MB).
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div
                            v-for="(slide, sIdx) in form.hero_slides"
                            :key="'slide-edit-' + slide.id"
                            class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-5 relative overflow-hidden"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-7 w-7 rounded-xl bg-orange-100 text-orange-700 font-black text-xs flex items-center justify-center">
                                        0{{ sIdx + 1 }}
                                    </span>
                                    <h3 class="font-bold text-sm text-slate-800">Slide Utama {{ sIdx + 1 }}</h3>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-400">Rasio 16:9 / 4:3</span>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                <!-- Form Fields (7 cols) -->
                                <div class="lg:col-span-7 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold text-slate-700">
                                                Badge Atas <span class="text-orange-500">*</span>
                                            </label>
                                            <input
                                                v-model="slide.badge"
                                                type="text"
                                                required
                                                class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium transition-all"
                                                placeholder="Contoh: Standar Pengajaran Unggul"
                                            />
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold text-slate-700">
                                                Tag Gambar (Overlay)
                                            </label>
                                            <input
                                                v-model="slide.tag"
                                                type="text"
                                                class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium transition-all"
                                                placeholder="Contoh: Tutor Berpengalaman"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">
                                            Judul Utama Slide <span class="text-orange-500">*</span>
                                        </label>
                                        <input
                                            v-model="slide.title"
                                            type="text"
                                            required
                                            class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-900 font-bold transition-all"
                                            placeholder="Contoh: Pendidikan Berkualitas dengan Pendampingan Tutor Berdedikasi"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">
                                            Subtitle / Penjelasan Singkat <span class="text-orange-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="slide.subtitle"
                                            rows="3"
                                            required
                                            class="w-full p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium transition-all"
                                            placeholder="Tuliskan deskripsi ringkas yang meyakinkan calon siswa atau orang tua..."
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- Image Upload & Preview (5 cols) -->
                                <div class="lg:col-span-5 space-y-3">
                                    <label class="block text-xs font-bold text-slate-700">Foto Ilustrasi Slide</label>
                                    <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 aspect-16/10 shadow-xs group">
                                        <img
                                            :src="heroSlidePreviews[sIdx] || slide.image"
                                            :alt="slide.title"
                                            class="w-full h-full object-cover"
                                        />
                                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <label :for="'hero-file-' + sIdx" class="cursor-pointer px-4 py-2 rounded-xl bg-white text-slate-800 text-xs font-bold shadow-lg hover:bg-orange-50 hover:text-orange-600 transition-colors flex items-center gap-1.5">
                                                <UploadCloud class="h-4 w-4 text-orange-500" />
                                                <span>Ganti Gambar</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                        <span>Ganti gambar jika ingin mengubah foto:</span>
                                        <label :for="'hero-file-' + sIdx" class="text-orange-600 font-bold hover:underline cursor-pointer">
                                            Pilih File...
                                        </label>
                                        <input
                                            :id="'hero-file-' + sIdx"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            class="hidden"
                                            @change="handleHeroImageChange(sIdx, $event)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 2: STANDAR MUTU PEMBELAJARAN                                          -->
                <!-- ========================================================================= -->
                <div v-show="activeTab === 'quality'" class="space-y-6">
                    <!-- Header Section Edit -->
                    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm text-slate-800">Kepala Seksi Standar Mutu</h3>
                            <span class="text-xs text-slate-400">Bagian Pengantar Mutu Belajar</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <div class="sm:col-span-4 space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Badge Seksi</label>
                                <input
                                    v-model="form.quality_header.badge"
                                    type="text"
                                    required
                                    class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                    placeholder="Standar & Dedikasi Kami"
                                />
                            </div>
                            <div class="sm:col-span-8 space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Judul Utama Seksi</label>
                                <input
                                    v-model="form.quality_header.title"
                                    type="text"
                                    required
                                    class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                    placeholder="Mengutamakan Mutu Pembelajaran & Karakter Siswa"
                                />
                            </div>
                            <div class="sm:col-span-12 space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Deskripsi Pengantar</label>
                                <textarea
                                    v-model="form.quality_header.subtitle"
                                    rows="2"
                                    required
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                    placeholder="Tuliskan komitmen mutu lembaga bimbel Anda..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Quality Cards Edit -->
                    <div class="grid grid-cols-1 gap-6">
                        <div
                            v-for="(item, qIdx) in form.quality_items"
                            :key="'quality-edit-' + item.id"
                            class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-5 relative"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-7 w-7 rounded-xl bg-amber-100 text-amber-700 font-black text-xs flex items-center justify-center">
                                        0{{ qIdx + 1 }}
                                    </span>
                                    <h3 class="font-bold text-sm text-slate-800">Pilar Keunggulan {{ qIdx + 1 }}</h3>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-400">Vertical Slider Card</span>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                <div class="lg:col-span-7 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold text-slate-700">Badge Kartu</label>
                                            <input
                                                v-model="item.badge"
                                                type="text"
                                                required
                                                class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                                placeholder="Contoh: Kurikulum Juara"
                                            />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-bold text-slate-700">Judul Pilar</label>
                                            <input
                                                v-model="item.title"
                                                type="text"
                                                required
                                                class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                                placeholder="Contoh: Kurikulum & Modul Terstruktur"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Penjelasan Ringkas</label>
                                        <textarea
                                            v-model="item.desc"
                                            rows="2"
                                            required
                                            class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                        ></textarea>
                                    </div>

                                    <!-- 3 Poin Checklist -->
                                    <div class="space-y-2 pt-2 border-t border-slate-100">
                                        <label class="block text-xs font-bold text-slate-700">3 Poin Manfaat / Rincian Checklist</label>
                                        <div class="space-y-2">
                                            <div
                                                v-for="(_, pIdx) in item.points"
                                                :key="pIdx"
                                                class="flex items-center gap-2"
                                            >
                                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                                <input
                                                    v-model="item.points[pIdx]"
                                                    type="text"
                                                    required
                                                    class="flex-1 h-9 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                                    :placeholder="'Poin keunggulan ' + (pIdx + 1)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Upload & Preview -->
                                <div class="lg:col-span-5 space-y-3">
                                    <label class="block text-xs font-bold text-slate-700">Foto Ilustrasi Mutu Belajar</label>
                                    <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 aspect-16/10 shadow-xs group">
                                        <img
                                            :src="qualityItemPreviews[qIdx] || item.image"
                                            :alt="item.title"
                                            class="w-full h-full object-cover"
                                        />
                                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <label :for="'quality-file-' + qIdx" class="cursor-pointer px-4 py-2 rounded-xl bg-white text-slate-800 text-xs font-bold shadow-lg hover:bg-orange-50 hover:text-orange-600 transition-colors flex items-center gap-1.5">
                                                <UploadCloud class="h-4 w-4 text-orange-500" />
                                                <span>Ganti Gambar</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                        <span>Ganti foto ilustrasi:</span>
                                        <label :for="'quality-file-' + qIdx" class="text-orange-600 font-bold hover:underline cursor-pointer">
                                            Pilih File...
                                        </label>
                                        <input
                                            :id="'quality-file-' + qIdx"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            class="hidden"
                                            @change="handleQualityImageChange(qIdx, $event)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 3: BANNER CTA PORTAL ORANG TUA                                        -->
                <!-- ========================================================================= -->
                <div v-show="activeTab === 'parent_cta'" class="space-y-6">
                    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-orange-500" />
                                <h3 class="font-bold text-sm text-slate-800">Banner Informasi Khusus Orang Tua / Wali Murid</h3>
                            </div>
                            <span class="text-xs text-slate-400">Seksi Ajakan Orang Tua</span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <div class="lg:col-span-7 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Badge Label</label>
                                        <input
                                            v-model="form.parent_cta.badge"
                                            type="text"
                                            required
                                            class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Teks Tombol Aksi</label>
                                        <input
                                            v-model="form.parent_cta.button_text"
                                            type="text"
                                            required
                                            class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Judul Banner Orang Tua</label>
                                    <input
                                        v-model="form.parent_cta.title"
                                        type="text"
                                        required
                                        class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Penjelasan & Ajakan</label>
                                    <textarea
                                        v-model="form.parent_cta.desc"
                                        rows="3"
                                        required
                                        class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                    ></textarea>
                                </div>

                                <!-- 3 Poin Manfaat -->
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <label class="block text-xs font-bold text-slate-700">3 Poin Manfaat untuk Orang Tua</label>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(_, pIdx) in form.parent_cta.points"
                                            :key="pIdx"
                                            class="flex items-center gap-2"
                                        >
                                            <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                            <input
                                                v-model="form.parent_cta.points[pIdx]"
                                                type="text"
                                                required
                                                class="flex-1 h-9 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                                :placeholder="'Poin manfaat ' + (pIdx + 1)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Image Upload & Preview -->
                            <div class="lg:col-span-5 space-y-3">
                                <label class="block text-xs font-bold text-slate-700">Foto Ilustrasi Banner Orang Tua</label>
                                <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 aspect-16/10 shadow-xs group">
                                    <img
                                        :src="parentCtaPreview || form.parent_cta.image"
                                        :alt="form.parent_cta.title"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <label for="parent-cta-file" class="cursor-pointer px-4 py-2 rounded-xl bg-white text-slate-800 text-xs font-bold shadow-lg hover:bg-orange-50 hover:text-orange-600 transition-colors flex items-center gap-1.5">
                                            <UploadCloud class="h-4 w-4 text-orange-500" />
                                            <span>Ganti Gambar</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-500">
                                    <span>Ganti foto ilustrasi:</span>
                                    <label for="parent-cta-file" class="text-orange-600 font-bold hover:underline cursor-pointer">
                                        Pilih File...
                                    </label>
                                    <input
                                        id="parent-cta-file"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        @change="handleParentCtaImageChange($event)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- TAB 4: BANNER CTA PORTAL TENTOR / GURU                                    -->
                <!-- ========================================================================= -->
                <div v-show="activeTab === 'tentor_cta'" class="space-y-6">
                    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <GraduationCap class="h-5 w-5 text-amber-600" />
                                <h3 class="font-bold text-sm text-slate-800">Banner Informasi Khusus Guru & Tentor Bimbel</h3>
                            </div>
                            <span class="text-xs text-slate-400">Seksi Ajakan Tentor</span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <div class="lg:col-span-7 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Badge Label</label>
                                        <input
                                            v-model="form.tentor_cta.badge"
                                            type="text"
                                            required
                                            class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">Teks Tombol Aksi</label>
                                        <input
                                            v-model="form.tentor_cta.button_text"
                                            type="text"
                                            required
                                            class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Judul Banner Guru</label>
                                    <input
                                        v-model="form.tentor_cta.title"
                                        type="text"
                                        required
                                        class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-bold"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Penjelasan & Ajakan</label>
                                    <textarea
                                        v-model="form.tentor_cta.desc"
                                        rows="3"
                                        required
                                        class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                    ></textarea>
                                </div>

                                <!-- 3 Poin Manfaat -->
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <label class="block text-xs font-bold text-slate-700">3 Poin Fitur untuk Guru / Tentor</label>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(_, pIdx) in form.tentor_cta.points"
                                            :key="pIdx"
                                            class="flex items-center gap-2"
                                        >
                                            <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                            <input
                                                v-model="form.tentor_cta.points[pIdx]"
                                                type="text"
                                                required
                                                class="flex-1 h-9 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15 text-xs text-slate-800 font-medium"
                                                :placeholder="'Poin fitur ' + (pIdx + 1)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Image Upload & Preview -->
                            <div class="lg:col-span-5 space-y-3">
                                <label class="block text-xs font-bold text-slate-700">Foto Ilustrasi Banner Tentor</label>
                                <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 aspect-16/10 shadow-xs group">
                                    <img
                                        :src="tentorCtaPreview || form.tentor_cta.image"
                                        :alt="form.tentor_cta.title"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <label for="tentor-cta-file" class="cursor-pointer px-4 py-2 rounded-xl bg-white text-slate-800 text-xs font-bold shadow-lg hover:bg-orange-50 hover:text-orange-600 transition-colors flex items-center gap-1.5">
                                            <UploadCloud class="h-4 w-4 text-orange-500" />
                                            <span>Ganti Gambar</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-500">
                                    <span>Ganti foto ilustrasi:</span>
                                    <label for="tentor-cta-file" class="text-orange-600 font-bold hover:underline cursor-pointer">
                                        Pilih File...
                                    </label>
                                    <input
                                        id="tentor-cta-file"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        @change="handleTentorCtaImageChange($event)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BAR (STICKY BOTTOM OR DESKTOP BAR) -->
                <div class="sticky bottom-4 z-40 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200/90 p-4 shadow-xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-500 hidden sm:flex">
                        <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                        <span>Perubahan yang disimpan langsung aktif di halaman depan web.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all disabled:opacity-50 disabled:pointer-events-none w-full sm:w-auto"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            <span v-if="form.processing">Menyimpan Perubahan...</span>
                            <span v-else>Simpan Perubahan Landing Page</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
