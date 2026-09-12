<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {
    User,
    Camera,
    Trash2,
    CheckCircle2,
    ShieldCheck,
    Building2,
    Calendar,
    Mail,
    Phone,
    GraduationCap,
    BookOpen,
    Info,
    Loader2,
    Lock
} from 'lucide-vue-next';
import { toast, confirmAction } from '@/composables/useNotification';

interface UserProfile {
    id: string;
    name: string;
    username: string;
    email: string;
    phone: string;
    role: string;
    avatar_url: string | null;
    status: string;
    created_at: string;
    tenant_name: string;
    details: {
        tentor?: {
            specialization: string;
            title_suffix?: string | null;
            phone?: string | null;
        };
        student?: {
            nis: string;
            study_group_name: string;
            education_level: string;
            academic_year: string;
            parent_phone?: string | null;
        };
    };
}

const props = defineProps<{
    profile: UserProfile;
}>();

// ==========================================
// PENGELOLAAN GANTI FOTO PROFIL
// ==========================================
const fileInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);
const selectedFile = ref<File | null>(null);
const isUploading = ref(false);

const handlePhotoSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    // Maksimal 5MB
    if (file.size > 5 * 1024 * 1024) {
        toast('Ukuran foto maksimal 5MB.', 'error');
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    selectedFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
};

