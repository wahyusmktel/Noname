<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useNotification } from '@/composables/useNotification';
import {
    Server,
    Globe,
    Power,
    CheckCircle2,
    XCircle,
    ExternalLink,
    AlertTriangle,
    Clock,
    User,
    ShieldAlert,
    Save,
    RefreshCw
} from 'lucide-vue-next';

const props = defineProps<{
    status: {
        is_active: boolean;
        message: string;
        updated_at: string | null;
        updated_by: string | null;
    };
    dev_url: string;
}>();

const { toast, confirmAction } = useNotification();

const form = useForm({
    is_active: props.status.is_active,
    message: props.status.message || 'Website development sedang tidak aktif. Seluruh layanan resmi dialihkan ke website utama https://bimbelnoname.com.',
});

const isSubmitting = ref(false);

const handleToggleStatus = async (targetActive: boolean) => {
    const title = targetActive
        ? 'Aktifkan Website Dev?'
        : 'Nonaktifkan Website Dev?';
    
    const text = targetActive
        ? 'Website https://dev.bimbelnoname.com akan dapat diakses kembali secara publik.'
        : 'Website https://dev.bimbelnoname.com akan ditutup dan pengunjung akan melihat halaman pemberitahuan.';

    const confirmed = await confirmAction({
        title,
        text,
        confirmButtonText: targetActive ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        icon: targetActive ? 'question' : 'warning',
    });

    if (!confirmed) return;

    isSubmitting.value = true;
    form.is_active = targetActive;

    form.post('/system/dev-environment/toggle', {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            isSubmitting.value = false;
            toast('Gagal memperbarui status: ' + (Object.values(errors)[0] || 'Terjadi kesalahan'), 'error');
        }
    });
};

