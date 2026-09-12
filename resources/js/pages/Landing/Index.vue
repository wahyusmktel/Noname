<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    GraduationCap,
    QrCode,
    MessageSquare,
    Smartphone,
    Users,
    CheckCircle2,
    ShieldCheck,
    Clock,
    ArrowRight,
    ChevronRight,
    ChevronLeft,
    Sparkles,
    BookOpen,
    Menu,
    X,
    Building2,
    UserCheck,
    Check,
    Star,
    MapPin,
    Phone,
    Mail,
    Award,
    Sparkle,
    HeartHandshake,
} from 'lucide-vue-next';

interface Slide {
    id: number;
    badge: string;
    title: string;
    subtitle: string;
    image: string;
    primary_btn: string;
    primary_url: string;
    sec_btn: string;
    sec_url: string;
    accent: string;
}

interface Program {
    id: string;
    name: string;
    level: string;
    desc: string;
    sessions: string;
    tag: string;
    highlight?: boolean;
}

interface Props {
    bimbel: {
        name: string;
        tagline: string;
        phone: string;
        email: string;
        city: string;
        address: string;
        brand_color: string;
    };
    slides: Slide[];
    stats: {
        total_students: string;
        graduation_rate: string;
        tutors_count: string;
        notif_speed: string;
    };
    programs: Program[];
    user?: any;
}

const props = defineProps<Props>();

// Mobile Menu Drawer
const isMobileNavOpen = ref(false);

// SLIDER CONTROLLER
const currentSlide = ref(0);
const isPaused = ref(false);
let slideInterval: any = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % props.slides.length;
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + props.slides.length) % props.slides.length;
};

const goToSlide = (index: number) => {
    currentSlide.value = index;
};

const startAutoplay = () => {
    stopAutoplay();
    slideInterval = setInterval(() => {
        if (!isPaused.value) {
            nextSlide();
        }
    }, 6000);
};

const stopAutoplay = () => {
    if (slideInterval) clearInterval(slideInterval);
};

onMounted(() => {
    startAutoplay();
});

onUnmounted(() => {
    stopAutoplay();
});

// Fasilitas Unggulan Bimbel No Name
const facilities = [
    {
        icon: Building2,
        title: 'Ruang Kelas Nyaman & Ber-AC',
        desc: 'Suasana belajar kondusif dengan kapasitas siswa terbatas (maks. 12-15 siswa/kelas) agar tutor dapat membimbing secara personal.',
    },
    {
        icon: QrCode,
        title: 'Terminal Presensi QR Mandiri',
        desc: 'Siswa langsung tap kartu barcode saat datang di meja resepsionis dan sistem langsung mencatat waktu kehadiran secara real-time.',
    },
    {
        icon: BookOpen,
        title: 'Modul & Bank Soal Terkini',
        desc: 'Materi modul disusun khusus sesuai kurikulum merdeka dan pola soal UTBK SNBT terbaru dengan ribuan variasi latihan.',
    },
    {
        icon: HeartHandshake,
        title: 'Konsultasi PR & Akademik Gratis',
        desc: 'Siswa bebas berdiskusi dan berkonsultasi mengenai tugas sekolah di luar jam bimbingan bersama tutor jaga.',
    },
];

