<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    GraduationCap,
    Sparkles,
    Users,
    CheckCircle2,
    Clock,
    ArrowRight,
    Phone,
    Mail,
    MapPin,
    Menu,
    X,
    BookOpen,
    CalendarCheck,
    Award,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    ShieldCheck,
    Compass,
    Smile,
    HeartHandshake,
    Instagram,
    Facebook,
    Youtube,
    Share2,
    ExternalLink,
} from 'lucide-vue-next';

interface Slide {
    id: number;
    badge: string;
    title: string;
    subtitle: string;
    image: string;
    tag?: string;
}

interface Props {
    bimbel: {
        name: string;
        tagline: string;
        phone: string;
        phone_2?: string | null;
        whatsapp_sender?: string | null;
        email: string;
        website?: string | null;
        city: string;
        address: string;
        operating_hours?: string | null;
        brand_color: string;
        logo_url?: string;
        tiktok_url?: string | null;
        instagram_url?: string | null;
        youtube_url?: string | null;
        facebook_url?: string | null;
    };
    slides?: Slide[];
    stats?: any;
    programs?: any[];
    user?: any;
}

const props = defineProps<Props>();

const formatWaUrl = (phone: string) => {
    const cleaned = (phone || '').replace(/[^0-9]/g, '');
    const formatted = cleaned.startsWith('0') ? '62' + cleaned.slice(1) : cleaned;
    return `https://wa.me/${formatted}`;
};

const formatSocialUrl = (url?: string | null) => {
    if (!url) return '';
    return url.startsWith('http://') || url.startsWith('https://') ? url : `https://${url}`;
};

const hasSocialMedia = computed(
    () => !!(props.bimbel.tiktok_url || props.bimbel.instagram_url || props.bimbel.youtube_url || props.bimbel.facebook_url)
);

// Mobile Menu Drawer state
const isMobileNavOpen = ref(false);

// Slider Data (3 Slides focused on Quality)
const heroSlides: Slide[] = props.slides && props.slides.length === 3 ? props.slides : [
    {
        id: 1,
        badge: 'Standar Pengajaran Unggul',
        title: 'Pendidikan Berkualitas dengan Pendampingan Tutor Berdedikasi',
        subtitle: 'Metode bimbingan belajar interaktif dan mendalam untuk membangun pemahaman konsep secara tuntas, bukan sekadar menghafal rumus.',
        image: '/images/slide_quality_tutor.jpg',
        tag: 'Tutor Berpengalaman',
    },
    {
        id: 2,
        badge: 'Fasilitas & Suasana Nyaman',
        title: 'Lingkungan Belajar Kondusif untuk Fokus Maksimal',
        subtitle: 'Kelas berukuran kecil didukung sarana belajar modern menciptakan suasana yang bersahabat dan memacu semangat belajar siswa.',
        image: '/images/slide_modern_class.jpg',
        tag: 'Kelas Kecil & Terarah',
    },
    {
        id: 3,
        badge: 'Evaluasi & Capaian Prestasi',
        title: 'Bimbingan Terarah Menuju Prestasi Akademik Terbaik',
        subtitle: 'Pemantauan perkembangan belajar yang terukur dan berkala membantu siswa meraih potensi terbaik dan percaya diri dalam menghadapi ujian.',
        image: '/images/slide_student_success.jpg',
        tag: 'Capaian Terukur',
    },
];

const currentSlide = ref(0);
let timer: any = null;

const startAutoSlide = () => {
    stopAutoSlide();
    timer = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % heroSlides.length;
    }, 6000);
};

const stopAutoSlide = () => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
};

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % heroSlides.length;
    startAutoSlide();
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + heroSlides.length) % heroSlides.length;
    startAutoSlide();
};

const goToSlide = (idx: number) => {
    currentSlide.value = idx;
    startAutoSlide();
};

// Data Vertical Slider Kualitas Pembelajaran (Standar & Dedikasi Kami)
const activeQualityIdx = ref(0);
let qualityTimer: any = null;

