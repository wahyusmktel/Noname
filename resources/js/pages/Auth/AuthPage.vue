<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import {
    GraduationCap,
    Building2,
    User,
    Mail,
    Lock,
    Phone,
    MapPin,
    ArrowRight,
    ArrowLeft,
    CheckCircle2,
    Sparkles,
    Eye,
    EyeOff,
    ShieldCheck,
    Loader2,
    Users,
    Check,
    BookOpen,
    HelpCircle,
    Zap,
    KeyRound,
} from 'lucide-vue-next';
import { useNotification } from '@/composables/useNotification';

interface Props {
    initialTab?: 'login' | 'register';
    errors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    initialTab: 'login',
});

const page = usePage();
const { toast } = useNotification();

// Active tab switcher ('login' | 'register')
const activeTab = ref<'login' | 'register'>(props.initialTab);

// Password visibility toggles
const showPassword = ref(false);
const showRegisterPassword = ref(false);
const showRegisterPasswordConfirm = ref(false);

// Wizard Step state (1, 2, 3)
const currentStep = ref(1);

// LOGIN FORM STATE
const loginForm = useForm({
    email: '',
    password: '',
    remember: true,
});

const submitLogin = () => {
    loginForm.post('/login', {
        onSuccess: () => {
            toast('Login berhasil! Mengalihkan ke dashboard...', 'success');
        },
        onError: (err) => {
            const firstError = Object.values(err)[0];
            if (firstError) {
                toast(firstError, 'error');
            }
        },
    });
};

// Autofill demo account for instant evaluation
const fillDemoAccount = () => {
    loginForm.email = 'admin@bintangprestasi.com';
    loginForm.password = 'password123';
    toast('Kredensial akun demo berhasil dimasukkan!', 'info');
};

// REGISTRATION WIZARD FORM STATE
const registerForm = useForm({
    // Step 1: Lembaga Bimbel
    institution_name: '',
    slug: '',
    city: '',
    phone: '',
    address: '',

    // Step 2: Akun Administrator
    name: '',
    email: '',
    admin_phone: '',
    password: '',
    password_confirmation: '',

    // Step 3: Layanan & Paket
    package_type: 'trial',
    service_levels: ['SMA / UTBK', 'SMP'],
    estimated_students: '50-200 Siswa',
});

// Auto-generate slug from institution name
const handleNameChange = () => {
    if (!registerForm.slug || registerForm.slug === slugify(registerForm.institution_name.slice(0, -1))) {
        registerForm.slug = slugify(registerForm.institution_name);
    }
};

const slugify = (text: string) => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-');
};

// Step Validation
const step1Errors = reactive<Record<string, string>>({});
const step2Errors = reactive<Record<string, string>>({});

const validateStep1 = () => {
    step1Errors.institution_name = '';
    step1Errors.slug = '';
    step1Errors.city = '';
    step1Errors.phone = '';

    let isValid = true;
    if (!registerForm.institution_name.trim()) {
        step1Errors.institution_name = 'Nama lembaga bimbel wajib diisi.';
        isValid = false;
    }
    if (!registerForm.slug.trim()) {
        step1Errors.slug = 'ID / Subdomain lembaga wajib diisi.';
        isValid = false;
    }
    if (!registerForm.city.trim()) {
        step1Errors.city = 'Kota domisili wajib diisi.';
        isValid = false;
    }
    if (!registerForm.phone.trim()) {
        step1Errors.phone = 'Nomor telepon lembaga wajib diisi.';
        isValid = false;
    }

    return isValid;
};

const validateStep2 = () => {
    step2Errors.name = '';
    step2Errors.email = '';
    step2Errors.admin_phone = '';
    step2Errors.password = '';
    step2Errors.password_confirmation = '';

    let isValid = true;
    if (!registerForm.name.trim()) {
        step2Errors.name = 'Nama penanggung jawab wajib diisi.';
        isValid = false;
    }
    if (!registerForm.email.trim() || !registerForm.email.includes('@')) {
        step2Errors.email = 'Format email administrator tidak valid.';
        isValid = false;
    }
    if (!registerForm.admin_phone.trim()) {
        step2Errors.admin_phone = 'Nomor WhatsApp admin wajib diisi.';
        isValid = false;
    }
    if (!registerForm.password || registerForm.password.length < 8) {
        step2Errors.password = 'Kata sandi minimal 8 karakter.';
        isValid = false;
    }
    if (registerForm.password !== registerForm.password_confirmation) {
        step2Errors.password_confirmation = 'Konfirmasi kata sandi tidak cocok.';
        isValid = false;
    }

    return isValid;
};

