import Swal, { type SweetAlertOptions } from 'sweetalert2';

/**
 * Toast notification (Auto dismiss 3 detik, terletak di pojok kanan atas)
 */
export const toast = (message: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
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
 * SweetAlert2 dialog konfirmasi fleksibel:
 * Mendukung pemanggilan berbasis opsi objek: `await confirmAction({ title, text })`
 * DAN pemanggilan berbasis callback: `confirmAction(title, text, () => { ... })`
 */
export const confirmAction = async (
    titleOrOptions?: string | {
        title?: string;
        text?: string;
        confirmText?: string;
        confirmButtonText?: string;
        cancelText?: string;
        cancelButtonText?: string;
        icon?: 'warning' | 'question' | 'info' | 'error';
    },
    textOrCallback?: string | (() => void),
    callbackOrOptions?: (() => void) | {
        confirmText?: string;
        confirmButtonText?: string;
        cancelText?: string;
        cancelButtonText?: string;
        icon?: 'warning' | 'question' | 'info' | 'error';
    }
): Promise<boolean> => {
    let title = 'Apakah Anda yakin?';
    let text = 'Tindakan ini tidak dapat dibatalkan!';
    let confirmButtonText = 'Ya, Lanjutkan';
    let cancelButtonText = 'Batal';
    let icon: 'warning' | 'question' | 'info' | 'error' = 'warning';
    let onConfirm: (() => void) | null = null;

    if (typeof titleOrOptions === 'string') {
        title = titleOrOptions;
        if (typeof textOrCallback === 'string') {
            text = textOrCallback;
        } else if (typeof textOrCallback === 'function') {
            onConfirm = textOrCallback;
        }

        if (typeof callbackOrOptions === 'function') {
            onConfirm = callbackOrOptions;
        } else if (callbackOrOptions && typeof callbackOrOptions === 'object') {
            confirmButtonText = callbackOrOptions.confirmButtonText ?? callbackOrOptions.confirmText ?? confirmButtonText;
            cancelButtonText = callbackOrOptions.cancelButtonText ?? callbackOrOptions.cancelText ?? cancelButtonText;
            icon = callbackOrOptions.icon ?? icon;
        }
    } else if (titleOrOptions && typeof titleOrOptions === 'object') {
        title = titleOrOptions.title ?? title;
        text = titleOrOptions.text ?? text;
        confirmButtonText = titleOrOptions.confirmButtonText ?? titleOrOptions.confirmText ?? confirmButtonText;
        cancelButtonText = titleOrOptions.cancelButtonText ?? titleOrOptions.cancelText ?? cancelButtonText;
        icon = titleOrOptions.icon ?? icon;
        if (typeof textOrCallback === 'function') {
            onConfirm = textOrCallback;
        }
    }

    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#f97316', // Orange theme
        cancelButtonColor: '#64748b',  // Slate
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'rounded-2xl font-sans',
            confirmButton: 'px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm text-white cursor-pointer',
            cancelButton: 'px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 cursor-pointer'
        }
    });

    if (result.isConfirmed && onConfirm) {
        onConfirm();
    }

    return result.isConfirmed;
};

export function useNotification() {
    return {
        toast,
        confirmAction
    };
}