const cancelPhotoSelection = () => {
    selectedFile.value = null;
    photoPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const submitPhoto = () => {
    if (!selectedFile.value) {
        toast('Silakan pilih foto terlebih dahulu.', 'warning');
        return;
    }

    isUploading.value = true;
    const formData = new FormData();
    formData.append('photo', selectedFile.value);

    router.post('/user/profile/photo', formData, {
        onSuccess: () => {
            toast('Foto profil berhasil diperbarui.', 'success');
            cancelPhotoSelection();
        },
        onError: (errors) => {
            const msg = errors.photo || 'Gagal memperbarui foto profil.';
            toast(msg, 'error');
        },
        onFinish: () => {
            isUploading.value = false;
        },
    });
};

const handleDeletePhoto = async () => {
    const confirmed = await confirmAction({
        title: 'Hapus Foto Profil?',
        text: 'Foto profil Anda saat ini akan dihapus dan kembali menggunakan inisial huruf nama.',
        confirmText: 'Ya, Hapus Foto',
        cancelText: 'Batal',
        icon: 'warning',
    });

    if (!confirmed) return;

    router.delete('/user/profile/photo', {
        onSuccess: () => {
            toast('Foto profil berhasil dihapus.', 'success');
        },
        onError: () => {
            toast('Gagal menghapus foto profil.', 'error');
        },
    });
};

const getRoleLabel = (role: string): string => {
    switch (role) {
        case 'superadmin':
            return 'Super Administrator';
        case 'admin_bimbel':
            return 'Administrator Lembaga Bimbel';
        case 'tutor':
            return 'Guru / Tentor Bimbel';
        case 'siswa':
        case 'orang_tua':
            return 'Wali Murid / Siswa';
        default:
            return role;
    }
};
</script>

<template>
    <AuthenticatedLayout title="Profil Saya">
        <Head title="Profil Saya - Sistem Absensi Bimbel" />

        <div class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Header Halaman -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                        <User class="h-6 w-6 text-orange-600" />
                        <span>Profil Saya</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Informasi akun pengguna terdaftar pada {{ profile.tenant_name }}
                    </p>
                </div>

                <!-- Info Label: Hanya Ganti Foto -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-orange-50 border border-orange-200/60 text-orange-700 text-xs font-bold self-start sm:self-auto">
                    <Info class="h-4 w-4 shrink-0 text-orange-500" />
                    <span>Hanya Bisa Mengubah Foto Profil</span>
                </div>
            </div>

            <!-- Kartu Banner Utama & Avatar -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <!-- Background Cover Gradient -->
                <div class="h-32 sm:h-40 bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 relative">
                    <div class="absolute inset-0 bg-black/10"></div>
                </div>

                <!-- Avatar & Action Area -->
                <div class="px-6 sm:px-8 pb-6 relative">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 -mt-16 sm:-mt-20">
                        <!-- Foto Avatar dengan Tombol Kamera -->
                        <div class="relative group inline-block self-center sm:self-auto">
                            <div class="h-28 w-28 sm:h-32 sm:w-32 rounded-3xl bg-white p-1.5 shadow-xl ring-4 ring-white/80 overflow-hidden">
                                <div class="h-full w-full rounded-2xl overflow-hidden bg-gradient-to-tr from-orange-500 to-amber-400 flex items-center justify-center text-white font-black text-3xl sm:text-4xl shadow-inner">
                                    <img
                                        v-if="photoPreview || profile.avatar_url"
                                        :src="photoPreview || profile.avatar_url!"
                                        :alt="profile.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ profile.name.charAt(0).toUpperCase() }}</span>
                                </div>
                            </div>

                            <!-- Tombol Upload Cepat Kamera di Atas Foto -->
                            <button
                                type="button"
                                @click="fileInput?.click()"
                                class="absolute bottom-1 right-1 h-9 w-9 rounded-xl bg-orange-600 hover:bg-orange-700 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer border-2 border-white"
                                title="Pilih Foto Baru"
                            >
                                <Camera class="h-4 w-4" />
                            </button>

                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                class="hidden"
                                @change="handlePhotoSelect"
                            />
                        </div>

                        <!-- Tombol Aksi Hapus / Simpan Foto -->
                        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2.5 pt-2">
                            <!-- Preview Actions jika ada file baru dipilih -->
                            <template v-if="photoPreview">
                                <button
                                    type="button"
                                    @click="cancelPhotoSelection"
                                    :disabled="isUploading"
                                    class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="submitPhoto"
                                    :disabled="isUploading"
                                    class="px-4 py-2 rounded-xl text-xs font-bold bg-orange-600 hover:bg-orange-700 text-white shadow-sm transition-colors flex items-center gap-1.5"
                                >
                                    <Loader2 v-if="isUploading" class="h-3.5 w-3.5 animate-spin" />
                                    <CheckCircle2 v-else class="h-3.5 w-3.5" />
                                    <span>Simpan Foto Baru</span>
                                </button>
                            </template>

                            <!-- Tombol Hapus Foto Lama jika ada -->
                            <template v-else-if="profile.avatar_url">
                                <button
                                    type="button"
                                    @click="handleDeletePhoto"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors flex items-center gap-1.5"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    <span>Hapus Foto</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Identitas Dasar Pengguna -->
                    <div class="mt-4 text-center sm:text-left">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <h2 class="text-xl font-black text-slate-900">{{ profile.name }}</h2>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-100 text-orange-700 w-fit mx-auto sm:mx-0">
                                <ShieldCheck class="h-3 w-3" />
                                {{ getRoleLabel(profile.role) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 flex items-center justify-center sm:justify-start gap-2">
                            <span>{{ profile.email !== '-' ? profile.email : 'ID: ' + profile.username }}</span>
                            <span>&bull;</span>
                            <span>{{ profile.tenant_name }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pesan Pemberitahuan Read-Only -->
            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/60 flex items-start gap-3">
                <Lock class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="text-xs text-amber-900 leading-relaxed">
                    <span class="font-bold block">Informasi Akun Dikelola Administrator:</span>
                    Data nama, username, nomor kontak, serta kelas/kelompok Anda ditetapkan secara resmi oleh administrator lembaga bimbingan belajar. Anda dapat memperbarui foto profil avatar Anda dengan menekan ikon kamera di atas.
                </div>
            </div>

            <!-- Detail Data Pengguna (Read-Only) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <User class="h-4 w-4 text-orange-600" />
                    <span>Rincian Data Profil</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Nama Lengkap -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Nama Lengkap</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.name }}</span>
                    </div>

                    <!-- Username / NIS -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Username / NIS</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 font-mono block">{{ profile.username }}</span>
                    </div>

                    <!-- Email -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Alamat Email</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block flex items-center gap-1.5">
                            <Mail class="h-3.5 w-3.5 text-slate-400" />
                            <span>{{ profile.email }}</span>
                        </span>
                    </div>

                    <!-- Nomor Handphone -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Nomor Handphone</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block flex items-center gap-1.5">
                            <Phone class="h-3.5 w-3.5 text-slate-400" />
                            <span>{{ profile.phone }}</span>
                        </span>
                    </div>

                    <!-- Lembaga Bimbel -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Lembaga Bimbingan Belajar</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block flex items-center gap-1.5">
                            <Building2 class="h-3.5 w-3.5 text-slate-400" />
                            <span>{{ profile.tenant_name }}</span>
                        </span>
                    </div>

                    <!-- Tanggal Bergabung -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Tanggal Terdaftar</span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5 text-slate-400" />
                            <span>{{ profile.created_at }}</span>
                        </span>
                    </div>
                </div>

                <!-- Rincian Tambahan Khusus Peran Tentor -->
                <div v-if="profile.details.tentor" class="pt-4 border-t border-slate-100 space-y-3">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <BookOpen class="h-3.5 w-3.5 text-orange-600" />
                        <span>Kualifikasi Guru / Pengajar</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 bg-orange-50/50 rounded-2xl border border-orange-100">
                            <span class="text-[11px] font-semibold text-orange-700 block">Spesialisasi Mata Pelajaran</span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.details.tentor.specialization }}</span>
                        </div>
                        <div class="p-3.5 bg-orange-50/50 rounded-2xl border border-orange-100">
                            <span class="text-[11px] font-semibold text-orange-700 block">Gelar Akademik</span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.details.tentor.title_suffix || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Rincian Tambahan Khusus Peran Siswa / Wali Murid -->
                <div v-if="profile.details.student" class="pt-4 border-t border-slate-100 space-y-3">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <GraduationCap class="h-3.5 w-3.5 text-orange-600" />
                        <span>Penempatan Kelas Belajar Ananda</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 bg-orange-50/50 rounded-2xl border border-orange-100">
                            <span class="text-[11px] font-semibold text-orange-700 block">Kelompok Belajar</span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.details.student.study_group_name }}</span>
                        </div>
                        <div class="p-3.5 bg-orange-50/50 rounded-2xl border border-orange-100">
                            <span class="text-[11px] font-semibold text-orange-700 block">Jenjang Pendidikan</span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.details.student.education_level }}</span>
                        </div>
                        <div class="p-3.5 bg-orange-50/50 rounded-2xl border border-orange-100">
                            <span class="text-[11px] font-semibold text-orange-700 block">Tahun Pelajaran</span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block">{{ profile.details.student.academic_year }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
