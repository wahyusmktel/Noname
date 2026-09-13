<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Development Sedang Tidak Aktif | Bimbel No name</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN for Standalone View -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-amber-50/30 to-slate-100 text-slate-800 min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-orange-500 selection:text-white">

    <!-- Top Minimal Navbar -->
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img 
                src="/images/logo_bnn.png" 
                alt="Logo Bimbel No name" 
                class="h-10 w-10 sm:h-11 sm:w-11 object-contain rounded-2xl bg-white p-1 border border-slate-200/80 shadow-xs"
                onerror="this.style.display='none'"
            >
            <div>
                <span class="text-base sm:text-lg font-black tracking-tight text-slate-900 block leading-tight">
                    Bimbel No name
                </span>
                <span class="text-[11px] font-semibold text-amber-600 block">
                    Development Environment
                </span>
            </div>
        </div>

        <a 
            href="https://bimbelnoname.com" 
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-orange-600 hover:border-orange-200 font-bold text-xs shadow-2xs transition-all"
        >
            <span>Ke Portal Production</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </header>

    <!-- Main Hero Section -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-12">
        <div class="max-w-xl w-full text-center space-y-8">
            
            <!-- Animated Icon Graphic -->
            <div class="relative mx-auto w-24 h-24 sm:w-28 sm:h-28 flex items-center justify-center">
                <div class="absolute inset-0 rounded-3xl bg-amber-400/20 blur-xl animate-pulse"></div>
                <div class="relative w-full h-full rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 p-0.5 shadow-xl shadow-orange-500/20">
                    <div class="w-full h-full bg-white rounded-[22px] flex items-center justify-center text-orange-600">
                        <!-- Server Lock Icon -->
                        <svg class="w-12 h-12 sm:w-14 sm:h-14 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                    </div>
                </div>
                <!-- Mini Lock Badge -->
                <div class="absolute -bottom-2 -right-2 bg-rose-500 text-white p-1.5 rounded-full border-2 border-white shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>

            <!-- Status Pill -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-rose-50 border border-rose-200/80 text-rose-700 text-xs font-bold tracking-wide shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                <span>STATUS: LINGKUNGAN PENGEMBANGAN NONAKTIF</span>
            </div>

            <!-- Headlines -->
            <div class="space-y-3">
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    Website Development Sedang Tidak Aktif
                </h1>
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-lg mx-auto">
                    {{ $message ?? 'Sistem lingkungan development (dev.bimbelnoname.com) saat ini dinonaktifkan oleh Administrator. Seluruh layanan belajar, absensi, dan administrasi resmi dialihkan ke website utama.' }}
                </p>
            </div>

            <!-- Notice Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs text-left space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase tracking-wider">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Layanan Utama Tetap Beroperasi Normal</span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Portal absensi guru, monitoring orang tua, pendaftaran siswa, dan seluruh basis data bimbel berjalan normal di server production. Silakan gunakan tautan di bawah ini untuk mengakses sistem resmi.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a 
                    href="https://bimbelnoname.com" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-3.5 rounded-xl bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 text-white font-bold text-sm shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:opacity-95 active:scale-95 transition-all cursor-pointer"
                >
                    <span>Kunjungi Website Resmi Production</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
                
                <a 
                    href="https://wa.me/6281234567890?text=Halo%20Admin%2C%20saya%20ingin%20bertanya%20mengenai%20akses%20Bimbel%20No%20name" 
                    target="_blank"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 hover:text-orange-600 border border-slate-200 font-bold text-sm shadow-2xs transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.311.045-.698.071-2.022-.477-1.696-.701-2.776-2.433-2.861-2.545-.084-.114-.683-.911-.683-1.737 0-.827.433-1.233.587-1.39.155-.157.34-.196.452-.196.113 0 .227.001.326.006.105.005.244-.04.382.292.144.348.491 1.2.534 1.288.043.088.072.19.014.305-.058.115-.087.186-.173.288-.087.102-.183.228-.261.306-.087.087-.178.182-.077.355.101.173.45 0.742 1.054 1.28 0.777.692 1.432.907 1.635 1.008.203.102.322.088.442-.05.12-.138.514-.598.652-.803.138-.205.276-.172.464-.102.188.069 1.196.564 1.402.667.206.103.344.153.394.24.05.087.05.506-.094.911z"/>
                    </svg>
                    <span>Bantuan Admin</span>
                </a>
            </div>

            <!-- Refresh check button -->
            <div>
                <button 
                    onclick="window.location.reload()" 
                    class="text-xs font-semibold text-slate-400 hover:text-orange-600 transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Coba muat ulang halaman</span>
                </button>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs font-medium text-slate-400">
        &copy; {{ date('Y') }} Bimbel No name. Hak Cipta Dilindungi.
    </footer>

</body>
</html>