const qualitySlides = [
    {
        id: 1,
        title: 'Kurikulum & Modul Terstruktur',
        badge: 'Kurikulum Juara',
        desc: 'Materi pembelajaran disusun secara tematik dan sistematis mengacu pada kurikulum terbaru. Disertai latihan pemahaman konsep bertahap dari dasar hingga penguasaan soal penalaran kompleks.',
        icon: BookOpen,
        color: 'text-amber-600 bg-amber-50 border-amber-200/80',
        image: '/images/quality_curriculum.jpg',
        points: [
            'Modul belajar komprehensif & tersusun sistematis',
            'Metode penalaran konsep tanpa hafalan buta',
            'Rangkuman intisari materi di setiap bab bimbingan',
        ],
    },
    {
        id: 2,
        title: 'Kelas Kecil & Pendampingan Personal',
        badge: 'Kelas Kondusif',
        desc: 'Setiap ruang kelas dibatasi hanya untuk kelompok kecil (maksimal 12-15 siswa). Pengajar memiliki waktu dan ruang yang cukup untuk membimbing setiap anak sesuai kecepatan daya tangkapnya.',
        icon: Users,
        color: 'text-orange-600 bg-orange-50 border-orange-200/80',
        image: '/images/quality_mentoring.jpg',
        points: [
            'Bimbingan personal oleh tutor sabar dan komunikatif',
            'Suasana kelas yang hangat, bersahabat, dan saling mendukung',
            'Pendampingan intensif hingga siswa benar-benar paham',
        ],
    },
    {
        id: 3,
        title: 'Evaluasi Terarah & Pembinaan Karakter',
        badge: 'Evaluasi Berkala',
        desc: 'Kami memantau perkembangan akademik secara berkala dengan pendekatan yang menumbuhkan rasa percaya diri, kejujuran, dan ketekunan belajar siswa demi meraih prestasi terbaik.',
        icon: Award,
        color: 'text-emerald-600 bg-emerald-50 border-emerald-200/80',
        image: '/images/quality_evaluation.jpg',
        points: [
            'Uji pemahaman dan evaluasi materi secara berkala',
            'Apresiasi motivatif atas setiap peningkatan prestasi siswa',
            'Pencatatan rekam jejak kemajuan belajar yang objektif',
        ],
    },
];

const startQualityAuto = () => {
    stopQualityAuto();
    qualityTimer = setInterval(() => {
        activeQualityIdx.value = (activeQualityIdx.value + 1) % qualitySlides.length;
    }, 5000);
};

const stopQualityAuto = () => {
    if (qualityTimer) {
        clearInterval(qualityTimer);
        qualityTimer = null;
    }
};

const selectQuality = (idx: number) => {
    activeQualityIdx.value = idx;
    startQualityAuto();
};

onMounted(() => {
    startAutoSlide();
    startQualityAuto();
});

