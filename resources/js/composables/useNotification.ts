import Swal, { type SweetAlertOptions } from 'sweetalert2';

export function useNotification() {
    /**
     * Toast notification (Auto dismiss 3 detik, terletak di pojok kanan atas)
     */
    const toast = (message: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toastEl) => {
                toastEl.onmouseenter = Swal.stopTimer;
                toastEl.onmouseleave = Swal.resumeTimer;
            }
        });

        return Toast.fire({
            icon,
            title: message
        });
    };

    /**
     * SweetAlert2 dialog konfirmasi untuk aksi destruktif atau penting
     */
    const confirmAction = async (options?: {
        title?: string;
        text?: string;
        confirmText?: string;
        cancelText?: string;
        icon?: 'warning' | 'question' | 'info' | 'error';
    }): Promise<boolean> => {
        const result = await Swal.fire({
            title: options?.title ?? 'Apakah Anda yakin?',
            text: options?.text ?? 'Tindakan ini tidak dapat dibatalkan!',
            icon: options?.icon ?? 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5', // Indigo
            cancelButtonColor: '#ef4444',  // Red
            confirmButtonText: options?.confirmText ?? 'Ya, Lanjutkan',
            cancelButtonText: options?.cancelText ?? 'Batal',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-2xl font-sans',
                confirmButton: 'px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm text-white',
                cancelButton: 'px-5 py-2.5 rounded-lg text-sm font-semibold text-white'
            }
        });

        return result.isConfirmed;
    };

    return {
        toast,
        confirmAction
    };
}
