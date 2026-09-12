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
    <div class="min-h-screen bg-slate-950 font-sans text-slate-100 flex flex-col justify-between relative overflow-hidden selection:bg-orange-500 selection:text-white">
        <Head :title="activeTab === 'login' ? 'Masuk ke Akun Bimbel' : 'Pendaftaran Lembaga Bimbel Baru'" />

        <!-- AMBIENT GLOW EFFECTS (Soft Modern Orange & Amber) -->
        <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[800px] rounded-full bg-gradient-to-tr from-orange-600/25 via-amber-500/20 to-transparent blur-[120px] -z-10"></div>
        <div class="pointer-events-none absolute -bottom-40 -left-20 h-[450px] w-[500px] rounded-full bg-orange-700/15 blur-[100px] -z-10"></div>
        <div class="pointer-events-none absolute top-1/3 -right-20 h-[400px] w-[450px] rounded-full bg-amber-600/15 blur-[110px] -z-10"></div>

        <!-- HEADER NAVIGATION BAR -->
        <header class="w-full max-w-7xl mx-auto px-4 sm:px-8 py-5 flex items-center justify-between z-10">
            <!-- Brand Logo -->
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-400 flex items-center justify-center text-white font-black shadow-lg shadow-orange-500/30">
                    <GraduationCap class="h-6 w-6" />
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight text-white flex items-center gap-1.5">
                        AbsensiBimbel<span class="text-orange-400 font-normal">.id</span>
                    </span>
                    <p class="text-[10px] text-slate-400 font-medium">Sistem Presensi Multi-Tenant Masa Kini</p>
                </div>
            </div>

            <!-- Tab Mode Pill Switcher -->
            <div class="flex items-center bg-slate-900/90 p-1 rounded-2xl border border-slate-800 shadow-inner">
                <button
                    @click="activeTab = 'login'"
                    type="button"
                    class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                    :class="activeTab === 'login' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/25' : 'text-slate-400 hover:text-white'"
                >
                    Masuk Akun
                </button>
                <button
                    @click="activeTab = 'register'"
                    type="button"
                    class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                    :class="activeTab === 'register' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/25' : 'text-slate-400 hover:text-white'"
                >
                    Daftar Lembaga Baru
                </button>
            </div>
        </header>

        <!-- MAIN CONTAINER -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-center z-10">
            <!-- MODE 1: LOGIN CARD -->
            <div
                v-if="activeTab === 'login'"
                class="w-full max-w-md bg-slate-900/80 backdrop-blur-xl border border-slate-800/90 rounded-3xl p-6 sm:p-8 shadow-2xl relative transition-all duration-300"
            >
                <div class="text-center space-y-1 mb-6">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold mb-2">
                        <KeyRound class="h-3.5 w-3.5" />
                        <span>Portal Akses Lembaga</span>
                    </div>
                    <h2 class="text-2xl font-black tracking-tight text-white">Selamat Datang Kembali</h2>
                    <p class="text-xs text-slate-400">Masukkan akun administrator lembaga bimbel Anda</p>
                </div>

                <!-- Quick Demo Pill -->
                <div class="mb-5 p-3 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-between gap-2 text-xs">
                    <div class="space-y-0.5">
                        <span class="font-bold text-orange-300 block text-[11px]">Akun Demo Pengujian:</span>
                        <span class="text-slate-300 font-mono text-[11px]">admin@bintangprestasi.com</span>
                    </div>
                    <button
                        @click="fillDemoAccount"
                        type="button"
                        class="px-2.5 py-1 rounded-lg bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-bold text-[11px] shadow-sm transition-all"
                    >
                        Auto-Fill
                    </button>
                </div>

                <form @submit.prevent="submitLogin" class="space-y-4">
                    <!-- Email or Username Input -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-300">Email atau Username</label>
                        <div class="relative">
                            <User class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                            <input
                                v-model="loginForm.email"
                                type="text"
                                required
                                placeholder="email@bimbel.com atau username (261001)"
                                class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium transition-all focus:outline-none"
                            />
                        </div>
                        <p v-if="loginForm.errors.email" class="text-[11px] text-rose-400 font-medium mt-1">
                            {{ loginForm.errors.email }}
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-300">Kata Sandi</label>
                            <a href="#" class="text-[11px] text-orange-400 hover:text-orange-300 font-medium">Lupa sandi?</a>
                        </div>
                        <div class="relative">
                            <Lock class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                            <input
                                v-model="loginForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="••••••••"
                                class="w-full h-10.5 pl-10 pr-10 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium transition-all focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-3 text-slate-500 hover:text-slate-300"
                            >
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="loginForm.errors.password" class="text-[11px] text-rose-400 font-medium mt-1">
                            {{ loginForm.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-400 select-none">
                            <input
                                type="checkbox"
                                v-model="loginForm.remember"
                                class="h-4 w-4 rounded bg-slate-950 border-slate-700 text-orange-500 focus:ring-orange-500/20 focus:ring-offset-0"
                            />
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button with Loading Animation -->
                    <button
                        type="submit"
                        :disabled="loginForm.processing"
                        class="w-full h-11 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 active:scale-98 text-white font-bold text-xs shadow-lg shadow-orange-500/25 flex items-center justify-center gap-2 transition-all disabled:opacity-50 disabled:pointer-events-none mt-2"
                    >
                        <Loader2 v-if="loginForm.processing" class="h-4 w-4 animate-spin" />
                        <span v-if="loginForm.processing">Memverifikasi Kredensial...</span>
                        <span v-else class="flex items-center gap-2">
                            <span>Masuk ke Dashboard</span>
                            <ArrowRight class="h-4 w-4" />
                        </span>
                    </button>
                </form>

                <div class="mt-6 pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
                    Belum memiliki akun lembaga bimbel?
                    <button @click="activeTab = 'register'" class="font-bold text-orange-400 hover:text-orange-300 ml-1">
                        Daftar Lembaga Baru
                    </button>
                </div>
            </div>

            <!-- MODE 2: WIZARD REGISTRATION CARD (FULL EXPERIENCE) -->
            <div
                v-else
                class="w-full max-w-3xl bg-slate-900/85 backdrop-blur-xl border border-slate-800/90 rounded-3xl p-6 sm:p-10 shadow-2xl relative transition-all duration-300"
            >
                <!-- Wizard Steps Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-3 text-xs">
                        <span class="font-bold text-orange-400 uppercase tracking-wider text-[11px]">
                            Langkah {{ currentStep }} dari 3:
                            <span class="text-white">
                                {{ currentStep === 1 ? 'Identitas Lembaga Bimbel' : currentStep === 2 ? 'Akun Administrator Utama' : 'Pilihan Paket & Aktivasi' }}
                            </span>
                        </span>
                        <span class="text-slate-400 font-mono text-[11px]">{{ Math.round((currentStep / 3) * 100) }}% Selesai</span>
                    </div>

                    <!-- Progress Bar with soft modern orange gradient -->
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-orange-500 via-amber-400 to-orange-500 h-2 rounded-full transition-all duration-500 ease-out"
                            :style="{ width: `${(currentStep / 3) * 100}%` }"
                        ></div>
                    </div>

                    <!-- Step Pills -->
                    <div class="grid grid-cols-3 gap-2 mt-4">
                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep >= 1 ? 'bg-orange-500/10 border-orange-500/30 text-orange-300' : 'bg-slate-950/40 border-slate-800 text-slate-600'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="currentStep > 1 ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300'">
                                <Check v-if="currentStep > 1" class="h-3 w-3" />
                                <span v-else>1</span>
                            </span>
                            <span class="truncate">Profil Lembaga</span>
                        </div>

                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep >= 2 ? 'bg-orange-500/10 border-orange-500/30 text-orange-300' : 'bg-slate-950/40 border-slate-800 text-slate-600'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black" :class="currentStep > 2 ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300'">
                                <Check v-if="currentStep > 2" class="h-3 w-3" />
                                <span v-else>2</span>
                            </span>
                            <span class="truncate">Akun Admin</span>
                        </div>

                        <div
                            class="flex items-center gap-2 p-2 rounded-xl text-xs font-semibold border transition-all"
                            :class="currentStep === 3 ? 'bg-orange-500/10 border-orange-500/30 text-orange-300' : 'bg-slate-950/40 border-slate-800 text-slate-600'"
                        >
                            <span class="h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-black bg-slate-800 text-slate-300">
                                3
                            </span>
                            <span class="truncate">Paket & Selesai</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitRegister">
                    <!-- ============================================== -->
                    <!-- STEP 1: IDENTITAS LEMBAGA BIMBEL -->
                    <!-- ============================================== -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="space-y-1 mb-4">
                            <h3 class="text-lg font-bold text-white">Informasi Lembaga Bimbingan Belajar</h3>
                            <p class="text-xs text-slate-400">Data ini akan menjadi identitas instansi pada cetakan laporan presensi dan notifikasi orang tua.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Lembaga -->
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Nama Lembaga Bimbel <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Building2 class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.institution_name"
                                        @input="handleNameChange"
                                        type="text"
                                        placeholder="Contoh: Bimbel Prestasi Gemilang"
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step1Errors.institution_name || registerForm.errors.institution_name" class="text-[11px] text-rose-400 font-medium">
                                    {{ step1Errors.institution_name || registerForm.errors.institution_name }}
                                </p>
                            </div>

                            <!-- Subdomain / Slug Lembaga -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    ID / URL Singkat Lembaga <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative flex rounded-xl bg-slate-950/80 border border-slate-700/80 focus-within:border-orange-500 focus-within:ring-3 focus-within:ring-orange-500/20 overflow-hidden">
                                    <input
                                        v-model="registerForm.slug"
                                        type="text"
                                        placeholder="prestasigemilang"
                                        class="w-full h-10.5 px-3 text-xs bg-transparent text-white font-mono placeholder:text-slate-600 focus:outline-none"
                                    />
                                    <span class="flex items-center px-3 bg-slate-800/80 text-[10px] text-slate-400 font-medium border-l border-slate-700">
                                        .bimbel.id
                                    </span>
                                </div>
                                <p v-if="step1Errors.slug || registerForm.errors.slug" class="text-[11px] text-rose-400 font-medium">
                                    {{ step1Errors.slug || registerForm.errors.slug }}
                                </p>
                            </div>

                            <!-- Kota Domisili -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Kota / Kabupaten <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <MapPin class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.city"
                                        type="text"
                                        placeholder="Contoh: Bandung"
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step1Errors.city || registerForm.errors.city" class="text-[11px] text-rose-400 font-medium">
                                    {{ step1Errors.city || registerForm.errors.city }}
                                </p>
                            </div>

                            <!-- Telepon Lembaga -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    No. WhatsApp Lembaga <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Phone class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.phone"
                                        type="text"
                                        placeholder="081234567890"
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step1Errors.phone || registerForm.errors.phone" class="text-[11px] text-rose-400 font-medium">
                                    {{ step1Errors.phone || registerForm.errors.phone }}
                                </p>
                            </div>

                            <!-- Alamat Kantor -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">Alamat Singkat Kantor</label>
                                <input
                                    v-model="registerForm.address"
                                    type="text"
                                    placeholder="Jl. Merdeka No. 12"
                                    class="w-full h-10.5 px-3.5 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 2: AKUN ADMINISTRATOR UTAMA -->
                    <!-- ============================================== -->
                    <div v-if="currentStep === 2" class="space-y-4">
                        <div class="space-y-1 mb-4">
                            <h3 class="text-lg font-bold text-white">Akun Administrator Lembaga</h3>
                            <p class="text-xs text-slate-400">Akun ini memiliki hak akses penuh untuk mengelola guru/tutor, siswa, jadwal, dan sesi presensi.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nama Admin -->
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Nama Lengkap Penanggung Jawab / Admin <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <User class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.name"
                                        type="text"
                                        placeholder="Contoh: Muhammad Ilham, M.Pd."
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step2Errors.name || registerForm.errors.name" class="text-[11px] text-rose-400 font-medium">
                                    {{ step2Errors.name || registerForm.errors.name }}
                                </p>
                            </div>

                            <!-- Email Login Admin -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Email Admin (Untuk Login) <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Mail class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.email"
                                        type="email"
                                        placeholder="admin@bimbelanda.com"
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step2Errors.email || registerForm.errors.email" class="text-[11px] text-rose-400 font-medium">
                                    {{ step2Errors.email || registerForm.errors.email }}
                                </p>
                            </div>

                            <!-- WhatsApp Admin -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    No. WhatsApp Pribadi Admin <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Phone class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.admin_phone"
                                        type="text"
                                        placeholder="081298765432"
                                        class="w-full h-10.5 pl-10 pr-4 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                </div>
                                <p v-if="step2Errors.admin_phone || registerForm.errors.admin_phone" class="text-[11px] text-rose-400 font-medium">
                                    {{ step2Errors.admin_phone || registerForm.errors.admin_phone }}
                                </p>
                            </div>

                            <!-- Password -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Kata Sandi Baru (Min. 8 Karakter) <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Lock class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.password"
                                        :type="showRegisterPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        class="w-full h-10.5 pl-10 pr-10 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegisterPassword = !showRegisterPassword"
                                        class="absolute right-3.5 top-3 text-slate-500 hover:text-slate-300"
                                    >
                                        <Eye v-if="!showRegisterPassword" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="step2Errors.password || registerForm.errors.password" class="text-[11px] text-rose-400 font-medium">
                                    {{ step2Errors.password || registerForm.errors.password }}
                                </p>
                            </div>

                            <!-- Confirm Password -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Konfirmasi Kata Sandi <span class="text-orange-400">*</span>
                                </label>
                                <div class="relative">
                                    <Lock class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                                    <input
                                        v-model="registerForm.password_confirmation"
                                        :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        class="w-full h-10.5 pl-10 pr-10 rounded-xl bg-slate-950/80 border border-slate-700/80 focus:border-orange-500 focus:ring-3 focus:ring-orange-500/20 text-xs text-white placeholder:text-slate-600 font-medium focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm"
                                        class="absolute right-3.5 top-3 text-slate-500 hover:text-slate-300"
                                    >
                                        <Eye v-if="!showRegisterPasswordConfirm" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="step2Errors.password_confirmation || registerForm.errors.password_confirmation" class="text-[11px] text-rose-400 font-medium">
                                    {{ step2Errors.password_confirmation || registerForm.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 3: PAKET LAYANAN & FINALISASI -->
                    <!-- ============================================== -->
                    <div v-if="currentStep === 3" class="space-y-6">
                        <div class="space-y-1">
                            <h3 class="text-lg font-bold text-white">Pilih Paket Layanan & Skala Bimbel</h3>
                            <p class="text-xs text-slate-400">Pilih paket masa uji coba untuk mengaktifkan database lembaga Anda.</p>
                        </div>

                        <!-- Package selection cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                v-for="pkg in packages"
                                :key="pkg.id"
                                @click="registerForm.package_type = pkg.id"
                                class="cursor-pointer rounded-2xl p-4 border transition-all relative overflow-hidden"
                                :class="registerForm.package_type === pkg.id ? 'bg-orange-500/10 border-orange-500 ring-2 ring-orange-500/30' : 'bg-slate-950/40 border-slate-800 hover:border-slate-700'"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="pkg.recommended ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300'">
                                        {{ pkg.tag }}
                                    </span>
                                    <div
                                        class="h-4 w-4 rounded-full border flex items-center justify-center text-white"
                                        :class="registerForm.package_type === pkg.id ? 'bg-orange-500 border-orange-500' : 'border-slate-700'"
                                    >
                                        <Check v-if="registerForm.package_type === pkg.id" class="h-2.5 w-2.5" />
                                    </div>
                                </div>
                                <h4 class="font-bold text-sm text-white">{{ pkg.name }}</h4>
                                <p class="text-base font-black text-orange-400 mt-1">{{ pkg.price }}</p>
                                <p class="text-[11px] text-slate-400 mt-1.5 leading-snug">{{ pkg.desc }}</p>
                            </div>
                        </div>

                        <!-- Educational Program Badges -->
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-300">
                                Jenjang Program yang Dibuka (Bisa pilih lebih dari satu):
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="lvl in educationalLevels"
                                    :key="lvl"
                                    type="button"
                                    @click="toggleLevel(lvl)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all flex items-center gap-1.5"
                                    :class="registerForm.service_levels.includes(lvl) ? 'bg-orange-500 text-white border-orange-500' : 'bg-slate-950/60 text-slate-400 border-slate-800 hover:border-slate-700'"
                                >
                                    <Check v-if="registerForm.service_levels.includes(lvl)" class="h-3 w-3" />
                                    <span>{{ lvl }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Confirmation Preview Summary Card -->
                        <div class="rounded-2xl bg-slate-950/80 border border-slate-800 p-4 space-y-2 text-xs">
                            <div class="flex items-center gap-2 text-orange-400 font-bold">
                                <ShieldCheck class="h-4 w-4" />
                                <span>Ringkasan Data Lembaga Baru</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                                <div>
                                    <span class="text-slate-500 block">Lembaga:</span>
                                    <span class="font-semibold text-slate-200">{{ registerForm.institution_name }} ({{ registerForm.city }})</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Domain Singkat:</span>
                                    <span class="font-mono text-orange-300">{{ registerForm.slug }}.bimbel.id</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Admin Pengelola:</span>
                                    <span class="font-semibold text-slate-200">{{ registerForm.name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Email Login:</span>
                                    <span class="font-semibold text-slate-200">{{ registerForm.email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WIZARD BUTTON CONTROLS (With Loading Animations) -->
                    <div class="flex items-center justify-between pt-6 mt-6 border-t border-slate-800">
                        <!-- Back Button -->
                        <button
                            v-if="currentStep > 1"
                            @click="prevStep"
                            type="button"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all active:scale-95"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali</span>
                        </button>
                        <div v-else></div>

                        <!-- Next / Submit Button with Loading State -->
                        <button
                            v-if="currentStep < 3"
                            @click="nextStep"
                            type="button"
                            :disabled="isStepLoading"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:opacity-95 text-white text-xs font-bold shadow-lg shadow-orange-500/25 transition-all active:scale-95 disabled:opacity-50"
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
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:opacity-95 text-white text-xs font-bold shadow-lg shadow-orange-500/25 transition-all active:scale-95 disabled:opacity-50"
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
            </div>
        </main>

        <!-- FOOTER -->
        <footer class="w-full max-w-7xl mx-auto px-4 sm:px-8 py-5 text-center text-xs text-slate-500 z-10">
            <p>&copy; 2026 AbsensiBimbel.id &bull; Arsitektur Multi-Tenant Terisolasi Aman &bull; Stack Laravel 13 + Vue 3 SPA</p>
        </footer>
    </div>
</template>