const handleSaveMessage = () => {
    isSubmitting.value = true;
    form.post('/system/dev-environment/toggle', {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            toast('Pesan pemberitahuan berhasil disimpan', 'success');
        },
        onError: (errors) => {
            isSubmitting.value = false;
            toast('Gagal menyimpan pesan: ' + (Object.values(errors)[0] || 'Terjadi kesalahan'), 'error');
        }
    });
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head title="Kontrol Lingkungan Dev" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-6 pb-12">
            
            <!-- Header Banner -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/20 shrink-0">
                        <Server class="h-7 w-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                Kontrol Lingkungan Website Dev
                            </h1>
                            <span 
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                                :class="status.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                            >
                                <span class="h-2 w-2 rounded-full" :class="status.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                <span>{{ status.is_active ? 'ONLINE / AKTIF' : 'OFFLINE / NONAKTIF' }}</span>
                            </span>
                        </div>
                        <p class="text-slate-500 text-xs sm:text-sm mt-1">
                            Kelola ketersediaan akses publik untuk lingkungan pengujian (<span class="font-semibold text-slate-700">{{ dev_url }}</span>).
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center">
                    <a 
                        :href="dev_url" 
                        target="_blank" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 hover:border-orange-300 bg-white hover:bg-slate-50 text-slate-700 hover:text-orange-600 font-bold text-xs shadow-2xs transition-all"
                    >
                        <Globe class="h-4 w-4 text-orange-500" />
                        <span>Kunjungi Web Dev</span>
                        <ExternalLink class="h-3.5 w-3.5 text-slate-400" />
                    </a>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left: Status Card & Action Toggle (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Status Action Card -->
                    <div 
                        class="rounded-3xl p-6 sm:p-8 border transition-all"
                        :class="status.is_active 
                            ? 'bg-gradient-to-br from-emerald-500/5 via-white to-emerald-500/10 border-emerald-200/80 shadow-emerald-500/5 shadow-xl' 
                            : 'bg-gradient-to-br from-rose-500/5 via-white to-rose-500/10 border-rose-200/80 shadow-rose-500/5 shadow-xl'"
                    >
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                            
                            <div class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Status Ketersediaan Saat Ini
                                </span>
                                <div class="flex items-center gap-3">
                                    <div 
                                        class="h-12 w-12 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-md"
                                        :class="status.is_active ? 'bg-emerald-500 shadow-emerald-500/20' : 'bg-rose-500 shadow-rose-500/20'"
                                    >
                                        <CheckCircle2 v-if="status.is_active" class="h-6 w-6" />
                                        <XCircle v-else class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg sm:text-xl font-black text-slate-900">
                                            {{ status.is_active ? 'Website Dev Sedang Aktif' : 'Website Dev Dinonaktifkan' }}
                                        </h3>
                                        <p class="text-xs sm:text-sm text-slate-500">
                                            {{ status.is_active 
                                                ? 'Pengguna dapat membuka halaman web dev secara normal.' 
                                                : 'Pengunjung yang membuka web dev akan melihat halaman khusus bahwa web sedang tidak aktif.' 
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Big Toggle Switch Button -->
                            <div>
                                <button
                                    v-if="status.is_active"
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="handleToggleStatus(false)"
                                    class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-lg shadow-rose-600/20 hover:shadow-rose-600/30 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                                >
                                    <Power class="h-4 w-4" />
                                    <span>Nonaktifkan Dev</span>
                                </button>
                                
                                <button
                                    v-else
                                    type="button"
                                    :disabled="isSubmitting"
                                    @click="handleToggleStatus(true)"
                                    class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/30 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                                >
                                    <Power class="h-4 w-4" />
                                    <span>Aktifkan Dev Sekarang</span>
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Custom Message Form Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">
                                    Pesan Halaman Penonaktifan
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Teks yang akan dibaca oleh pengunjung saat mencoba mengakses website dev ketika status nonaktif.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Teks Pemberitahuan Pengunjung
                            </label>
                            <textarea
                                v-model="form.message"
                                rows="3"
                                class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition-all outline-hidden"
                                placeholder="Tuliskan pesan pemberitahuan untuk pengunjung..."
                            ></textarea>
                            <p class="text-[11px] text-slate-400">
                                Contoh: "Website development sedang tidak aktif. Fitur baru sedang dipersiapkan oleh tim pengembang."
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="button"
                                :disabled="isSubmitting || form.processing"
                                @click="handleSaveMessage"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow-md shadow-orange-600/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                            >
                                <Save class="h-4 w-4" />
                                <span>Simpan Pesan Pemberitahuan</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Right: Information & Preview Guide (1 Col) -->
                <div class="space-y-6">
                    
                    <!-- Audit / Update Info -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <Clock class="h-4 w-4 text-orange-500" />
                            <span>Riwayat Pembaruan Status</span>
                        </h3>
                        
                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-slate-500">Terakhir Diubah:</span>
                                <span class="font-semibold text-slate-800">{{ formatDate(status.updated_at) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-slate-500">Oleh Pengguna:</span>
                                <span class="font-semibold text-slate-800">{{ status.updated_by || 'Sistem' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-slate-500">Target URL:</span>
                                <a :href="dev_url" target="_blank" class="font-semibold text-orange-600 hover:underline">
                                    {{ dev_url }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Security / Technical Guide -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50/50 rounded-3xl p-6 border border-amber-200/80 shadow-xs space-y-3">
                        <div class="flex items-center gap-2 text-amber-800 text-xs font-bold uppercase tracking-wider">
                            <ShieldAlert class="h-4 w-4 text-amber-600" />
                            <span>Informasi Teknis</span>
                        </div>
                        <ul class="text-xs text-amber-900/80 space-y-2 leading-relaxed list-disc list-inside">
                            <li>Website <strong>production (bimbelnoname.com)</strong> akan tetap aktif 100% dan tidak terpengaruh oleh saklar ini.</li>
                            <li>Ketika dinonaktifkan, dev akan merespons dengan kode <strong>HTTP 503</strong> beserta tautan langsung kembali ke website production.</li>
                            <li>Bypass khusus untuk tim teknis: gunakan parameter <code class="bg-white px-1.5 py-0.5 rounded border border-amber-300 font-mono text-[10px]">?bypass_dev=bnn_dev_bypass_2026</code> jika sewaktu-waktu perlu memeriksa dev saat status offline.</li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