// Step Navigation with subtle button loading simulation
const isStepLoading = ref(false);

const nextStep = () => {
    if (currentStep.value === 1) {
        if (!validateStep1()) return;
        isStepLoading.value = true;
        setTimeout(() => {
            isStepLoading.value = false;
            currentStep.value = 2;
        }, 300);
    } else if (currentStep.value === 2) {
        if (!validateStep2()) return;
        isStepLoading.value = true;
        setTimeout(() => {
            isStepLoading.value = false;
            currentStep.value = 3;
        }, 300);
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitRegister = () => {
    registerForm.post('/register', {
        onSuccess: () => {
            toast('Pendaftaran lembaga bimbel berhasil! Selamat datang.', 'success');
        },
        onError: (err) => {
            const firstError = Object.values(err)[0];
            if (firstError) {
                toast(firstError, 'error');
            }
        },
    });
};

// Available Package Cards
const packages = [
    {
        id: 'trial',
        name: 'Trial 14 Hari',
        price: 'Gratis',
        desc: 'Coba seluruh fitur presensi tanpa komitmen awal.',
        tag: 'Masa Uji Coba',
    },
    {
        id: 'pro',
        name: 'Paket Pro Bimbel',
        price: 'Populer',
        desc: 'Cocok untuk bimbel berkembang dengan banyak kelas & tutor.',
        tag: 'Rekomendasi',
        recommended: true,
    },
    {
        id: 'enterprise',
        name: 'Multi-Cabang',
        price: 'Kustom',
        desc: 'Dukungan ratusan cabang bimbel dan integrasi API lengkap.',
        tag: 'Skala Besar',
    },
];

// Available Educational Levels
const educationalLevels = [
    'SD / MI',
    'SMP / MTs',
    'SMA / MA / SMK',
    'Persiapan UTBK / SNBT',
    'Kedinasan & Poltek',
    'Bahasa Asing (TOEFL/IELTS)',
];

const toggleLevel = (level: string) => {
    const idx = registerForm.service_levels.indexOf(level);
    if (idx > -1) {
        registerForm.service_levels.splice(idx, 1);
    } else {
        registerForm.service_levels.push(level);
    }
};

onMounted(() => {
    const flash = (page.props as any).flash;
    if (flash?.success) toast(flash.success, 'success');
    if (flash?.error) toast(flash.error, 'error');
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-800 flex flex-col justify-between selection:bg-orange-500 selection:text-white">
        <Head :title="activeTab === 'login' ? 'Masuk ke Akun Bimbel' : 'Pendaftaran Lembaga Bimbel'" />

        <!-- ========================================================================= -->
        <!-- MODE 1: FULL PAGE LIGHT SOFT REDESIGN (DEFAULT LOGIN VIEW)                -->
        <!-- ========================================================================= -->
        <div v-if="activeTab === 'login'" class="flex-1 flex flex-col lg:flex-row min-h-screen">
            <!-- LEFT COLUMN: VISUAL BRAND SHOWCASE & AI HERO ILLUSTRATION (DESKTOP ONLY) -->
            <div class="hidden lg:flex lg:w-7/12 xl:w-3/5 bg-gradient-to-br from-amber-50/70 via-orange-50/40 to-slate-100/90 p-8 lg:p-14 flex-col justify-between relative border-r border-slate-200/70 overflow-hidden">
                <!-- Soft ambient glows -->
                <div class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 rounded-full bg-orange-200/35 blur-3xl -z-10"></div>
                <div class="pointer-events-none absolute -bottom-24 right-10 w-96 h-96 rounded-full bg-amber-200/30 blur-3xl -z-10"></div>

                <!-- Top Brand Header -->
                <div class="flex items-center gap-3.5">
                    <img
                        :src="($page.props as any).app_logo || '/images/logo_bnn.png'"
                        alt="Logo Bimbel"
                        class="h-12 w-auto object-contain rounded-2xl bg-white p-1 border border-slate-200/80 shadow-xs shrink-0"
                    />
                    <div>
                        <span class="text-lg font-black tracking-tight text-slate-900 flex items-center gap-1">
                            Bimbel No Name
                        </span>
                        <p class="text-xs font-semibold text-orange-600">Solusi Presensi & Monitoring Belajar Terpadu</p>
                    </div>
                </div>

                <!-- Center Content: AI Hero Image & Value Proposition -->
                <div class="my-auto py-8 max-w-2xl mx-auto w-full">
                    <!-- AI Hero Illustration Card -->
                    <div class="relative rounded-3xl overflow-hidden shadow-xl shadow-slate-200/80 border border-white/80 bg-white p-2 sm:p-2.5 transition-transform hover:scale-[1.008] duration-300">
                        <img
                            src="/images/bimbel_login_hero.jpg"
                            alt="Suasana Bimbingan Belajar Modern dan Interaktif"
                            class="w-full h-auto max-h-[380px] sm:max-h-[420px] object-cover rounded-2xl"
                        />
                        <!-- Subtle floating overlay badge -->
                        <div class="absolute bottom-5 left-5 right-5 sm:right-auto bg-white/95 backdrop-blur-md px-4 py-2.5 rounded-2xl border border-white/90 shadow-md flex items-center gap-2.5 text-xs font-semibold text-slate-800">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                            <span>Presensi Presisi & Monitoring Belajar Siswa</span>
                        </div>
                    </div>

                    <!-- Headline & Description (Replaces "multi tenant masa kini") -->
                    <div class="mt-8 space-y-2.5 text-left">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                            Sistem Presensi & Manajemen Bimbingan Belajar Terpadu
                        </h1>
                        <p class="text-sm text-slate-600 leading-relaxed max-w-xl">
                            Pencatatan kehadiran presisi, jurnal materi pertemuan, foto dokumentasi kelas, dan pemantauan perkembangan siswa secara real-time dalam satu platform.
                        </p>
                    </div>
                </div>

                <!-- Bottom Feature Badges -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-6 border-t border-slate-200/60">
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white/80 border border-white shadow-xs">
                        <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                        <span class="text-xs font-bold text-slate-700">Presensi & Foto Kelas</span>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white/80 border border-white shadow-xs">
                        <div class="h-8 w-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                            <Users class="h-4 w-4" />
                        </div>
                        <span class="text-xs font-bold text-slate-700">Portal Wali Murid</span>
                    </div>
                    <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white/80 border border-white shadow-xs">
                        <div class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                        <span class="text-xs font-bold text-slate-700">Keamanan Terenkripsi</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: LIGHT SOFT LOGIN FORM (FULL-WIDTH ON MOBILE, CLEAN & FOCUSED) -->
            <div class="w-full lg:w-5/12 xl:w-2/5 min-h-screen lg:min-h-0 bg-white flex flex-col justify-between p-5 sm:p-8 lg:p-12 relative shadow-sm">
                <!-- Mobile Brand Header (Visible ONLY on mobile screens - Clean & Compact) -->
                <div class="lg:hidden flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <img
                        :src="($page.props as any).app_logo || '/images/logo_bnn.png'"
                        alt="Logo Bimbel"
                        class="h-11 w-auto object-contain rounded-2xl bg-white p-1 border border-slate-200/80 shadow-xs shrink-0"
                    />
                    <div>
                        <span class="text-base font-black tracking-tight text-slate-900 block leading-tight">
                            Bimbel No Name
                        </span>
                        <p class="text-xs font-semibold text-orange-600 mt-0.5">Presensi & Monitoring Belajar</p>
                    </div>
                </div>

                <!-- Main Login Form Wrapper -->
                <div class="my-auto max-w-md w-full mx-auto space-y-5">
                    <!-- Title & Greeting -->
                    <div class="space-y-1.5 text-left">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Selamat Datang Kembali</h2>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Masukkan kredensial akun Anda untuk mengakses sistem presensi dan laporan belajar
                        </p>
                    </div>

                    <!-- Parent / Wali Murid Friendly Login Guide Card -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200/70 flex items-start gap-3 text-xs shadow-xs">
                        <div class="h-8 w-8 rounded-xl bg-amber-500/15 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                            <Users class="h-4 w-4" />
                        </div>
                        <div class="space-y-0.5">
                            <span class="font-bold text-amber-900 block text-[11px] sm:text-xs">Panduan Login Orang Tua / Siswa:</span>
                            <p class="text-amber-800/90 leading-relaxed text-[11px]">
                                Masukkan <strong>Nomor Induk Siswa (NIS)</strong> atau <strong>Email</strong> yang terdaftar, serta kata sandi yang telah dibagikan oleh pihak bimbel.
                            </p>
                        </div>
                    </div>

                    <!-- LOGIN FORM -->
                    <form @submit.prevent="submitLogin" class="space-y-4">
                        <!-- Email or Username Input -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">NIS Siswa atau Email</label>
                            <div class="relative">
                                <User class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="loginForm.email"
                                    type="text"
                                    required
                                    placeholder="NIS (contoh: 261001) atau email terdaftar"
                                    class="w-full h-12 pl-10 pr-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-sm text-slate-800 placeholder:text-slate-400 font-medium transition-all focus:outline-none"
                                />
                            </div>
                            <p v-if="loginForm.errors.email" class="text-[11px] text-rose-500 font-medium mt-1">
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700">Kata Sandi</label>
                            </div>
                            <div class="relative">
                                <Lock class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="loginForm.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full h-12 pl-10 pr-11 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-sm text-slate-800 placeholder:text-slate-400 font-medium transition-all focus:outline-none"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-2 top-2 h-8 w-8 flex items-center justify-center text-slate-400 hover:text-slate-600 rounded-lg cursor-pointer"
                                >
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="loginForm.errors.password" class="text-[11px] text-rose-500 font-medium mt-1">
                                {{ loginForm.errors.password }}
                            </p>
                        </div>

                        <!-- Remember Me Checkbox -->
                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-600 select-none py-1">
                                <input
                                    type="checkbox"
                                    v-model="loginForm.remember"
                                    class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500/20 focus:ring-offset-0"
                                />
                                <span class="font-medium">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <!-- Submit Button with Loading Animation -->
                        <button
                            type="submit"
                            :disabled="loginForm.processing"
                            class="w-full h-12 sm:h-12.5 rounded-2xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-[0.99] text-white font-bold text-sm sm:text-base shadow-lg shadow-orange-500/25 flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:pointer-events-none mt-2 cursor-pointer"
                        >
                            <Loader2 v-if="loginForm.processing" class="h-4 w-4 animate-spin" />
                            <span v-if="loginForm.processing">Memverifikasi Akun...</span>
                            <span v-else class="flex items-center gap-2">
                                <span>Masuk ke Akun</span>
                                <ArrowRight class="h-4 w-4" />
                            </span>
                        </button>

                        <!-- Help Assistance Link for Parents -->
                        <div class="pt-2 text-center">
                            <a
                                href="https://wa.me/6281234567890?text=Halo%20Admin%20Bimbel,%20saya%20wali%20murid%20ingin%20menanyakan%20akun%20login%20ananda"
                                target="_blank"
                                class="inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-orange-600 transition-colors py-1.5 px-3 rounded-xl hover:bg-orange-50 cursor-pointer"
                            >
                                <Phone class="h-3.5 w-3.5 text-emerald-600" />
                                <span>Lupa NIS atau kata sandi? Hubungi Admin Bimbel</span>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- FOOTER / COPYRIGHT BIMBEL NO NAME -->
                <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                    <p>&copy; {{ new Date().getFullYear() }} Bimbel No Name. Seluruh hak cipta dilindungi.</p>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODE 2: WIZARD REGISTRATION (KEPT FOR ROUTE / DIRECT URL ACCESS)          -->
        <!-- ========================================================================= -->
        <div v-else class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8 bg-slate-50">
            <div class="w-full max-w-3xl bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl relative">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-400 flex items-center justify-center text-white font-bold">
                            <GraduationCap class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Pendaftaran Lembaga Bimbel</h2>
                            <p class="text-xs text-slate-500">Lengkapi formulir pendaftaran lembaga bimbingan belajar</p>
                        </div>
                    </div>
                    <button
                        @click="activeTab = 'login'"
                        class="text-xs font-semibold text-orange-600 hover:text-orange-700 cursor-pointer"
                    >
                        Kembali ke Login
                    </button>
                </div>

                <!-- Wizard Steps Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <span class="font-bold text-orange-600 uppercase tracking-wider text-[11px]">
                            Langkah {{ currentStep }} dari 3:
                            <span class="text-slate-800">
                                {{ currentStep === 1 ? 'Identitas Lembaga Bimbel' : currentStep === 2 ? 'Akun Administrator Utama' : 'Pilihan Paket & Aktivasi' }}
                            </span>
                        </span>
                        <span class="text-slate-400 font-mono text-[11px]">{{ Math.round((currentStep / 3) * 100) }}% Selesai</span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-orange-500 to-amber-500 h-2 rounded-full transition-all duration-500 ease-out"
                            :style="{ width: `${(currentStep / 3) * 100}%` }"
                        ></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mt-4">
                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep >= 1 ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-slate-50 border-slate-200 text-slate-400'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="currentStep > 1 ? 'bg-orange-500 text-white' : 'bg-slate-200 text-slate-700'">
                                <Check v-if="currentStep > 1" class="h-3 w-3" />
                                <span v-else>1</span>
                            </span>
                            <span class="truncate">Profil Lembaga</span>
                        </div>

                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep >= 2 ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-slate-50 border-slate-200 text-slate-400'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="currentStep > 2 ? 'bg-orange-500 text-white' : 'bg-slate-200 text-slate-700'">
                                <Check v-if="currentStep > 2" class="h-3 w-3" />
                                <span v-else>2</span>
                            </span>
                            <span class="truncate">Akun Admin</span>
                        </div>

                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep === 3 ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-slate-50 border-slate-200 text-slate-400'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black bg-slate-200 text-slate-700">
                                3
                            </span>
                            <span class="truncate">Paket & Selesai</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitRegister">
                    <!-- STEP 1 -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700">Nama Lembaga Bimbel <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <Building2 class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                                    <input
                                        v-model="registerForm.institution_name"
                                        @input="handleNameChange"
                                        type="text"
                                        required
                                        placeholder="Contoh: Bimbel Bintang Cemerlang"
                                        class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                    />
                                </div>
                                <p v-if="step1Errors.institution_name" class="text-[11px] text-rose-500 font-medium">{{ step1Errors.institution_name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">ID Lembaga (Subdomain) <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.slug"
                                    type="text"
                                    required
                                    placeholder="bintang-cemerlang"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step1Errors.slug" class="text-[11px] text-rose-500 font-medium">{{ step1Errors.slug }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Kota Domisili <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.city"
                                    type="text"
                                    required
                                    placeholder="Contoh: Jakarta Selatan"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step1Errors.city" class="text-[11px] text-rose-500 font-medium">{{ step1Errors.city }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Nomor Telepon Lembaga <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.phone"
                                    type="text"
                                    required
                                    placeholder="081234567890"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step1Errors.phone" class="text-[11px] text-rose-500 font-medium">{{ step1Errors.phone }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Alamat Lengkap Kantor</label>
                                <input
                                    v-model="registerForm.address"
                                    type="text"
                                    placeholder="Jl. Pendidikan No. 123"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div v-if="currentStep === 2" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700">Nama Penanggung Jawab / Admin Utama <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Dr. Irwan Susanto, M.Pd"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step2Errors.name" class="text-[11px] text-rose-500 font-medium">{{ step2Errors.name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Email Administrator <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.email"
                                    type="email"
                                    required
                                    placeholder="admin@bimbelcemerlang.com"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step2Errors.email" class="text-[11px] text-rose-500 font-medium">{{ step2Errors.email }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">WhatsApp Admin <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="registerForm.admin_phone"
                                    type="text"
                                    required
                                    placeholder="081299887766"
                                    class="w-full h-11 px-4 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                />
                                <p v-if="step2Errors.admin_phone" class="text-[11px] text-rose-500 font-medium">{{ step2Errors.admin_phone }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Kata Sandi (Min 8 Karakter) <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input
                                        v-model="registerForm.password"
                                        :type="showRegisterPassword ? 'text' : 'password'"
                                        required
                                        placeholder="••••••••"
                                        class="w-full h-11 pl-4 pr-10 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                    />
                                    <button type="button" @click="showRegisterPassword = !showRegisterPassword" class="absolute right-3 top-3.5 text-slate-400">
                                        <Eye v-if="!showRegisterPassword" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="step2Errors.password" class="text-[11px] text-rose-500 font-medium">{{ step2Errors.password }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input
                                        v-model="registerForm.password_confirmation"
                                        :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                        required
                                        placeholder="••••••••"
                                        class="w-full h-11 pl-4 pr-10 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 text-xs sm:text-sm text-slate-800"
                                    />
                                    <button type="button" @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm" class="absolute right-3 top-3.5 text-slate-400">
                                        <Eye v-if="!showRegisterPasswordConfirm" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="step2Errors.password_confirmation" class="text-[11px] text-rose-500 font-medium">{{ step2Errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3 -->
                    <div v-if="currentStep === 3" class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Paket Layanan</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div
                                    v-for="pkg in packages"
                                    :key="pkg.id"
                                    @click="registerForm.package_type = pkg.id"
                                    class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                    :class="registerForm.package_type === pkg.id ? 'border-orange-500 bg-orange-50/50' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ pkg.tag }}</span>
                                        <h4 class="font-bold text-sm text-slate-900 mt-2">{{ pkg.name }}</h4>
                                        <p class="text-xs text-slate-500 mt-1">{{ pkg.desc }}</p>
                                    </div>
                                    <span class="font-black text-sm text-orange-600 mt-4">{{ pkg.price }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Jenjang Pendidikan yang Disediakan</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="level in educationalLevels"
                                    :key="level"
                                    type="button"
                                    @click="toggleLevel(level)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border"
                                    :class="registerForm.service_levels.includes(level) ? 'bg-orange-500 text-white border-orange-500' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                >
                                    {{ level }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CONTROLS -->
                    <div class="flex items-center justify-between pt-6 mt-6 border-t border-slate-200">
                        <button
                            v-if="currentStep > 1"
                            @click="prevStep"
                            type="button"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-all cursor-pointer"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </button>
                        <div v-else></div>

                        <button
                            v-if="currentStep < 3"
                            @click="nextStep"
                            type="button"
                            :disabled="isStepLoading"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold shadow-md shadow-orange-500/20 transition-all disabled:opacity-50 cursor-pointer"
                        >
                            <Loader2 v-if="isStepLoading" class="h-4 w-4 animate-spin" />
                            <span v-if="isStepLoading">Memvalidasi...</span>
                            <span v-else class="flex items-center gap-1.5">
                                <span>Lanjutkan</span>
                                <ArrowRight class="h-4 w-4" />
                            </span>
                        </button>

                        <button
                            v-else
                            type="submit"
                            :disabled="registerForm.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 text-white text-xs font-bold shadow-md shadow-orange-500/20 transition-all disabled:opacity-50 cursor-pointer"
                        >
                            <Loader2 v-if="registerForm.processing" class="h-4 w-4 animate-spin" />
                            <span v-if="registerForm.processing">Mendaftarkan Lembaga...</span>
                            <span v-else class="flex items-center gap-1.5">
                                <Sparkles class="h-4 w-4 text-amber-200" />
                                <span>Selesaikan Pendaftaran & Masuk</span>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="pt-6 mt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                    <p>&copy; {{ new Date().getFullYear() }} Bimbel No Name. Seluruh hak cipta dilindungi.</p>
                </div>
            </div>
        </div>
    </div>
</template>
