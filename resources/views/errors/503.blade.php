<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Sedang Dalam Pemeliharaan | Bimbel No name</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- Auto reload check every 30 seconds -->
    <meta http-equiv="refresh" content="30">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN for standalone maintenance page -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased selection:bg-orange-500 selection:text-white flex flex-col justify-between relative overflow-x-hidden">
    <!-- Ambient Background Glows -->
    <div class="absolute -top-40 -left-40 w-96 sm:w-128 h-96 sm:h-128 bg-amber-200/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-40 w-96 sm:w-128 h-96 sm:h-128 bg-orange-200/30 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header Navigation Bar -->
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between relative z-10">
        <div class="flex items-center gap-3">
            <img
                src="/images/logo_bnn.png"
                alt="Logo Bimbel No name"
                class="h-10 w-10 sm:h-12 sm:w-12 object-contain rounded-2xl bg-white p-1 border border-slate-200/80 shadow-xs"
            />
            <div>
                <span class="text-base sm:text-lg font-black tracking-tight text-slate-900 block leading-tight">
                    Bimbel No name
                </span>
                <p class="text-[11px] font-semibold text-orange-600">Sistem Presensi & Portal Akademik</p>
            </div>
        </div>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold shadow-2xs">
            <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
            <span>Maintenance Mode</span>
        </div>
    </header>

    <!-- Main Maintenance Hero Section -->
    <main class="flex-1 max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-14 flex items-center justify-center relative z-10">
        <div class="w-full rounded-3xl bg-white border border-slate-200/90 shadow-xl p-8 sm:p-12 text-center space-y-7 relative overflow-hidden">
            <!-- Decorative Accent Top Line -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600"></div>

            <!-- Animated Icon Graphic -->
            <div class="mx-auto w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-gradient-to-br from-amber-100 to-orange-100 border border-orange-200/80 flex items-center justify-center text-orange-600 shadow-inner">
                <svg class="h-10 w-10 sm:h-12 sm:w-12 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>

            <!-- Content Headings -->
            <div class="space-y-3">
                <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-orange-50 border border-orange-200 text-orange-700 text-xs font-bold">
                    <span>Sedang Ditingkatkan</span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                    Sistem Sedang Dalam Pemeliharaan Berkala
                </h1>

                <p class="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto leading-relaxed">
                    Kami sedang melakukan peningkatan performa, pemeliharaan basis data, dan pembaruan infrastruktur server presensi bimbingan belajar. Sistem akan segera kembali aktif dalam beberapa saat.
                </p>
            </div>

            <!-- Highlights Checklist -->
            <div class="max-w-md mx-auto rounded-2xl bg-slate-50/80 border border-slate-200/80 p-4 text-left space-y-2.5 text-xs text-slate-600 font-medium">
                <div class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Peningkatan Kecepatan & Respons Server</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Enkripsi & Pencadangan Data Presensi Terjadwal</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Sinkronisasi Data Real-Time Siswa & Guru</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <button
                    onclick="window.location.reload()"
                    type="button"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-xs shadow-md shadow-orange-500/25 hover:opacity-95 active:scale-95 transition-all cursor-pointer"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Muat Ulang Halaman</span>
                </button>

                <a
                    href="https://wa.me/6281377646187"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs active:scale-95 transition-all"
                >
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.301-.15-1.78-.878-2.056-.978-.276-.1-.476-.15-.676.15-.2.3-.777.978-.952 1.178-.176.201-.351.226-.652.076-.301-.15-1.27-.468-2.42-1.493-.895-.798-1.5-1.784-1.675-2.085-.175-.301-.019-.464.132-.614.136-.134.301-.35.452-.526.15-.175.2-.301.301-.501.1-.2.05-.376-.025-.526-.076-.15-.677-1.633-.928-2.235-.245-.586-.494-.506-.677-.516h-.577c-.2 0-.526.075-.802.376s-1.053 1.028-1.053 2.508c0 1.48 1.078 2.909 1.229 3.11.15.2 2.122 3.24 5.14 4.542.718.31 1.279.495 1.716.634.721.229 1.378.197 1.897.119.58-.088 1.78-.728 2.03-1.43.25-.702.25-1.304.175-1.43-.075-.125-.276-.201-.577-.35z"/>
                        <path d="M12.042 2C6.518 2 2.029 6.489 2.029 12.013c0 1.86.51 3.67 1.479 5.253L2 22l4.877-1.46c1.528.892 3.275 1.373 5.165 1.373 5.524 0 10.013-4.489 10.013-10.013C22.055 6.489 17.566 2 12.042 2zm0 18.253c-1.62 0-3.2-.434-4.582-1.256l-.329-.196-3.4 1.018 1.038-3.32-.214-.34A8.17 8.17 0 0 1 3.829 12.013c0-4.529 3.684-8.213 8.213-8.213 4.529 0 8.213 3.684 8.213 8.213 0 4.529-3.684 8.213-8.213 8.213z"/>
                    </svg>
                    <span>Hubungi CS WhatsApp</span>
                </a>
            </div>

            <!-- Auto Refresh Note -->
            <p class="text-[11px] text-slate-400 pt-3">
                Halaman ini akan otomatis memeriksa pembaruan setiap 30 detik.
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 text-center text-[11px] text-slate-400 relative z-10">
        &copy; {{ date('Y') }} Bimbel No name. Seluruh hak cipta dilindungi.
    </footer>
</body>
</html>