// Testimoni Siswa & Orang Tua Bimbel No Name
const testimonials = [
    {
        quote: 'Sangat tenang rasanya menyekolahkan anak di Bimbel No Name. Setiap jam 16.00 saat anak saya sampai di bimbel, WhatsApp saya langsung berbunyi konfirmasi bahwa ananda sudah tiba di kelas.',
        name: 'Ibu Ratna Dewi',
        role: 'Orang Tua Siswa Kelas 12 SMA',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop',
    },
    {
        quote: 'Guru-guru di Bimbel No Name sangat ramah dan penjelasannya mudah dipahami. Absensi tinggal scan kartu dan catatan materi selalu dibagikan ke orang tua secara transparan.',
        name: 'Dimas Arya Pratama',
        role: 'Alumni Bimbel No Name (Lolos Kedokteran UI)',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop',
    },
    {
        quote: 'Sebagai pengajar, sistem presensi di Bimbel No Name sangat memudahkan. Tidak perlu repot bawa lembaran absen, tinggal tap di smartphone dan fokus membimbing anak-anak.',
        name: 'Siti Nurhaliza, S.Pd.',
        role: 'Tutor Fisika & Matematika',
        avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=200&auto=format&fit=crop',
    },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased selection:bg-orange-500 selection:text-white flex flex-col">
        <Head :title="bimbel.name + ' - Bimbingan Belajar Modern Berbasis Presensi Real-Time'" />

        <!-- ========================================================================= -->
        <!-- 1. FULL-WIDTH TOP NAVIGATION BAR -->
        <!-- ========================================================================= -->
        <header class="sticky top-0 z-50 w-full border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-xl transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo: Bimbel No Name -->
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-orange-500 via-amber-400 to-orange-600 flex items-center justify-center text-white font-black shadow-lg shadow-orange-500/25 transition-transform group-hover:scale-105">
                        <GraduationCap class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-extrabold tracking-tight text-white">
                                {{ bimbel.name }}
                            </span>
                            <span class="hidden sm:inline-flex items-center gap-1 rounded-full bg-orange-500/10 border border-orange-500/20 px-2 py-0.5 text-[10px] font-bold text-orange-400">
                                <Sparkles class="h-2.5 w-2.5" />
                                Official
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium">{{ bimbel.tagline }}</p>
                    </div>
                </Link>

                <!-- Center Nav Links -->
                <nav class="hidden lg:flex items-center gap-7 text-xs font-semibold text-slate-300">
                    <a href="#beranda" class="hover:text-orange-400 transition-colors">Beranda</a>
                    <a href="#program-belajar" class="hover:text-orange-400 transition-colors">Program Belajar</a>
                    <a href="#fitur-presensi" class="hover:text-orange-400 transition-colors">Sistem Presensi & Ortu</a>
                    <a href="#fasilitas" class="hover:text-orange-400 transition-colors">Fasilitas & Tutor</a>
                    <a href="#testimoni" class="hover:text-orange-400 transition-colors">Testimoni</a>
                    <a href="#kontak" class="hover:text-orange-400 transition-colors">Kontak Kami</a>
                </nav>

                <!-- Right Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <template v-if="user">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:opacity-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all active:scale-95"
                        >
                            <span>Dashboard ({{ user.name }})</span>
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-900 transition-all border border-slate-800"
                        >
                            Portal Guru & Siswa (Login)
                        </Link>
                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 text-white font-bold text-xs shadow-lg shadow-orange-500/25 transition-all active:scale-95"
                        >
                            <Sparkles class="h-3.5 w-3.5 text-amber-200" />
                            <span>Daftar Siswa Baru</span>
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu Hamburger Button -->
                <button
                    @click="isMobileNavOpen = !isMobileNavOpen"
                    class="flex lg:hidden items-center justify-center h-10 w-10 rounded-xl text-slate-300 hover:text-white hover:bg-slate-900 focus:outline-none"
                    title="Menu Navigasi"
                >
                    <Menu v-if="!isMobileNavOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile Drawer -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div
                    v-if="isMobileNavOpen"
                    class="lg:hidden border-b border-slate-800 bg-slate-950/95 backdrop-blur-2xl px-6 py-6 space-y-4 shadow-2xl"
                >
                    <nav class="flex flex-col space-y-3 text-sm font-semibold text-slate-300">
                        <a @click="isMobileNavOpen = false" href="#beranda" class="hover:text-orange-400 py-1">Beranda</a>
                        <a @click="isMobileNavOpen = false" href="#program-belajar" class="hover:text-orange-400 py-1">Program Belajar</a>
                        <a @click="isMobileNavOpen = false" href="#fitur-presensi" class="hover:text-orange-400 py-1">Sistem Presensi & Ortu</a>
                        <a @click="isMobileNavOpen = false" href="#fasilitas" class="hover:text-orange-400 py-1">Fasilitas & Tutor</a>
                        <a @click="isMobileNavOpen = false" href="#testimoni" class="hover:text-orange-400 py-1">Testimoni</a>
                        <a @click="isMobileNavOpen = false" href="#kontak" class="hover:text-orange-400 py-1">Kontak Kami</a>
                    </nav>
                    <div class="pt-4 border-t border-slate-800 flex flex-col gap-2.5">
                        <template v-if="user">
                            <Link
                                href="/dashboard"
                                class="w-full py-3 rounded-xl bg-orange-500 text-white font-bold text-center text-xs"
                            >
                                Dashboard ({{ user.name }})
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="w-full py-2.5 rounded-xl border border-slate-800 text-slate-300 text-center text-xs font-semibold"
                            >
                                Portal Siswa & Tutor (Masuk)
                            </Link>
                            <Link
                                href="/register"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold text-center text-xs shadow-md shadow-orange-500/25"
                            >
                                Daftar Bimbingan Sekarang
                            </Link>
                        </template>
                    </div>
                </div>
            </transition>
        </header>

        <!-- ========================================================================= -->
        <!-- 2. FULL-PAGE SLIDER HERO SECTION (PROFIL BIMBEL NO NAME) -->
        <!-- ========================================================================= -->
        <section
            id="beranda"
            class="relative w-full min-h-[calc(100vh-5rem)] flex items-center overflow-hidden"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
        >
            <!-- Ambient Glow -->
            <div class="pointer-events-none absolute top-1/4 left-1/2 -translate-x-1/2 h-[600px] w-[900px] rounded-full bg-gradient-to-tr from-orange-600/25 via-amber-500/15 to-transparent blur-[140px] -z-10"></div>

            <!-- Slider Slides -->
            <div
                v-for="(slide, idx) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                :class="idx === currentSlide ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
            >
                <!-- Background Image (Nantinya diatur di admin settings Bimbel No Name) -->
                <div class="absolute inset-0">
                    <img
                        :src="slide.image"
                        :alt="slide.title"
                        class="w-full h-full object-cover object-center scale-105 transition-transform duration-10000 ease-out"
                        :class="idx === currentSlide ? 'scale-100' : 'scale-105'"
                    />
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/85 to-slate-950/50"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-slate-950/50"></div>
                    <div class="absolute inset-0 bg-orange-950/20 mix-blend-color"></div>
                </div>

                <!-- Slide Content -->
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full min-h-[calc(100vh-5rem)] flex items-center py-16">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center w-full">
                        <!-- Left Hero Copy (7 cols) -->
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Badge -->
                            <div class="inline-flex items-center gap-2 rounded-full bg-orange-500/20 border border-orange-500/40 px-4 py-1.5 text-xs font-bold text-orange-300 backdrop-blur-md shadow-sm">
                                <Sparkles class="h-3.5 w-3.5 text-amber-300" />
                                <span>{{ slide.badge }}</span>
                            </div>

                            <!-- Title Headline -->
                            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-[1.12]">
                                {{ slide.title }}
                            </h1>

                            <!-- Subtitle Description -->
                            <p class="text-sm sm:text-base lg:text-lg text-slate-300 max-w-2xl font-medium leading-relaxed">
                                {{ slide.subtitle }}
                            </p>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-4 pt-2">
                                <a
                                    :href="slide.primary_url"
                                    class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-95 text-white font-bold text-sm shadow-xl shadow-orange-500/30 transition-all"
                                >
                                    <span>{{ slide.primary_btn }}</span>
                                    <ArrowRight class="h-4 w-4" />
                                </a>
                                <a
                                    :href="slide.sec_url"
                                    class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-slate-900/80 hover:bg-slate-800 text-white font-semibold text-sm border border-slate-700/80 backdrop-blur-sm transition-all"
                                >
                                    <span>{{ slide.sec_btn }}</span>
                                </a>
                            </div>

                            <!-- Highlights -->
                            <div class="pt-6 flex flex-wrap items-center gap-6 text-xs text-slate-400 font-medium">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-400" />
                                    <span>Notifikasi Hadir WhatsApp ke Ortu</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                    <span>Kelas Maks. 12-15 Siswa</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-4 w-4 text-amber-400" />
                                    <span>Tutor Lulusan PTN Terkemuka</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Card Floating (5 cols) -->
                        <div class="hidden lg:block lg:col-span-5 relative">
                            <div class="rounded-3xl bg-slate-900/90 border border-slate-700/80 p-6 shadow-2xl backdrop-blur-xl space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                        <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                                        <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-[10px] font-mono text-orange-400 font-bold">{{ bimbel.name }}</span>
                                </div>

                                <!-- Slide Context Mockup -->
                                <div v-if="idx === 0" class="space-y-3">
                                    <div class="p-4 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-center space-y-2">
                                        <div class="h-14 w-14 mx-auto rounded-2xl bg-orange-500 text-white flex items-center justify-center font-bold shadow-md shadow-orange-500/25">
                                            <GraduationCap class="h-8 w-8" />
                                        </div>
                                        <h4 class="text-sm font-bold text-white">Bimbingan Belajar Berstandar Juara</h4>
                                        <p class="text-[11px] text-slate-300">Kurikulum Merdeka, Pemantapan UTBK & Pendampingan Tugas Sekolah</p>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 flex items-center justify-between text-xs">
                                        <span class="text-slate-400">Pilihan Program:</span>
                                        <span class="text-orange-400 font-bold">SD &bull; SMP &bull; SMA &bull; UTBK</span>
                                    </div>
                                </div>

                                <div v-else-if="idx === 1" class="space-y-3">
                                    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 space-y-2">
                                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                                            <MessageSquare class="h-4 w-4" />
                                            <span>WhatsApp Resmi Bimbel No Name</span>
                                        </div>
                                        <div class="rounded-xl bg-slate-950/90 p-3 text-[11px] text-slate-300 font-sans space-y-1 border border-slate-800">
                                            <p class="font-bold text-emerald-400">Yth. Orang Tua / Wali Murid,</p>
                                            <p>Ananda <strong>Dimas Arya</strong> telah hadir di kelas <strong>Intensif TPS</strong> pada pukul <strong>15:58 WIB</strong>.</p>
                                            <span class="text-[9px] text-slate-500 block text-right">Otomatis &bull; Bimbel No Name Terverifikasi</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="space-y-3">
                                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-amber-300">Pengajar & Tutor Profesional</span>
                                            <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-bold">Kelas Aktif</span>
                                        </div>
                                        <div class="rounded-xl bg-slate-950/90 p-3 text-xs space-y-1 border border-slate-800">
                                            <p class="font-bold text-white">Sesi Matematika Penalaran Aljabar</p>
                                            <p class="text-[11px] text-slate-400">Tutor: Amanda Putri, M.Ed.</p>
                                            <p class="text-[10px] text-emerald-400 font-semibold mt-1">100% Siswa Hadir & Tuntas Materi</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2 text-center text-[10px] text-slate-400 font-medium border-t border-slate-800">
                                    📍 {{ bimbel.address }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slider Arrows -->
            <button
                @click="prevSlide"
                class="absolute left-4 sm:left-8 top-1/2 -translate-y-1/2 z-20 h-12 w-12 rounded-2xl bg-slate-900/70 hover:bg-orange-500 active:scale-90 text-white flex items-center justify-center backdrop-blur-md border border-slate-700/60 shadow-lg transition-all"
                title="Slide Sebelumnya"
            >
                <ChevronLeft class="h-6 w-6" />
            </button>

            <button
                @click="nextSlide"
                class="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 z-20 h-12 w-12 rounded-2xl bg-slate-900/70 hover:bg-orange-500 active:scale-90 text-white flex items-center justify-center backdrop-blur-md border border-slate-700/60 shadow-lg transition-all"
                title="Slide Berikutnya"
            >
                <ChevronRight class="h-6 w-6" />
            </button>

            <!-- Slider Dots -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex items-center gap-3">
                <button
                    v-for="(slide, idx) in slides"
                    :key="'dot-' + slide.id"
                    @click="goToSlide(idx)"
                    class="h-2.5 rounded-full transition-all duration-300"
                    :class="idx === currentSlide ? 'w-10 bg-gradient-to-r from-orange-500 to-amber-400 shadow-md shadow-orange-500/50' : 'w-2.5 bg-slate-700 hover:bg-slate-500'"
                    :title="'Slide ' + (idx + 1)"
                ></button>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- STATS BANNER BIMBEL NO NAME -->
        <!-- ========================================================================= -->
        <section class="border-y border-slate-800 bg-slate-900/70 py-8 relative z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-x-0 md:divide-x divide-slate-800">
                    <div class="p-3">
                        <p class="text-2xl sm:text-4xl font-black text-white tracking-tight">{{ stats.total_students }}</p>
                        <p class="text-xs font-semibold text-orange-400 uppercase tracking-wider mt-1">Siswa Aktif Belajar</p>
                    </div>
                    <div class="p-3">
                        <p class="text-2xl sm:text-4xl font-black text-white tracking-tight">{{ stats.graduation_rate }}</p>
                        <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider mt-1">Lolos PTN & Sekolah Favorit</p>
                    </div>
                    <div class="p-3">
                        <p class="text-2xl sm:text-4xl font-black text-white tracking-tight">{{ stats.tutors_count }}</p>
                        <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mt-1">Pengajar Berdedikasi</p>
                    </div>
                    <div class="p-3">
                        <p class="text-2xl sm:text-4xl font-black text-white tracking-tight">{{ stats.notif_speed }}</p>
                        <p class="text-xs font-semibold text-sky-400 uppercase tracking-wider mt-1">Laporan Kehadiran Ortu</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 3. PRESENTASI FITUR UNGGULAN: PRESENSI REAL-TIME, ORTU, & TUTOR -->
        <!-- ========================================================================= -->
        <section id="fitur-presensi" class="py-20 sm:py-28 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
                <!-- Header -->
                <div class="text-center max-w-3xl mx-auto space-y-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-bold uppercase tracking-wider">
                        <Sparkles class="h-3.5 w-3.5" />
                        Teknologi Presensi & Pendampingan
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white">
                        Sistem Presensi Real-Time & Pemantauan Orang Tua
                    </h2>
                    <p class="text-sm sm:text-base text-slate-400 leading-relaxed font-medium">
                        Di Bimbel No Name, kami tidak hanya fokus pada materi pelajaran, tetapi juga memastikan kehadiran siswa terpantau secara transparan bagi keluarga.
                    </p>
                </div>

                <!-- PILAR 1: ABSENSI REALTIME -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-orange-500/10 border border-orange-500/30 text-orange-400 text-xs font-bold">
                            <QrCode class="h-4 w-4" />
                            <span>1. Absensi Real-Time & Cepat</span>
                        </div>
                        <h3 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            Siswa Tap Kartu QR Saat Tiba, Presensi Tercatat Seketika
                        </h3>
                        <p class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed">
                            Setiap siswa Bimbel No Name dibekali kartu presensi dengan QR Code unik. Begitu tiba di lobi bimbel, siswa cukup menempelkan kartu ke layar pemindai dan status kehadiran langsung tersimpan dalam hitungan milidetik.
                        </p>
                        <ul class="space-y-2.5 text-xs sm:text-sm text-slate-300">
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Pemindaian cepat tanpa antre (<0.3 detik per siswa).</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Mencegah titip absen dengan verifikasi kode dinamis.</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Merekam jam masuk dan jam kepulangan secara akurat.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 p-6 sm:p-8 shadow-2xl relative">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <span class="text-xs font-bold text-slate-200">Terminal Scanner Lobi Bimbel</span>
                                    <span class="text-[10px] text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded-full">Siap Digunakan</span>
                                </div>
                                <div class="h-44 rounded-2xl bg-slate-950 border border-slate-800 flex flex-col items-center justify-center p-4">
                                    <div class="h-16 w-16 rounded-2xl border-2 border-dashed border-orange-500/60 flex items-center justify-center">
                                        <QrCode class="h-8 w-8 text-orange-400" />
                                    </div>
                                    <p class="text-xs text-slate-300 font-semibold mt-3">Scan Kartu Siswa Bimbel No Name</p>
                                    <span class="text-[10px] text-emerald-400 font-mono">Kehadiran Berhasil Diverifikasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PILAR 2: PEMANTAUAN ORANG TUA VIA WHATSAPP -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-6 order-2 lg:order-1">
                        <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 p-6 sm:p-8 shadow-2xl relative">
                            <div class="rounded-2xl bg-slate-950 p-4 border border-slate-800 space-y-3">
                                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-900">
                                    <div class="h-8 w-8 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold text-xs">WA</div>
                                    <div>
                                        <p class="text-xs font-bold text-white">Notifikasi Resmi Bimbel No Name</p>
                                        <p class="text-[9px] text-emerald-400 font-medium">Terverifikasi WhatsApp Bisnis</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-emerald-950/40 border border-emerald-800/40 p-3.5 space-y-2 text-xs text-slate-200">
                                    <p class="font-bold text-emerald-300">Yth. Bapak/Ibu Wali Murid,</p>
                                    <p class="leading-relaxed text-[11px]">
                                        Kami menginformasikan bahwa ananda <strong>Dimas Arya</strong> telah tiba di gedung Bimbel No Name dan sedang mengikuti sesi belajar <strong>Matematika Saintek</strong>.
                                    </p>
                                    <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-[10px] font-mono space-y-0.5">
                                        <p>&bull; Waktu Hadir: 15:58 WIB</p>
                                        <p>&bull; Tutor: Amanda Putri, M.Ed.</p>
                                        <p>&bull; Ruang: Ruang Belajar Einstein 1</p>
                                    </div>
                                    <span class="text-[9px] text-slate-500 block text-right">15:58 WIB &bull; Terkirim Langsung</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-6 space-y-6 order-1 lg:order-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold">
                            <MessageSquare class="h-4 w-4" />
                            <span>2. Ketenangan untuk Orang Tua</span>
                        </div>
                        <h3 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            Pemantauan Aktivitas & Notifikasi Langsung ke WhatsApp Orang Tua
                        </h3>
                        <p class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed">
                            Orang tua tidak perlu lagi merasa was-was apakah anaknya sudah sampai di tempat bimbel atau belum. Sistem kami secara otomatis mengirimkan kabar kehadiran ke nomor WhatsApp Anda tanpa biaya tambahan.
                        </p>
                        <ul class="space-y-2.5 text-xs sm:text-sm text-slate-300">
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-400" />
                                <span>Pesan WhatsApp otomatis tiba detik itu juga saat anak absen.</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-400" />
                                <span>Laporan rekapitulasi kehadiran bulanan dikirimkan ke orang tua.</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-amber-400" />
                                <span>Pemberitahuan dini jika siswa berhalangan atau terlambat.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- PILAR 3: MEMPERMUDAH GURU / TUTOR BIMBEL -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-orange-600/10 border border-orange-600/30 text-orange-400 text-xs font-bold">
                            <UserCheck class="h-4 w-4" />
                            <span>3. Kemudahan untuk Guru & Tutor</span>
                        </div>
                        <h3 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                            Mempermudah Guru Bimbel No Name Mengajar & Monitoring Kelas
                        </h3>
                        <p class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed">
                            Tutor di Bimbel No Name dapat fokus membimbing dan berdiskusi dengan siswa. Absensi kelas dapat dikelola dalam 1 klik lewat smartphone pengajar, lengkap dengan pengisian jurnal materi mengajar.
                        </p>
                        <ul class="space-y-2.5 text-xs sm:text-sm text-slate-300">
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Buka sesi belajar dan absensi kelas 1 sentuhan.</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Pencatatan jurnal materi mengajar digital yang rapi dan terarsip.</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <CheckCircle2 class="h-4 w-4 text-orange-400" />
                                <span>Kemudahan memantau siswa yang membutuhkan pendampingan khusus.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 p-6 sm:p-8 shadow-2xl relative">
                            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white">Kelas Bimbel No Name Hari Ini</span>
                                    <span class="text-[10px] bg-orange-500/20 text-orange-300 px-2 py-0.5 rounded-full font-bold">Sedang Berlangsung</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1">
                                    <p class="font-bold text-white">Kelas Intensif UTBK TPS A</p>
                                    <p class="text-[11px] text-slate-400">Pengajar: Dr. Aris Sudrajat, M.Si.</p>
                                    <p class="text-[10px] text-emerald-400 font-mono">Kehadiran: 23 dari 24 Siswa Hadir</p>
                                </div>
                                <div class="flex gap-2">
                                    <span class="px-3 py-1 rounded-lg bg-orange-500 text-white font-bold text-[10px]">Jurnal Tersimpan</span>
                                    <span class="px-3 py-1 rounded-lg bg-slate-800 text-slate-300 font-medium text-[10px]">Materi: Penalaran Matematika</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 4. PROGRAM BELAJAR DI BIMBEL NO NAME -->
        <!-- ========================================================================= -->
        <section id="program-belajar" class="py-20 bg-slate-900/60 border-y border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <span class="text-xs font-bold text-orange-400 uppercase tracking-wider">Jenjang Pembelajaran</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">Program Belajar di Bimbel No Name</h2>
                    <p class="text-xs sm:text-sm text-slate-400 font-medium">Pilihan kelas komprehensif yang dirancang untuk membimbing siswa dari dasar hingga jenjang perguruan tinggi.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="prog in programs"
                        :key="prog.id"
                        class="p-6 rounded-3xl bg-slate-950 border transition-all space-y-4 flex flex-col justify-between"
                        :class="prog.highlight ? 'border-orange-500/60 ring-2 ring-orange-500/20 shadow-xl shadow-orange-500/10' : 'border-slate-800 hover:border-slate-700'"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="prog.highlight ? 'bg-orange-500 text-white' : 'bg-slate-800 text-orange-400'">
                                    {{ prog.tag }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-400">{{ prog.level }}</span>
                            </div>
                            <h4 class="text-lg font-bold text-white">{{ prog.name }}</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">{{ prog.desc }}</p>
                        </div>
                        <div class="pt-3 border-t border-slate-900">
                            <span class="text-[11px] text-slate-500 font-medium block mb-3" v-html="prog.sessions"></span>
                            <a
                                href="https://wa.me/6281234567890?text=Halo%20Admin%20Bimbel%20No%20Name,%20saya%20tertarik%20dengan%20program%20belajar"
                                class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold transition-colors"
                                :class="prog.highlight ? 'bg-orange-500 hover:bg-orange-600 text-white' : 'bg-slate-900 hover:bg-slate-800 text-slate-200'"
                            >
                                <span>Daftar / Konsultasi</span>
                                <ArrowRight class="h-3.5 w-3.5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 5. FASILITAS UNGGULAN BIMBEL NO NAME -->
        <!-- ========================================================================= -->
        <section id="fasilitas" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="text-xs font-bold text-orange-400 uppercase tracking-wider">Kenyamanan Belajar</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">Fasilitas Belajar di Bimbel No Name</h2>
                <p class="text-xs sm:text-sm text-slate-400 font-medium">Setiap ruang belajar didesain agar siswa betah berdiskusi, fokus, dan meraih prestasi optimal.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(f, fIdx) in facilities"
                    :key="fIdx"
                    class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3"
                >
                    <div class="h-11 w-11 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center">
                        <component :is="f.icon" class="h-5 w-5" />
                    </div>
                    <h4 class="text-base font-bold text-white">{{ f.title }}</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">{{ f.desc }}</p>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 6. TESTIMONI -->
        <!-- ========================================================================= -->
        <section id="testimoni" class="py-16 bg-slate-900/40 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <span class="text-xs font-bold text-orange-400 uppercase tracking-wider">Kisah Pengalaman</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">Apa Kata Mereka Tentang Bimbel No Name?</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="(t, tIdx) in testimonials"
                        :key="tIdx"
                        class="p-6 rounded-3xl bg-slate-950/80 border border-slate-800 space-y-4 flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex text-amber-400 gap-1">
                                <Star v-for="s in 5" :key="s" class="h-4 w-4 fill-amber-400" />
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 italic leading-relaxed">
                                "{{ t.quote }}"
                            </p>
                        </div>
                        <div class="flex items-center gap-3 pt-3 border-t border-slate-900">
                            <img :src="t.avatar" :alt="t.name" class="h-10 w-10 rounded-full object-cover border border-slate-700" />
                            <div>
                                <h5 class="text-xs font-bold text-white">{{ t.name }}</h5>
                                <p class="text-[10px] text-slate-400">{{ t.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 7. KONTAK & LOKASI BIMBEL NO NAME -->
        <!-- ========================================================================= -->
        <section id="kontak" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center relative z-10">
                    <div class="space-y-4">
                        <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-bold tracking-wider uppercase">Pendaftaran Terbuka</span>
                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight">
                            Ingin Bergabung di Bimbel No Name?
                        </h2>
                        <p class="text-xs sm:text-sm text-orange-100 leading-relaxed font-medium">
                            Dapatkan free konsultasi penjurusan, tes pemetaan potensi akademik, dan rasakan kemudahan pantau kehadiran anak bersama kami.
                        </p>
                        <div class="pt-2 flex flex-wrap gap-3">
                            <a
                                href="https://wa.me/6281234567890?text=Halo%20Bimbel%20No%20Name,%20saya%20ingin%20mendaftar"
                                class="px-5 py-3 rounded-xl bg-white text-orange-600 font-bold text-xs shadow-md hover:bg-orange-50 transition-all"
                            >
                                Chat WhatsApp Kami
                            </a>
                            <Link
                                href="/login"
                                class="px-5 py-3 rounded-xl bg-orange-700/60 hover:bg-orange-700 font-semibold text-xs border border-white/20 transition-all"
                            >
                                Portal Akun Siswa & Tutor
                            </Link>
                        </div>
                    </div>

                    <div class="bg-slate-950/70 backdrop-blur-md rounded-2xl p-6 border border-white/20 space-y-3 text-xs">
                        <h4 class="font-bold text-sm text-white">Informasi Kontak Lembaga</h4>
                        <div class="space-y-2 text-slate-200">
                            <div class="flex items-start gap-2.5">
                                <MapPin class="h-4 w-4 text-orange-400 shrink-0 mt-0.5" />
                                <span>{{ bimbel.address }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Phone class="h-4 w-4 text-orange-400 shrink-0" />
                                <span>{{ bimbel.phone }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Mail class="h-4 w-4 text-orange-400 shrink-0" />
                                <span>{{ bimbel.email }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <Clock class="h-4 w-4 text-orange-400 shrink-0" />
                                <span>Senin - Sabtu: 08:00 - 20:00 WIB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================= -->
        <!-- 8. FOOTER LENGKAP -->
        <!-- ========================================================================= -->
        <footer class="border-t border-slate-800 bg-slate-950 pt-12 pb-8 text-xs text-slate-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold">
                                <GraduationCap class="h-5 w-5" />
                            </div>
                            <span class="text-base font-extrabold text-white">{{ bimbel.name }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Lembaga bimbingan belajar berkualitas dengan sistem kehadiran real-time dan notifikasi langsung ke orang tua siswa.
                        </p>
                    </div>

                    <div class="space-y-2.5">
                        <h5 class="text-xs font-bold text-white uppercase tracking-wider">Akses Cepat</h5>
                        <ul class="space-y-1.5 text-[11px] text-slate-400">
                            <li><a href="#program-belajar" class="hover:text-orange-400">Program Belajar</a></li>
                            <li><a href="#fitur-presensi" class="hover:text-orange-400">Sistem Presensi Siswa</a></li>
                            <li><a href="#fasilitas" class="hover:text-orange-400">Fasilitas Belajar</a></li>
                            <li><Link href="/login" class="hover:text-orange-400">Portal Login Guru & Siswa</Link></li>
                        </ul>
                    </div>

                    <div class="space-y-2.5">
                        <h5 class="text-xs font-bold text-white uppercase tracking-wider">Layanan Presensi</h5>
                        <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 space-y-1 text-[11px]">
                            <div class="flex items-center gap-2 text-emerald-400 font-semibold">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Sistem Presensi Aktif</span>
                            </div>
                            <p class="text-slate-500 text-[10px]">Terhubung langsung ke WhatsApp Orang Tua Siswa.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-900 text-center text-[11px] text-slate-500">
                    <p>&copy; 2026 {{ bimbel.name }} &bull; Hak Cipta Dilindungi &bull; Powered by Modern Attendance System</p>
                </div>
            </div>
        </footer>
    </div>
</template>
