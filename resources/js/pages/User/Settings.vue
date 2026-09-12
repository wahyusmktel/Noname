<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    SlidersHorizontal,
    KeyRound,
    Eye,
    EyeOff,
    CheckCircle2,
    Shield,
    Lock,
    Clock,
    AlertCircle,
    Loader2
} from 'lucide-vue-next';
import { toast, confirmAction } from '@/composables/useNotification';

interface AccountInfo {
    id: string;
    name: string;
    username: string;
    email: string;
    phone: string;
    role: string;
    status: string;
    tenant_name: string;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    account: AccountInfo;
}>();

// ==========================================
// FORM UBAH KATA SANDI
// ==========================================
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const handleUpdatePassword = async () => {
    if (!passwordForm.current_password) {
        toast('Kata sandi saat ini wajib diisi.', 'warning');
        return;
    }

    if (!passwordForm.password) {
        toast('Kata sandi baru wajib diisi.', 'warning');
        return;
    }

    if (passwordForm.password.length < 8) {
        toast('Kata sandi baru minimal 8 karakter.', 'warning');
        return;
    }

    if (passwordForm.password !== passwordForm.password_confirmation) {
        toast('Konfirmasi kata sandi baru tidak cocok.', 'warning');
        return;
    }

    const confirmed = await confirmAction({
        title: 'Perbarui Kata Sandi?',
        text: 'Pastikan Anda mengingat kata sandi baru ini untuk login berikutnya.',
        confirmText: 'Ya, Simpan Kata Sandi',
        cancelText: 'Batal',
        icon: 'question',
    });

    if (!confirmed) return;

    passwordForm.put('/user/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            toast('Kata sandi berhasil diperbarui.', 'success');
            passwordForm.reset();
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            if (firstErr) {
                toast(firstErr as string, 'error');
            }
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Pengaturan Akun">
        <Head title="Pengaturan Akun & Keamanan - Sistem Absensi Bimbel" />

        <div class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                        <SlidersHorizontal class="h-6 w-6 text-orange-600" />
                        <span>Pengaturan Akun & Keamanan</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola kata sandi dan pengaturan keamanan kredensial akun Anda
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri: Form Ubah Kata Sandi (2 Kolom) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <KeyRound class="h-4 w-4 text-orange-600" />
                                <span>Ubah Kata Sandi Akun</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Perbarui kata sandi secara berkala untuk menjaga keamanan akun Anda
                            </p>
                        </div>

                        <form @submit.prevent="handleUpdatePassword" class="space-y-4">
                            <!-- Kata Sandi Saat Ini -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        v-model="passwordForm.current_password"
                                        placeholder="Masukkan kata sandi lama Anda"
                                        required
                                        class="w-full h-11 px-3.5 pr-10 text-xs bg-slate-50 border rounded-xl outline-none focus:bg-white transition-all text-slate-800 font-medium"
                                        :class="passwordForm.errors.current_password ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-orange-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-500 mt-1">
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <!-- Kata Sandi Baru -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        :type="showNewPassword ? 'text' : 'password'"
                                        v-model="passwordForm.password"
                                        placeholder="Minimal 8 karakter huruf, angka, atau simbol"
                                        required
                                        class="w-full h-11 px-3.5 pr-10 text-xs bg-slate-50 border rounded-xl outline-none focus:bg-white transition-all text-slate-800 font-medium"
                                        :class="passwordForm.errors.password ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-orange-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showNewPassword = !showNewPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <EyeOff v-if="showNewPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="text-xs text-rose-500 mt-1">
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <!-- Konfirmasi Kata Sandi Baru -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                    Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        v-model="passwordForm.password_confirmation"
                                        placeholder="Ulangi kata sandi baru Anda"
                                        required
                                        class="w-full h-11 px-3.5 pr-10 text-xs bg-slate-50 border rounded-xl outline-none focus:bg-white transition-all text-slate-800 font-medium"
                                        :class="passwordForm.errors.password_confirmation ? 'border-rose-400 focus:border-rose-500' : 'border-slate-200 focus:border-orange-500'"
                                    />
                                    <button
                                        type="button"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-rose-500 mt-1">
                                    {{ passwordForm.errors.password_confirmation }}
                                </p>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="h-11 px-6 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-md shadow-orange-500/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                                >
                                    <Loader2 v-if="passwordForm.processing" class="h-4 w-4 animate-spin" />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    <span>Simpan Kata Sandi</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kolom Kanan: Status Akun & Keamanan Sesi -->
                <div class="space-y-6">
                    <!-- Status Keamanan Akun -->
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <Shield class="h-4 w-4 text-emerald-600" />
                            <span>Status Keamanan Akun</span>
                        </h3>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500">Status Akun</span>
                                <span class="font-bold text-emerald-600 flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Aktif Terverifikasi</span>
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500">Tipe Enkripsi</span>
                                <span class="font-mono font-bold text-slate-700">Bcrypt v4</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500">Terakhir Diperbarui</span>
                                <span class="font-semibold text-slate-700 text-[11px]">{{ account.updated_at }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Panduan Keamanan Kredensial -->
                    <div class="bg-gradient-to-br from-orange-500/10 via-amber-500/5 to-transparent rounded-3xl border border-orange-200/80 p-5 space-y-2.5">
                        <div class="flex items-center gap-2 text-orange-800 font-bold text-xs">
                            <Lock class="h-4 w-4 text-orange-600" />
                            <span>Tips Menjaga Keamanan Akun</span>
                        </div>
                        <ul class="text-[11px] text-slate-600 space-y-1.5 list-disc list-inside leading-relaxed">
                            <li>Jangan membagikan kata sandi Anda kepada orang lain.</li>
                            <li>Gunakan kombinasi minimal 8 karakter berupa huruf, angka, dan simbol unik.</li>
                            <li>Selalu klik tombol "Keluar dari Sistem" jika menggunakan perangkat umum/bersama.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