onUnmounted(() => {
    stopAutoSlide();
    stopQualityAuto();
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased selection:bg-orange-500 selection:text-white flex flex-col">
        <Head :title="bimbel.name + ' - Bimbingan Belajar Berkualitas & Terpercaya'" />

        <!-- ========================================================================= -->
        <!-- 1. TOP NAVBAR (LIGHT SOFT & CLEAN)                                        -->
        <!-- ========================================================================= -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
                <!-- Brand Logo: Bimbel No Name -->
                <Link href="/" class="flex items-center gap-3 group">
                    <img
                        :src="bimbel.logo_url || '/images/logo_bnn.png'"
                        :alt="bimbel.name"
                        class="h-10 w-10 sm:h-11 sm:w-11 object-contain rounded-2xl bg-white p-1 border border-slate-200/80 shadow-xs shrink-0 transition-transform group-hover:scale-105"
                    />
                    <div>
                        <span class="text-lg sm:text-xl font-black tracking-tight text-slate-900 block leading-tight">
                            {{ bimbel.name }}
                        </span>
                        <p class="text-[11px] font-semibold text-orange-600 hidden sm:block">Standar Kualitas Bimbingan Belajar Modern</p>
                    </div>
                </Link>

                <!-- Center Nav Links (Desktop) -->
                <nav class="hidden md:flex items-center gap-6 lg:gap-7 text-xs font-bold text-slate-600">
                    <a href="#hero-slider" class="hover:text-orange-600 transition-colors">Beranda</a>
                    <a href="#kualitas" class="hover:text-orange-600 transition-colors">Standar Kualitas</a>
                    <a href="#portal-orangtua" class="hover:text-orange-600 transition-colors">Portal Orang Tua</a>
                    <a href="#portal-tentor" class="hover:text-orange-600 transition-colors">Portal Tentor</a>
                    <a href="#kontak" class="hover:text-orange-600 transition-colors">Kontak Lembaga</a>
                </nav>

                <!-- Right Action Button -->
                <div class="hidden sm:flex items-center gap-3">
                    <Link
                        v-if="user"
                        href="/dashboard"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-xs shadow-md shadow-orange-500/20 hover:opacity-95 active:scale-95 transition-all"
                    >
                        <span>Dashboard ({{ user.name }})</span>
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        v-else
                        href="/login"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-xs shadow-md shadow-orange-500/20 hover:opacity-95 active:scale-95 transition-all"
                    >
                        <span>Masuk ke Portal</span>
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    @click="isMobileNavOpen = !isMobileNavOpen"
                    class="md:hidden flex items-center justify-center h-10 w-10 rounded-xl text-slate-600 hover:text-orange-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    title="Menu"
                >
                    <Menu v-if="!isMobileNavOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile Drawer Menu (Light Soft) -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="isMobileNavOpen"
                    class="md:hidden border-b border-slate-200 bg-white px-6 py-5 space-y-4 shadow-xl"
                >
                    <nav class="flex flex-col space-y-3 text-sm font-bold text-slate-700">
                        <a @click="isMobileNavOpen = false" href="#hero-slider" class="hover:text-orange-600 py-1">Beranda</a>
                        <a @click="isMobileNavOpen = false" href="#kualitas" class="hover:text-orange-600 py-1">Standar Kualitas</a>
                        <a @click="isMobileNavOpen = false" href="#portal-orangtua" class="hover:text-orange-600 py-1">Portal Orang Tua</a>
                        <a @click="isMobileNavOpen = false" href="#portal-tentor" class="hover:text-orange-600 py-1">Portal Tentor</a>
                        <a @click="isMobileNavOpen = false" href="#kontak" class="hover:text-orange-600 py-1">Kontak Lembaga</a>
                    </nav>
                    <div class="pt-3 border-t border-slate-100">
                        <Link
                            v-if="user"
                            href="/dashboard"
                            class="w-full py-3 rounded-xl bg-orange-500 text-white font-bold text-center text-xs flex items-center justify-center gap-2 shadow-sm"
                        >
                            <span>Dashboard ({{ user.name }})</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            v-else
                            href="/login"
                            class="w-full py-3 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-center text-xs flex items-center justify-center gap-2 shadow-md shadow-orange-500/25"
                        >
                            <span>Masuk ke Portal</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </transition>
        </header>

        <!-- ========================================================================= -->
        <!-- 2. HERO SLIDER SECTION (FULL PAGE / FULL VIEWPORT HEIGHT)                 -->
        <!-- ========================================================================= -->
        <section
            id="hero-slider"
            class="relative w-full min-h-[calc(100vh-4.5rem)] lg:h-[calc(100vh-4.5rem)] flex flex-col justify-between overflow-hidden bg-gradient-to-b from-white via-orange-50/20 to-slate-50 border-b border-slate-200/70 select-none"
            @mouseenter="stopAutoSlide"
            @mouseleave="startAutoSlide"
        >
            <!-- Ambient Background Glows -->
            <div class="absolute -top-32 -left-32 w-96 sm:w-128 h-96 sm:h-128 bg-orange-200/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/4 -right-32 w-96 sm:w-128 h-96 sm:h-128 bg-amber-200/20 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Slide Main Content (Flex-1 Centered Vertically) -->
            <div class="relative flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center py-8 lg:py-4 z-10">
                <div class="relative w-full h-full flex items-center">
                    <div
                        v-for="(slide, idx) in heroSlides"
                        :key="slide.id"
                        class="w-full transition-all duration-700 ease-in-out"
                        :class="currentSlide === idx ? 'opacity-100 translate-x-0 relative z-10' : 'opacity-0 absolute inset-0 pointer-events-none -translate-x-8'"
                    >
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 xl:gap-16 items-center">
                            <!-- Left Slide Content -->
                            <div class="lg:col-span-7 space-y-6 text-left order-2 lg:order-1">
                                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-50 border border-orange-200/80 text-orange-700 text-xs font-bold tracking-wide shadow-2xs">
                                    <Sparkles class="h-3.5 w-3.5 text-orange-500 shrink-0" />
                                    <span>{{ slide.badge }}</span>
                                </div>

                                <h1 class="text-3xl sm:text-5xl lg:text-5xl xl:text-6xl font-black text-slate-900 tracking-tight leading-[1.14]">
                                    {{ slide.title }}
                                </h1>

                                <p class="text-sm sm:text-base lg:text-lg text-slate-600 font-medium leading-relaxed max-w-2xl">
                                    {{ slide.subtitle }}
                                </p>

                                <!-- Slider Action Buttons -->
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 pt-2">
                                    <Link
                                        href="/login"
                                        class="inline-flex items-center justify-center gap-2.5 px-7 py-4 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-sm shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:opacity-95 active:scale-95 transition-all"
                                    >
                                        <span>Masuk ke Sistem Orang Tua</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>

                                    <a
                                        href="#portal-tentor"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 hover:text-orange-600 border border-slate-200 font-bold text-sm shadow-2xs transition-all"
                                    >
                                        <GraduationCap class="h-4 w-4 text-orange-500" />
                                        <span>Akses Guru / Tentor</span>
                                    </a>
                                </div>

                                <!-- Key Highlights -->
                                <div class="pt-3 flex flex-wrap items-center gap-5 text-xs font-semibold text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                        <span>Bimbingan Terarah & Personal</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 class="h-4 w-4 text-orange-500 shrink-0" />
                                        <span>Pemahaman Konsep Tuntas</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2 class="h-4 w-4 text-amber-500 shrink-0" />
                                        <span>Tutor Ahli Berdedikasi</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Slide Image Frame (AI 3D Image Full Height) -->
                            <div class="lg:col-span-5 order-1 lg:order-2">
                                <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 aspect-16/10 sm:aspect-16/9 lg:aspect-4/3 max-h-[380px] lg:max-h-[460px] w-full group">
                                    <img
                                        :src="slide.image"
                                        :alt="slide.title"
                                        class="w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                                        loading="eager"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/30 via-transparent to-transparent pointer-events-none"></div>
                                    <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/80 shadow-md text-xs font-bold text-slate-800 flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                                        <span>{{ slide.tag || 'Kualitas Teruji' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full-Width Bottom Bar Controls -->
            <div class="w-full bg-white/80 backdrop-blur-md border-t border-slate-200/80 py-4 px-4 sm:px-8 lg:px-12 z-20">
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                    <!-- Dots & Counter -->
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-mono font-bold text-slate-400">
                            <span class="text-orange-600 font-black">0{{ currentSlide + 1 }}</span> / 0{{ heroSlides.length }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="(slide, idx) in heroSlides"
                                :key="'dot-' + slide.id"
                                @click="goToSlide(idx)"
                                class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                                :class="currentSlide === idx ? 'w-8 bg-orange-500' : 'w-2.5 bg-slate-300 hover:bg-slate-400'"
                                :title="'Slide ' + (idx + 1)"
                            ></button>
                        </div>
                    </div>

                    <!-- Center: Scroll Down Prompt -->
                    <a
                        href="#kualitas"
                        class="hidden md:inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-orange-600 transition-colors"
                    >
                        <span>Standar Mutu Pembelajaran</span>
                        <ChevronDown class="h-4 w-4 animate-bounce text-orange-500" />
                    </a>

                    <!-- Prev/Next Navigation Buttons -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="prevSlide"
                            class="h-10 w-10 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50/50 shadow-xs flex items-center justify-center transition-all cursor-pointer"
                            title="Slide Sebelumnya"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button
                            @click="nextSlide"
                            class="h-10 w-10 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-orange-600 hover:border-orange-300 hover:bg-orange-50/50 shadow-xs flex items-center justify-center transition-all cursor-pointer"
                            title="Slide Berikutnya"
                        >
                            <ChevronRight class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 3. STANDAR KUALITAS PEMBELAJARAN (INTERACTIVE VERTICAL SLIDER)            -->
        <!-- ========================================================================= -->
        <section
            id="kualitas"
            class="py-16 sm:py-24 bg-white border-b border-slate-200/70"
            @mouseenter="stopQualityAuto"
            @mouseleave="startQualityAuto"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <!-- Section Header -->
                <div class="text-center max-w-3xl mx-auto space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-orange-600 bg-orange-50 px-3.5 py-1 rounded-full border border-orange-200/80 shadow-2xs">
                        Standar & Dedikasi Kami
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                        Mengutamakan Mutu Pembelajaran & Karakter Siswa
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Kami percaya bahwa prestasi berkelanjutan bermula dari proses belajar yang terarah, suasana kelas yang suportif, dan pendampingan oleh pengajar yang mengayomi.
                    </p>
                </div>

                <!-- Interactive Vertical Slider Container -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    <!-- Left: Vertical Card Selectors (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        <div
                            v-for="(item, idx) in qualitySlides"
                            :key="item.id"
                            @click="selectQuality(idx)"
                            class="p-5 sm:p-6 rounded-3xl border transition-all duration-300 cursor-pointer relative overflow-hidden text-left"
                            :class="activeQualityIdx === idx
                                ? 'bg-white border-orange-300 shadow-lg ring-2 ring-orange-500/15 translate-x-1 sm:translate-x-2'
                                : 'bg-slate-50/70 border-slate-200/80 hover:bg-white hover:border-slate-300 opacity-75 hover:opacity-100'"
                        >
                            <!-- Active Accent Bar -->
                            <div
                                v-if="activeQualityIdx === idx"
                                class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-orange-500 to-amber-500"
                            ></div>

                            <div class="flex items-start gap-4">
                                <div
                                    class="h-11 w-11 rounded-2xl flex items-center justify-center border shrink-0 transition-transform"
                                    :class="item.color"
                                >
                                    <component :is="item.icon" class="h-5 w-5" />
                                </div>
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3
                                            class="font-bold text-sm sm:text-base leading-snug"
                                            :class="activeQualityIdx === idx ? 'text-slate-900 font-extrabold' : 'text-slate-700'"
                                        >
                                            {{ item.title }}
                                        </h3>
                                        <span
                                            v-if="activeQualityIdx === idx"
                                            class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 shrink-0"
                                        >
                                            0{{ idx + 1 }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        {{ item.desc }}
                                    </p>

                                    <!-- Expanded Points on Active -->
                                    <transition
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                    >
                                        <div v-if="activeQualityIdx === idx" class="pt-3 border-t border-slate-100 space-y-1.5">
                                            <div
                                                v-for="(point, pIdx) in item.points"
                                                :key="pIdx"
                                                class="flex items-center gap-2 text-[11px] font-medium text-slate-600"
                                            >
                                                <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                                                <span>{{ point }}</span>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Dynamic AI Image Showcase (7 cols) -->
                    <div class="lg:col-span-7">
                        <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 aspect-16/10 sm:aspect-16/9 lg:aspect-4/3 max-h-[460px] w-full">
                            <div
                                v-for="(item, idx) in qualitySlides"
                                :key="'img-' + item.id"
                                class="absolute inset-0 transition-opacity duration-500 ease-in-out"
                                :class="activeQualityIdx === idx ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
                            >
                                <img
                                    :src="item.image"
                                    :alt="item.title"
                                    class="w-full h-full object-cover object-center"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent pointer-events-none"></div>

                                <!-- Floating Glass Overlay Info -->
                                <div class="absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur-md p-4 rounded-2xl border border-white/80 shadow-lg flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl flex items-center justify-center border shrink-0" :class="item.color">
                                            <component :is="item.icon" class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-orange-600 tracking-wider block">{{ item.badge }}</span>
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900">{{ item.title }}</h4>
                                        </div>
                                    </div>
                                    <span class="text-xs font-mono font-bold text-slate-400 shrink-0 hidden sm:block">
                                        Slide 0{{ idx + 1 }} / 0{{ qualitySlides.length }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 4. CTA KHUSUS ORANG TUA / WALI MURID (DENGAN GAMBAR AI)                   -->
        <!-- ========================================================================= -->
        <section id="portal-orangtua" class="py-16 sm:py-20 bg-gradient-to-b from-slate-50 to-orange-50/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-white border border-slate-200/90 shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 items-center">
                    <!-- Left: AI Image of Happy Parents checking child's progress -->
                    <div class="lg:col-span-6 p-6 sm:p-8 lg:p-10 order-2 lg:order-1">
                        <div class="relative rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-slate-100 aspect-16/9 lg:aspect-4/3 max-h-[380px] lg:max-h-[440px] w-full">
                            <img
                                src="/images/cta_parent_portal.jpg"
                                alt="Orang Tua Memantau Kemajuan Belajar Siswa di Bimbel"
                                class="w-full h-full object-cover object-center"
                                loading="lazy"
                            />
                            <!-- Overlay Badge -->
                            <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/80 shadow-sm text-xs font-bold text-slate-800 flex items-center gap-2">
                                <Users class="h-4 w-4 text-orange-500" />
                                <span>Kemudahan Monitoring Wali Murid</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Content & Direct Access Button -->
                    <div class="lg:col-span-6 p-6 sm:p-10 lg:p-12 space-y-6 order-1 lg:order-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-200/80 text-xs font-bold">
                            <ShieldCheck class="h-3.5 w-3.5 text-orange-500" />
                            <span>Khusus Orang Tua & Wali Murid</span>
                        </div>

                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                            Pantau Kehadiran & Kemajuan Belajar Ananda dengan Mudah
                        </h3>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Bimbel menyediakan portal monitoring khusus bagi Ayah dan Bunda. Gunakan akun siswa (NIS / Email) yang telah diberikan untuk memantau aktivitas belajar ananda secara transparan langsung dari genggaman Anda.
                        </p>

                        <!-- Benefit List -->
                        <div class="space-y-2.5 text-xs text-slate-600 font-medium">
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Melihat riwayat presensi kehadiran di setiap sesi kelas</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Membaca rangkuman jurnal materi dan catatan perkembangan dari guru</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Melihat foto dokumentasi kegiatan belajar anak di kelas</span>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <Link
                                href="/login"
                                class="inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-sm shadow-md shadow-orange-500/25 hover:opacity-95 active:scale-95 transition-all"
                            >
                                <span>Masuk ke Portal Orang Tua</span>
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 5. CTA KHUSUS GURU & TENTOR (PORTAL TENTOR BIMBEL)                        -->
        <!-- ========================================================================= -->
        <section id="portal-tentor" class="py-16 sm:py-20 bg-gradient-to-b from-orange-50/20 to-white border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-white border border-slate-200/90 shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 items-center">
                    <!-- Left: Content & Direct Access Button for Tutor -->
                    <div class="lg:col-span-6 p-6 sm:p-10 lg:p-12 space-y-6 order-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200/80 text-xs font-bold">
                            <GraduationCap class="h-3.5 w-3.5 text-amber-600" />
                            <span>Portal Khusus Guru & Tentor Bimbel</span>
                        </div>

                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                            Kelola Presensi & Dokumentasi Mengajar dengan Cepat
                        </h3>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Sistem presensi terpadu memudahkan Bapak dan Ibu guru mencatat kehadiran siswa di tiap sesi pertemuan, mengunggah foto dokumentasi kelas, dan mengisi jurnal materi bimbingan tanpa terbebani administrasi manual.
                        </p>

                        <!-- Benefit List for Teachers -->
                        <div class="space-y-2.5 text-xs text-slate-600 font-medium">
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Buka sesi presensi dan catat kehadiran siswa per kelas secara instan</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Unggah foto dokumentasi kelas langsung dari smartphone Anda</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
                                <span>Isi jurnal materi ajar dan catatan perkembangan belajar siswa</span>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <Link
                                href="/login"
                                class="inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white font-bold text-sm shadow-md shadow-orange-500/25 hover:opacity-95 active:scale-95 transition-all"
                            >
                                <span>Masuk ke Portal Tentor</span>
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <!-- Right: AI Image of smiling Indonesian Tutors with Tablet -->
                    <div class="lg:col-span-6 p-6 sm:p-8 lg:p-10 order-2">
                        <div class="relative rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-slate-100 aspect-16/9 lg:aspect-4/3 max-h-[380px] lg:max-h-[440px] w-full">
                            <img
                                src="/images/cta_tentor_portal.jpg"
                                alt="Tutor Bimbel Menggunakan Aplikasi Presensi dan Jurnal Belajar"
                                class="w-full h-full object-cover object-center"
                                loading="lazy"
                            />
                            <!-- Overlay Badge -->
                            <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/80 shadow-sm text-xs font-bold text-slate-800 flex items-center gap-2">
                                <GraduationCap class="h-4 w-4 text-amber-600" />
                                <span>Presensi & Jurnal Kelas Digital</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 6. KONTAK & INFORMASI LEMBAGA (SOFT LIGHT)                                -->
        <!-- ========================================================================= -->
        <section id="kontak" class="py-14 sm:py-16 bg-white border-t border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-slate-50 border border-slate-200/80 p-6 sm:p-10 shadow-xs grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7 space-y-3.5">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-orange-700 border border-orange-200/80 text-xs font-bold shadow-2xs">
                            <GraduationCap class="h-3.5 w-3.5 text-orange-500" />
                            <span>Lembaga Bimbingan Belajar</span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ bimbel.name }}
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-xl">
                            Berkomitmen memberikan standar pendidikan terbaik, lingkungan belajar yang ramah anak, dan transparansi informasi akademik bagi seluruh keluarga siswa.
                        </p>
                    </div>

                    <div class="lg:col-span-5 bg-white rounded-2xl p-5 border border-slate-200/80 space-y-4 text-xs">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-400 pb-2 border-b border-slate-100 flex items-center justify-between">
                            <span>Kontak Lembaga</span>
                            <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/60">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Buka
                            </span>
                        </h4>

                        <div class="space-y-2.5 text-slate-600">
                            <div class="flex items-start gap-2.5">
                                <MapPin class="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                                <span class="leading-relaxed">{{ bimbel.address }}</span>
                            </div>

                            <!-- Phone 1 (Utama) -->
                            <div class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <Phone class="h-4 w-4 text-orange-500 shrink-0" />
                                    <div class="truncate">
                                        <span class="font-semibold text-slate-700">{{ bimbel.phone }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1.5">(Utama)</span>
                                    </div>
                                </div>
                                <a
                                    :href="formatWaUrl(bimbel.phone)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors shrink-0"
                                >
                                    <span>WhatsApp</span>
                                </a>
                            </div>

                            <!-- Phone 2 (Tambahan / CS jika ada) -->
                            <div v-if="bimbel.phone_2" class="flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <Phone class="h-4 w-4 text-orange-500 shrink-0" />
                                    <div class="truncate">
                                        <span class="font-semibold text-slate-700">{{ bimbel.phone_2 }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1.5">(CS / Info)</span>
                                    </div>
                                </div>
                                <a
                                    :href="formatWaUrl(bimbel.phone_2)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors shrink-0"
                                >
                                    <span>WhatsApp</span>
                                </a>
                            </div>

                            <!-- Email -->
                            <div class="flex items-center gap-2.5">
                                <Mail class="h-4 w-4 text-orange-500 shrink-0" />
                                <a :href="'mailto:' + bimbel.email" class="hover:text-orange-600 transition-colors truncate">{{ bimbel.email }}</a>
                            </div>

                            <!-- Jam Operasional -->
                            <div class="flex items-center gap-2.5">
                                <Clock class="h-4 w-4 text-orange-500 shrink-0" />
                                <span>{{ bimbel.operating_hours || 'Senin - Sabtu: 08:00 - 20:00 WIB' }}</span>
                            </div>
                        </div>

                        <!-- Media Sosial Lembaga -->
                        <div v-if="hasSocialMedia" class="pt-3 border-t border-slate-100 space-y-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Media Sosial Kami</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    v-if="bimbel.instagram_url"
                                    :href="formatSocialUrl(bimbel.instagram_url)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-pink-50 hover:bg-pink-100 text-pink-700 border border-pink-200/80 font-medium text-xs transition-colors"
                                >
                                    <Instagram class="h-3.5 w-3.5 text-pink-600" />
                                    <span>Instagram</span>
                                </a>

                                <a
                                    v-if="bimbel.tiktok_url"
                                    :href="formatSocialUrl(bimbel.tiktok_url)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 font-medium text-xs transition-colors"
                                >
                                    <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/>
                                    </svg>
                                    <span>TikTok</span>
                                </a>

                                <a
                                    v-if="bimbel.youtube_url"
                                    :href="formatSocialUrl(bimbel.youtube_url)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200/80 font-medium text-xs transition-colors"
                                >
                                    <Youtube class="h-3.5 w-3.5 text-red-600" />
                                    <span>YouTube</span>
                                </a>

                                <a
                                    v-if="bimbel.facebook_url"
                                    :href="formatSocialUrl(bimbel.facebook_url)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 font-medium text-xs transition-colors"
                                >
                                    <Facebook class="h-3.5 w-3.5 text-blue-600" />
                                    <span>Facebook</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 7. FOOTER (CLEAN LIGHT THEME)                                             -->
        <!-- ========================================================================= -->
        <footer class="mt-auto border-t border-slate-200/80 bg-white py-8 text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <img
                        :src="bimbel.logo_url || '/images/logo_bnn.png'"
                        :alt="bimbel.name"
                        class="h-7 w-7 object-contain rounded-lg bg-white p-0.5 border border-slate-200/80 shadow-2xs shrink-0"
                    />
                    <span class="font-bold text-slate-800 text-sm">{{ bimbel.name }}</span>
                    <span class="text-slate-300 hidden sm:inline">&bull;</span>
                    <span class="hidden sm:inline text-[11px] text-slate-500">{{ bimbel.city }}</span>
                </div>

                <!-- Social Icons in Footer -->
                <div v-if="hasSocialMedia" class="flex items-center gap-3">
                    <a
                        v-if="bimbel.instagram_url"
                        :href="formatSocialUrl(bimbel.instagram_url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="h-8 w-8 rounded-full bg-slate-100 hover:bg-pink-50 hover:text-pink-600 text-slate-600 flex items-center justify-center transition-colors"
                        title="Instagram"
                    >
                        <Instagram class="h-4 w-4" />
                    </a>
                    <a
                        v-if="bimbel.tiktok_url"
                        :href="formatSocialUrl(bimbel.tiktok_url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="h-8 w-8 rounded-full bg-slate-100 hover:bg-slate-200 hover:text-slate-900 text-slate-600 flex items-center justify-center transition-colors"
                        title="TikTok"
                    >
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/>
                        </svg>
                    </a>
                    <a
                        v-if="bimbel.youtube_url"
                        :href="formatSocialUrl(bimbel.youtube_url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="h-8 w-8 rounded-full bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 flex items-center justify-center transition-colors"
                        title="YouTube"
                    >
                        <Youtube class="h-4 w-4" />
                    </a>
                    <a
                        v-if="bimbel.facebook_url"
                        :href="formatSocialUrl(bimbel.facebook_url)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="h-8 w-8 rounded-full bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-600 flex items-center justify-center transition-colors"
                        title="Facebook"
                    >
                        <Facebook class="h-4 w-4" />
                    </a>
                </div>

                <div class="text-[11px] text-slate-500 text-center sm:text-right">
                    &copy; 2026 {{ bimbel.name }}. Seluruh hak cipta dilindungi.
                </div>
            </div>
        </footer>
    </div>
</template>
